import os
import sys
from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
import pyrfc
from datetime import datetime
import logging
import traceback
import mysql.connector
from mysql.connector import Error
import time
import json

# Load environment variables
load_dotenv()

app = Flask(__name__)
CORS(app)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class SAPConnector:
    def __init__(self):
        self.conn = None
        self.params = {
            'ashost': os.getenv('SAP_ASHOST'),
            'sysnr': os.getenv('SAP_SYSNR'),
            'client': os.getenv('SAP_CLIENT'),
            'user': os.getenv('SAP_USERNAME'),
            'passwd': os.getenv('SAP_PASSWORD'),
            'lang': os.getenv('SAP_LANG', 'EN')
        }

    def connect(self):
        """Connect to SAP"""
        try:
            logger.info(f"🔌 Connecting to SAP...")
            self.conn = pyrfc.Connection(**self.params)
            logger.info("✅ SAP Connected")
            return True
        except Exception as e:
            logger.error(f"❌ SAP Connection failed: {e}")
            return False

    def disconnect(self):
        """Disconnect from SAP"""
        if self.conn:
            try:
                self.conn.close()
                logger.info("🔌 SAP Disconnected")
            except:
                pass

    def get_reservation_data(self, plant: str, pro_numbers: list):
        """Get reservation data from SAP - Loop per PRO number"""
        all_data = []

        try:
            if not self.conn and not self.connect():
                return None

            # Loop untuk setiap PRO number
            for pro_number in pro_numbers:
                try:
                    logger.info(f"📡 Calling RFC Z_FM_YMMF005 for PRO: {pro_number}")

                    result = self.conn.call(
                        'Z_FM_YMMF005',
                        P_WERKS=plant,
                        P_AUFNR=pro_number
                    )

                    # Check for data
                    if 'T_DATA1' in result:
                        data = result['T_DATA1']
                        if isinstance(data, list) and data:
                            logger.info(f"✅ Got {len(data)} records for PRO {pro_number}")

                            # **DEBUG: Log struktur data pertama untuk melihat field yang tersedia**
                            if data:
                                first_record = data[0]
                                logger.info(f"📊 Struktur data SAP untuk debugging:")
                                for key, value in first_record.items():
                                    logger.info(f"  {key}: {value} (type: {type(value)})")

                            # Tambahkan PRO number ke setiap record
                            for item in data:
                                item['PRO_NUMBER'] = pro_number
                                all_data.append(item)
                        else:
                            logger.warning(f"⚠️  T_DATA1 is empty or not a list for PRO {pro_number}")
                    else:
                        logger.warning(f"⚠️  No data in response for PRO {pro_number}")

                except Exception as e:
                    logger.error(f"❌ Error getting data for PRO {pro_number}: {e}")
                    continue

            logger.info(f"📊 Total records from all PROs: {len(all_data)}")
            return all_data

        except Exception as e:
            logger.error(f"❌ Error in get_reservation_data: {e}")
            return None

    def get_stock_data(self, plant: str, matnr: str):
        """Get stock data from SAP using RFC Z_FM_YMMR006NX"""
        try:
            if not self.conn and not self.connect():
                return None

            logger.info(f"📡 Calling RFC Z_FM_YMMR006NX for Plant: {plant}, Material: {matnr}")

            result = self.conn.call(
                'Z_FM_YMMR006NX',
                P_WERKS=plant,
                P_MATNR=matnr
            )

            # Check for data in T_DATA
            if 'T_DATA' in result:
                data = result['T_DATA']
                if isinstance(data, list) and data:
                    logger.info(f"✅ Got {len(data)} stock records for material {matnr}")

                    # Debug: log field yang tersedia
                    if data:
                        first_record = data[0]
                        logger.info(f"📊 Struktur data stock untuk debugging:")
                        for key, value in first_record.items():
                            logger.info(f"  {key}: {value} (type: {type(value)})")

                    return data
                else:
                    logger.warning(f"⚠️  T_DATA is empty or not a list for material {matnr}")
                    return []
            else:
                logger.warning(f"⚠️  No data in response for material {matnr}")
                return []

        except Exception as e:
            logger.error(f"❌ Error getting stock data: {e}")
            logger.error(traceback.format_exc())
            return None

class MySQLHandler:
    def __init__(self):
        self.config = {
            'host': os.getenv('DB_HOST', 'localhost'),
            'port': int(os.getenv('DB_PORT', 3306)),
            'database': os.getenv('DB_DATABASE', 'warehouse_reservation'),
            'user': os.getenv('DB_USERNAME', 'root'),
            'password': os.getenv('DB_PASSWORD', ''),
            'charset': 'utf8mb4'
        }

    def get_connection(self):
        """Get MySQL connection"""
        try:
            conn = mysql.connector.connect(**self.config)
            return conn
        except Error as e:
            logger.error(f"❌ MySQL Connection error: {e}")
            return None

    def save_reservation_data(self, plant: str, pro_numbers: list, sap_data: list, user_id: int = None):
        """Save SAP data to MySQL dengan SEMUA field dari T_DATA1"""
        if not sap_data:
            logger.warning("⚠️  No data to save")
            return 0

        conn = self.get_connection()
        if not conn:
            logger.error("❌ Cannot connect to MySQL")
            return 0

        cursor = conn.cursor()
        saved_count = 0

        try:
            # Get table structure
            cursor.execute("SHOW COLUMNS FROM sap_reservations")
            columns = [row[0] for row in cursor.fetchall()]
            logger.info(f"📊 Table columns: {columns}")

            # Log untuk debugging - tampilkan semua field yang ada di data SAP
            if sap_data:
                first_item = sap_data[0]
                logger.info(f"🔍 Field yang tersedia dari SAP T_DATA1:")
                sap_fields = list(first_item.keys())
                for i, field in enumerate(sorted(sap_fields), 1):
                    logger.info(f"  {i:2d}. {field}: {first_item.get(field)}")

            for item in sap_data:
                try:
                    # **PERBAIKAN: Gunakan PSMNG untuk quantity**
                    quantity = 0
                    # Coba ambil dari PSMNG dulu, jika tidak ada coba PSMHD
                    if 'PSMNG' in item and item['PSMNG']:
                        quantity = float(item['PSMNG'])
                    elif 'PSMHD' in item and item['PSMHD']:
                        quantity = float(item['PSMHD'])
                    else:
                        quantity = 0

                    # Prepare base data dengan SEMUA field dari SAP
                    base_data = {
                        'rsnum': item.get('RSNUM', ''),
                        'rspos': item.get('RSPOS', ''),
                        'sap_plant': plant,
                        'sap_order': item.get('PRO_NUMBER', ''),
                        'aufnr': item.get('AUFNR', ''),
                        'matnr': item.get('MATNR', ''),
                        'maktx': item.get('MAKTX', ''),
                        'psmng': quantity,
                        'meins': item.get('MEINS', 'ST'),
                        'gstrp': self._parse_sap_date(item.get('GSTRP')),
                        'gltrp': self._parse_sap_date(item.get('GLTRP')),
                        'makhd': item.get('MAKHD', ''),
                        'mtart': item.get('MTART', ''),
                        'sortf': item.get('SORTF', ''),
                        'dwerk': item.get('DWERK', plant),
                        'sync_by': user_id,
                        'sync_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                        'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                        'updated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                    }

                    # **TAMBAHAN: Field-field yang sebelumnya NULL di database**
                    additional_fields = {
                        'nampl': item.get('NAMPL', ''),
                        'lgort': item.get('LGORT', ''),
                        'namsl': item.get('NAMSL', ''),
                        'dispo': item.get('DISPO', ''),
                        'kdauf': item.get('KDAUF', ''),
                        'kdpos': item.get('KDPOS', ''),
                        'mathd': item.get('MATHD', ''),
                        'dispc': item.get('DISPC', ''),
                        'groes': item.get('GROES', ''),
                        'ferth': item.get('FERTH', ''),
                        'zeinr': item.get('ZEINR', ''),
                        'matkl': item.get('MATKL', ''),
                        'naslr': item.get('NASLR', ''),
                        'ulgo': item.get('ULGO', ''),
                        'bdter': self._parse_sap_date(item.get('BDTER', '')),
                        'plnum': item.get('PLNUM', ''),
                        'plnbez': item.get('PLNBEZ', ''),
                        'pltxt': item.get('PLTXT', ''),
                        'bsmng': float(item.get('BSMNG', 0)) if item.get('BSMNG') else 0,
                        'bmein': item.get('BMEIN', ''),
                        'enmng': float(item.get('ENMNG', 0)) if item.get('ENMNG') else 0,
                        'einhe': item.get('EINHE', ''),
                        'umren': float(item.get('UMREN', 0)) if item.get('UMREN') else 0,
                        'umrez': float(item.get('UMREZ', 0)) if item.get('UMREZ') else 0,
                        'aufpl': item.get('AUFPL', ''),
                        'aplzl': item.get('APLZL', ''),
                        'rwdat': self._parse_sap_date(item.get('RWDAT', '')),
                        'verid': item.get('VERID', ''),
                        'cuobj': item.get('CUOBJ', ''),
                        'cucfg': item.get('CUCFG', ''),
                        'charg': item.get('CHARG', ''),
                        'sobkz': item.get('SOBKZ', ''),
                        'kzbws': item.get('KZBWS', ''),
                        'kzear': item.get('KZEAR', ''),
                        'umlgo': item.get('UMLGO', ''),
                        'wempf': item.get('WEMPF', ''),
                        'ablad': item.get('ABLAD', ''),
                        'hsdat': self._parse_sap_date(item.get('HSDAT', '')),
                        'vsdat': self._parse_sap_date(item.get('VSDAT', '')),
                        'ssdat': self._parse_sap_date(item.get('SSDAT', '')),
                        'weanz': float(item.get('WEANZ', 0)) if item.get('WEANZ') else 0,
                        'webez': item.get('WEBEZ', ''),
                        'kzvbr': item.get('KZVBR', ''),
                        'kzstr': item.get('KZSTR', ''),
                        'kzrvb': item.get('KZRVB', ''),
                        'xloek': item.get('XLOEK', ''),
                        'prvbe': item.get('PRVBE', ''),
                        'bedae': item.get('BEDAE', ''),
                        'sanka': item.get('SANKA', ''),
                        'kzdis': item.get('KZDIS', ''),
                        'profl': item.get('PROFL', ''),
                        'kzsti': item.get('KZSTI', ''),
                        'kzkri': item.get('KZKRI', ''),
                        'verss': item.get('VERSS', ''),
                        'kalst': float(item.get('KALST', 0)) if item.get('KALST') else 0,
                        'rvrel': item.get('RVREL', ''),
                        'bdmng': float(item.get('BDMNG', 0)) if item.get('BDMNG') else 0,
                        'schgt': item.get('SCHGT', ''),
                        'kzpps': item.get('KZPPS', ''),
                        'pspnr': item.get('PSPNR', ''),
                        'aufps': item.get('AUFPS', ''),
                        'pamng': float(item.get('PAMNG', 0)) if item.get('PAMNG') else 0,
                        'prreg': item.get('PRREG', ''),
                        'fevor': item.get('FEVOR', ''),
                        'kaufk': item.get('KAUFK', ''),
                        'kunnr': item.get('KUNNR', ''),
                        'ktext': item.get('KTEXT', ''),
                        'vptnr': item.get('VPTNR', ''),
                        'vorna': item.get('VORNA', ''),
                        'name1': item.get('NAME1', ''),
                        'name2': item.get('NAME2', ''),
                        'ort01': item.get('ORT01', ''),
                        'pstlz': item.get('PSTLZ', ''),
                        'land1': item.get('LAND1', ''),
                        'stras': item.get('STRAS', ''),
                        'tel_number': item.get('TEL_NUMBER', ''),
                    }

                    # Gabungkan base_data dengan additional_fields
                    base_data.update(additional_fields)

                    # **PERBAIKAN: Filter hanya kolom yang ada di tabel**
                    data_to_insert = {}
                    for col in columns:
                        if col in base_data and base_data[col] is not None:
                            data_to_insert[col] = base_data[col]

                    # Validasi data wajib
                    if not data_to_insert.get('rsnum') or not data_to_insert.get('matnr'):
                        logger.warning(f"⚠️  Skipping record missing rsnum or matnr")
                        continue

                    # Debug: Tampilkan data yang akan diinsert
                    if saved_count == 0:
                        logger.info(f"🔍 Data pertama yang akan diinsert:")
                        for key, value in data_to_insert.items():
                            logger.info(f"  {key}: {value}")

                    # Build SQL
                    cols = list(data_to_insert.keys())
                    values = list(data_to_insert.values())

                    if not cols:
                        continue

                    placeholders = ['%s'] * len(cols)

                    sql = f"""
                        INSERT INTO sap_reservations ({', '.join(cols)})
                        VALUES ({', '.join(placeholders)})
                        ON DUPLICATE KEY UPDATE
                            {', '.join([f"{col} = VALUES({col})" for col in cols if col not in ['rsnum', 'rspos', 'matnr', 'sap_plant']])},
                            updated_at = VALUES(updated_at),
                            sync_at = VALUES(sync_at),
                            sync_by = VALUES(sync_by)
                    """

                    cursor.execute(sql, values)
                    saved_count += 1

                    if saved_count % 100 == 0:
                        logger.info(f"📝 Processed {saved_count} records...")

                except Exception as e:
                    logger.error(f"❌ Error processing record: {e}")
                    logger.error(f"Record data keys: {list(item.keys())}")
                    logger.error(f"Error details: {traceback.format_exc()}")
                    continue

            conn.commit()
            logger.info(f"✅ Saved {saved_count} records from {len(sap_data)} SAP records")

        except Exception as e:
            logger.error(f"❌ Save error: {e}")
            logger.error(f"Error traceback: {traceback.format_exc()}")
            conn.rollback()
        finally:
            cursor.close()
            conn.close()

        return saved_count

    def _parse_sap_date(self, date_value):
        """Parse SAP date (YYYYMMDD) to MySQL date"""
        if not date_value:
            return None
        try:
            date_str = str(date_value)
            if len(date_str) == 8 and date_str.isdigit():
                return f"{date_str[:4]}-{date_str[4:6]}-{date_str[6:8]}"
            elif len(date_str) == 10 and date_str[4] == '-' and date_str[7] == '-':
                return date_str
        except Exception as e:
            logger.warning(f"⚠️  Failed to parse date {date_value}: {e}")
        return None

# Initialize
sap = SAPConnector()
db = MySQLHandler()

@app.route('/api/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'healthy',
        'service': 'SAP Sync',
        'timestamp': datetime.now().isoformat(),
        'version': '1.4.0',
        'features': ['all_sap_fields', 'enhanced_logging', 'complete_data_mapping', 'stock_inquiry']
    })

@app.route('/api/sap/sync', methods=['POST'])
def sync_reservations():
    start_time = time.time()

    try:
        data = request.get_json()
        plant = data.get('plant')
        pro_numbers = data.get('pro_numbers', [])
        user_id = data.get('user_id')

        logger.info(f"🔄 Starting sync process", {
            'plant': plant,
            'pro_numbers': pro_numbers,
            'pro_count': len(pro_numbers),
            'user_id': user_id
        })

        if not plant or not pro_numbers:
            return jsonify({
                'success': False,
                'message': 'Plant and PRO numbers are required'
            }), 400

        # Get data from SAP
        sap_data = sap.get_reservation_data(plant, pro_numbers)

        if sap_data is None:
            return jsonify({
                'success': False,
                'message': 'Failed to get data from SAP'
            }), 500

        if not sap_data:
            return jsonify({
                'success': True,
                'message': 'No data found in SAP for the given PRO numbers',
                'synced_count': 0,
                'records_from_sap': 0,
                'processing_time': round(time.time() - start_time, 2)
            })

        # Save to MySQL
        saved_count = db.save_reservation_data(plant, pro_numbers, sap_data, user_id)

        # Disconnect SAP
        sap.disconnect()

        processing_time = round(time.time() - start_time, 2)

        logger.info(f"✅ Sync completed", {
            'plant': plant,
            'pro_count': len(pro_numbers),
            'sap_records': len(sap_data),
            'saved_count': saved_count,
            'processing_time': processing_time
        })

        return jsonify({
            'success': True,
            'message': f'Sync completed. {saved_count} records saved from {len(pro_numbers)} PRO numbers.',
            'synced_count': saved_count,
            'total_pros': len(pro_numbers),
            'records_from_sap': len(sap_data),
            'processing_time': processing_time,
            'data': sap_data
        })

    except Exception as e:
        logger.error(f"🔥 Sync error: {e}")
        logger.error(traceback.format_exc())

        return jsonify({
            'success': False,
            'message': f'Sync failed: {str(e)}',
            'error_details': str(e)
        }), 500

@app.route('/api/sap/stock', methods=['POST'])
def get_stock():
    """Get stock data from SAP using RFC Z_FM_YMMR006NX"""
    start_time = time.time()

    try:
        data = request.get_json()
        plant = data.get('plant')
        matnr = data.get('matnr')

        if not plant or not matnr:
            return jsonify({
                'success': False,
                'message': 'Plant and Material are required'
            }), 400

        logger.info(f"📦 Getting stock data for Plant: {plant}, Material: {matnr}")

        # Get stock data from SAP
        stock_data = sap.get_stock_data(plant, matnr)

        if stock_data is None:
            return jsonify({
                'success': False,
                'message': 'Failed to get stock data from SAP'
            }), 500

        processing_time = round(time.time() - start_time, 2)

        # Format data untuk konsistensi
        formatted_data = []
        for item in stock_data:
            formatted_item = {
                'MATNR': item.get('MATNR', ''),
                'MTBEZ': item.get('MTBEZ', ''),
                'MAKTX': item.get('MAKTX', ''),
                'WERK': item.get('WERK', ''),
                'LGORT': item.get('LGORT', ''),
                'CHARG': item.get('CHARG', ''),
                'CLABS': float(item.get('CLABS', 0)) if item.get('CLABS') else 0,
                'MEINS': item.get('MEINS', ''),
                'VBELN': item.get('VBELN', ''),
                'POSNR': item.get('POSNR', ''),
                'LABST': float(item.get('LABST', 0)) if item.get('LABST') else 0,
                'UMLMC': float(item.get('UMLMC', 0)) if item.get('UMLMC') else 0,
                'INSME': float(item.get('INSME', 0)) if item.get('INSME') else 0,
                'SPEME': float(item.get('SPEME', 0)) if item.get('SPEME') else 0,
                'EINME': float(item.get('EINME', 0)) if item.get('EINME') else 0,
                'RETME': float(item.get('RETME', 0)) if item.get('RETME') else 0,
                'HERBL': item.get('HERBL', ''),
                'HERKL': item.get('HERKL', ''),
                'SOBKZ': item.get('SOBKZ', ''),
                'KUNNR': item.get('KUNNR', ''),
                'PSPNR': item.get('PSPNR', ''),
                'KDAUF': item.get('KDAUF', ''),
                'KDPOS': item.get('KDPOS', ''),
                'SHKZG': item.get('SHKZG', ''),
                'WAERS': item.get('WAERS', ''),
                'DMBTR': float(item.get('DMBTR', 0)) if item.get('DMBTR') else 0,
            }
            formatted_data.append(formatted_item)

        logger.info(f"✅ Stock data retrieved: {len(formatted_data)} records")

        return jsonify({
            'success': True,
            'message': f'Stock data retrieved successfully',
            'plant': plant,
            'material': matnr,
            'record_count': len(formatted_data),
            'processing_time': processing_time,
            'data': formatted_data
        })

    except Exception as e:
        logger.error(f"🔥 Stock data error: {e}")
        logger.error(traceback.format_exc())

        return jsonify({
            'success': False,
            'message': f'Failed to get stock data: {str(e)}'
        }), 500

@app.route('/api/sap/stock/batch', methods=['POST'])
def get_stock_batch():
    """Get stock data for multiple materials"""
    start_time = time.time()

    try:
        data = request.get_json()
        plant = data.get('plant')
        materials = data.get('materials', [])
        user_id = data.get('user_id')

        if not plant or not materials:
            return jsonify({
                'success': False,
                'message': 'Plant and Materials are required'
            }), 400

        logger.info(f"📦 Getting stock data for {len(materials)} materials in Plant: {plant}")

        all_stock_data = []
        errors = []

        for i, matnr in enumerate(materials):
            try:
                logger.info(f"Processing material {i+1}/{len(materials)}: {matnr}")

                stock_data = sap.get_stock_data(plant, matnr)

                if stock_data:
                    # Tambahkan material info ke setiap record
                    for item in stock_data:
                        item['REQUESTED_MATNR'] = matnr
                    all_stock_data.extend(stock_data)

                    logger.info(f"Got {len(stock_data)} stock records for material {matnr}")
                else:
                    logger.warning(f"No stock data for material {matnr}")
                    errors.append(f"No stock data for material {matnr}")

                # Delay kecil untuk menghindari overload SAP
                if i < len(materials) - 1:
                    time.sleep(0.1)

            except Exception as e:
                error_msg = f"Error processing material {matnr}: {str(e)}"
                logger.error(error_msg)
                errors.append(error_msg)
                continue

        processing_time = round(time.time() - start_time, 2)

        return jsonify({
            'success': True,
            'message': f'Stock data retrieved for {len(materials)} materials',
            'plant': plant,
            'total_materials': len(materials),
            'total_records': len(all_stock_data),
            'processing_time': processing_time,
            'errors': errors,
            'data': all_stock_data
        })

    except Exception as e:
        logger.error(f"🔥 Batch stock data error: {e}")
        logger.error(traceback.format_exc())

        return jsonify({
            'success': False,
            'message': f'Failed to get batch stock data: {str(e)}'
        }), 500

# Endpoint untuk debugging struktur data SAP
@app.route('/api/sap/debug/structure', methods=['POST'])
def debug_sap_structure():
    try:
        data = request.get_json()
        plant = data.get('plant', '3000')
        pro_numbers = data.get('pro_numbers', [''])

        # Ambil satu record untuk debugging
        sap_data = sap.get_reservation_data(plant, pro_numbers[:1])

        if sap_data and len(sap_data) > 0:
            first_record = sap_data[0]
            return jsonify({
                'success': True,
                'record_count': len(sap_data),
                'fields': list(first_record.keys()),
                'sample_record': first_record
            })
        else:
            return jsonify({
                'success': False,
                'message': 'No data returned from SAP'
            }), 404

    except Exception as e:
        return jsonify({
            'success': False,
            'message': str(e)
        }), 500

if __name__ == '__main__':
    logger.info("🚀 Starting SAP Sync Service v1.4.0")
    logger.info("✨ Features: Complete SAP field mapping, enhanced logging, stock inquiry")
    app.run(host='0.0.0.0', port=5000, debug=True)
