# sap_rfc.py - FIXED VERSION tanpa sync_by_name
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
                        if isinstance(data, list):
                            logger.info(f"✅ Got {len(data)} records for PRO {pro_number}")

                            # Tambahkan PRO number ke setiap record
                            for item in data:
                                item['PRO_NUMBER'] = pro_number
                                all_data.append(item)
                        else:
                            logger.warning(f"⚠️  T_DATA1 is not a list for PRO {pro_number}")
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
        """Save SAP data to MySQL"""
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

                    # Prepare base data
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

                    # **PERBAIKAN: Filter hanya kolom yang ada di tabel**
                    data_to_insert = {}
                    for col in columns:
                        if col in base_data and base_data[col] is not None:
                            data_to_insert[col] = base_data[col]

                    # Validasi data wajib
                    if not data_to_insert.get('rsnum') or not data_to_insert.get('matnr'):
                        logger.warning(f"⚠️  Skipping record missing rsnum or matnr")
                        continue

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

                except Exception as e:
                    logger.error(f"❌ Error processing record: {e}")
                    logger.error(f"Record data: {item}")
                    continue

            conn.commit()
            logger.info(f"✅ Saved {saved_count} records from {len(sap_data)} SAP records")

        except Exception as e:
            logger.error(f"❌ Save error: {e}")
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
        except:
            pass
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
        'version': '1.2.0'
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

if __name__ == '__main__':
    logger.info("🚀 Starting SAP Sync Service v1.2.0")
    app.run(host='0.0.0.0', port=5000, debug=True)
