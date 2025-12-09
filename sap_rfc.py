# sap_rfc.py - FIXED VERSION dengan semua field dari SAP
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
                    # Field utama yang sudah ada
                    base_data = {
                        'rsnum': item.get('RSNUM', ''),
                        'rspos': item.get('RSPOS', ''),
                        'sap_plant': plant,
                        'sap_order': item.get('PRO_NUMBER', ''),  # Gunakan PRO number dari loop
                        'aufnr': item.get('AUFNR', ''),
                        'matnr': item.get('MATNR', ''),
                        'maktx': item.get('MAKTX', ''),
                        'psmng': quantity,
                        'meins': item.get('MEINS', 'ST'),
                        'gstrp': self._parse_sap_date(item.get('GSTRP')),
                        'gltrp': self._parse_sap_date(item.get('GLTRP')),
                        'makhd': item.get('MAKHD', ''),  # Kolom Finish Good
                        'mtart': item.get('MTART', ''),
                        'sortf': item.get('SORTF', ''),
                        'dwerk': item.get('DWERK', plant),
                        'sync_by': user_id,
                        'sync_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                        'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                        'updated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                    }

                    # **TAMBAHAN: Field-field yang sebelumnya NULL di database**
                    # Field dari T_DATA1 yang perlu ditambahkan
                    additional_fields = {
                        # Field dari gambar yang NULL
                        'nampl': item.get('NAMPL', ''),      # Nama Plant
                        'lgort': item.get('LGORT', ''),      # Storage Location
                        'namsl': item.get('NAMSL', ''),      # Nama Storage Location
                        'dispo': item.get('DISPO', ''),      # MRP Controller
                        'kdauf': item.get('KDAUF', ''),      # Order Number (Reference)
                        'kdpos': item.get('KDPOS', ''),      # Item Number (Reference)
                        'mathd': item.get('MATHD', ''),      # Material Handling
                        'dispc': item.get('DISPC', ''),      # Production Supervisor
                        'groes': item.get('GROES', ''),      # Size/Dimensions
                        'ferth': item.get('FERTH', ''),      # Production Finish Time
                        'zeinr': item.get('ZEINR', ''),      # Requirement Tracking Number
                        'matkl': item.get('MATKL', ''),      # Material Group
                        'naslr': item.get('NASLR', ''),      # Name of Requirement Segment
                        'ulgo': item.get('ULGO', ''),       # Storage Type

                        # Field lain yang mungkin ada di T_DATA1
                        'bdter': self._parse_sap_date(item.get('BDTER', '')),  # Requirement Date
                        'plnum': item.get('PLNUM', ''),      # Planned Order Number
                        'plnbez': item.get('PLNBEZ', ''),    # Planned Order Description
                        'pltxt': item.get('PLTXT', ''),      # Planned Order Long Text
                        'bsmng': float(item.get('BSMNG', 0)) if item.get('BSMNG') else 0,  # Base Quantity
                        'bmein': item.get('BMEIN', ''),      # Base Unit of Measure
                        'enmng': float(item.get('ENMNG', 0)) if item.get('ENMNG') else 0,  # Withdrawal Quantity
                        'einhe': item.get('EINHE', ''),      # Withdrawal Unit
                        'umren': float(item.get('UMREN', 0)) if item.get('UMREN') else 0,  # Numerator for Conversion
                        'umrez': float(item.get('UMREZ', 0)) if item.get('UMREZ') else 0,  # Denominator for Conversion
                        'aufpl': item.get('AUFPL', ''),      # Routing Number of Operations
                        'aplzl': item.get('APLZL', ''),      # Counter for Operations
                        'rwdat': self._parse_sap_date(item.get('RWDAT', '')),  # Goods Receipt Date
                        'verid': item.get('VERID', ''),      # Production Version
                        'cuobj': item.get('CUOBJ', ''),      # Configuration Object
                        'cucfg': item.get('CUCFG', ''),      # Configuration
                        'charg': item.get('CHARG', ''),      # Batch Number
                        'sobkz': item.get('SOBKZ', ''),      # Special Stock Indicator
                        'kzbws': item.get('KZBWS', ''),      # Individual/Colllective Requirements
                        'kzear': item.get('KZEAR', ''),      # Final Issue
                        'umlgo': item.get('UMLGO', ''),      # Storage Type for Withdrawal
                        'wempf': item.get('WEMPF', ''),      # Goods Recipient
                        'ablad': item.get('ABLAD', ''),      # Unloading Point
                        'hsdat': self._parse_sap_date(item.get('HSDAT', '')),  # Shelf Life Expiration Date
                        'vsdat': self._parse_sap_date(item.get('VSDAT', '')),  # Production Start Date
                        'ssdat': self._parse_sap_date(item.get('SSDAT', '')),  # System Status Date
                        'weanz': float(item.get('WEANZ', 0)) if item.get('WEANZ') else 0,  # Number of GR Slips
                        'webez': item.get('WEBEZ', ''),      # Goods Recipient's Name
                        'kzvbr': item.get('KZVBR', ''),      # Consumption Posting
                        'kzstr': item.get('KZSTR', ''),      # Structure Scope
                        'kzrvb': item.get('KZRVB', ''),      # Reserv./Dependent Requirements
                        'xloek': item.get('XLOEK', ''),      # Deletion Indicator
                        'prvbe': item.get('PRVBE', ''),      # Supply Area
                        'bedae': item.get('BEDAE', ''),      # Requirements Type
                        'sanka': item.get('SANKA', ''),      # Indicator: Relevant for MRP
                        'kzdis': item.get('KZDIS', ''),      # MRP Element: Indicator
                        'profl': item.get('PROFL', ''),      # LP Relevant
                        'kzsti': item.get('KZSTI', ''),      # BOM Explosion Number
                        'kzkri': item.get('KZKRI', ''),      # Critical Part
                        'verss': item.get('VERSS', ''),      # Production Supersession
                        'kalst': float(item.get('KALST', 0)) if item.get('KALST') else 0,  # BOM Level
                        'rvrel': item.get('RVREL', ''),      # Rel. Requirement (Backflush)
                        'bdmng': float(item.get('BDMNG', 0)) if item.get('BDMNG') else 0,  # Requirement Quantity
                        'schgt': item.get('SCHGT', ''),      # Bulk Material
                        'kzpps': item.get('KZPPS', ''),      # Phantoms
                        'pspnr': item.get('PSPNR', ''),      # WBS Element
                        'aufps': item.get('AUFPS', ''),      # Order Item Number
                        'pamng': float(item.get('PAMNG', 0)) if item.get('PAMNG') else 0,  # Planned Order Quantity
                        'prreg': item.get('PRREG', ''),      # Priority Regulation
                        'fevor': item.get('FEVOR', ''),      # Production Supervisor
                        'kaufk': item.get('KAUFK', ''),      # Customer
                        'kunnr': item.get('KUNNR', ''),      # Customer Number
                        'ktext': item.get('KTEXT', ''),      # Description
                        'vptnr': item.get('VPTNR', ''),      # Partner Account Number
                        'vorna': item.get('VORNA', ''),      # First Name
                        'name1': item.get('NAME1', ''),      # Name 1
                        'name2': item.get('NAME2', ''),      # Name 2
                        'ort01': item.get('ORT01', ''),      # City
                        'pstlz': item.get('PSTLZ', ''),      # Postal Code
                        'land1': item.get('LAND1', ''),      # Country
                        'stras': item.get('STRAS', ''),      # Street
                        'tel_number': item.get('TEL_NUMBER', ''),  # Telephone Number
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
                    if saved_count == 0:  # Hanya untuk record pertama
                        logger.info(f"🔍 Data pertama yang akan diinsert:")
                        for key, value in data_to_insert.items():
                            logger.info(f"  {key}: {value}")

                    # Build SQL - hanya kolom yang ada di data_to_insert
                    cols = list(data_to_insert.keys())
                    values = list(data_to_insert.values())

                    if not cols:  # Jika tidak ada kolom, skip
                        continue

                    placeholders = ['%s'] * len(cols)

                    # **PERBAIKAN: Simple INSERT dengan ON DUPLICATE KEY UPDATE**
                    # Gunakan kombinasi unik: rsnum + rspos + matnr + sap_plant
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

                    # Log setiap 100 records
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
            # Coba format lain jika tidak 8 digit
            elif len(date_str) == 10 and date_str[4] == '-' and date_str[7] == '-':
                # Sudah dalam format YYYY-MM-DD
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
        'version': '1.3.0',
        'features': ['all_sap_fields', 'enhanced_logging', 'complete_data_mapping']
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
            'data': sap_data  # Kirim data ke Laravel
        })

    except Exception as e:
        logger.error(f"🔥 Sync error: {e}")
        logger.error(traceback.format_exc())

        return jsonify({
            'success': False,
            'message': f'Sync failed: {str(e)}',
            'error_details': str(e)
        }), 500

# Endpoint untuk debugging struktur data SAP
@app.route('/api/sap/debug/structure', methods=['POST'])
def debug_sap_structure():
    try:
        data = request.get_json()
        plant = data.get('plant', '3000')
        pro_numbers = data.get('pro_numbers', [''])

        # Ambil satu record untuk debugging
        sap_data = sap.get_reservation_data(plant, pro_numbers[:1])  # Ambil hanya PRO pertama

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
    logger.info("🚀 Starting SAP Sync Service v1.3.0")
    logger.info("✨ Features: Complete SAP field mapping, enhanced logging")
    app.run(host='0.0.0.0', port=5000, debug=True)
