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
import re

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
            logger.error(f"MySQL Connection error: {e}")
            return None

    def save_reservation_data(self, plant: str, pro_numbers: list, sap_data: list, user_id: int = None):
        """Save SAP data to MySQL dengan SEMUA field dari T_DATA1"""
        if not sap_data:
            return 0

        conn = self.get_connection()
        if not conn:
            return 0

        cursor = conn.cursor()
        saved_count = 0

        try:
            cursor.execute("SHOW COLUMNS FROM sap_reservations")
            columns = [row[0] for row in cursor.fetchall()]

            for item in sap_data:
                try:
                    quantity = 0
                    if 'PSMNG' in item and item['PSMNG']:
                        quantity = float(item['PSMNG'])
                    elif 'PSMHD' in item and item['PSMHD']:
                        quantity = float(item['PSMHD'])

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

                    base_data.update(additional_fields)

                    data_to_insert = {}
                    for col in columns:
                        if col in base_data and base_data[col] is not None:
                            data_to_insert[col] = base_data[col]

                    if not data_to_insert.get('rsnum') or not data_to_insert.get('matnr'):
                        continue

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

                except Exception as e:
                    logger.error(f"Error processing record: {e}")
                    continue

            conn.commit()
            return saved_count

        except Exception as e:
            logger.error(f"Save error: {e}")
            conn.rollback()
            return 0
        finally:
            cursor.close()
            conn.close()

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
            return None

    def check_transfer_exists(self, document_no, plant_supply):
        """Cek apakah transfer sudah ada"""
        conn = self.get_connection()
        if not conn:
            return None

        cursor = conn.cursor()

        try:
            sql = """
                SELECT id, status, transfer_no
                FROM reservation_transfers
                WHERE document_no = %s AND plant_supply = %s
                LIMIT 1
            """

            cursor.execute(sql, (document_no, plant_supply))
            result = cursor.fetchone()

            if result:
                return {
                    'id': result[0],
                    'status': result[1],
                    'transfer_no': result[2]
                }
            return None

        except Exception as e:
            logger.error(f"Error checking transfer exists: {e}")
            return None
        finally:
            cursor.close()
            conn.close()

    def save_transfer_to_db(self, transfer_data, sap_response, item_results, user_id=None, user_name=None):
        """Save transfer data to MySQL reservation_transfers table"""
        conn = self.get_connection()
        if not conn:
            return None

        cursor = conn.cursor()

        try:
            # Extract data from transfer_data
            transfer_info = transfer_data.get('transfer_info', {})
            items = transfer_data.get('items', [])

            # Ambil plant_supply dari transfer_info dengan fallback yang aman
            plant_supply = transfer_info.get('plant_supply', '')

            # Jika tidak ada di transfer_info, coba dari items pertama
            if not plant_supply and items:
                plant_supply = items[0].get('plant_supply', '') if items else ''

            # Get material document from SAP response
            material_doc = None

            # Check multiple possible field names for material document
            if sap_response and isinstance(sap_response, dict):
                material_doc = (
                    sap_response.get('MAT_DOC') or
                    sap_response.get('MATDOC') or
                    sap_response.get('MATERIALDOC') or
                    sap_response.get('EV_MATERIAL_DOC')
                )

            # If still not found, check in RETURN messages
            if not material_doc and sap_response and 'RETURN' in sap_response:
                for msg in sap_response['RETURN']:
                    if msg.get('MESSAGE', '').find('Material document') != -1:
                        # Try to extract material document number from message
                        match = re.search(r'\d+', msg.get('MESSAGE', ''))
                        if match:
                            material_doc = match.group()
                            break

            # Calculate totals
            total_items = len([item for item in items if item.get('material_code')])
            total_quantity = sum(float(item.get('quantity', 0)) for item in items if item.get('quantity'))

            # Check jika document_id valid, jika tidak gunakan NULL
            document_id = transfer_info.get('document_id')

            # Cek apakah document_id valid dengan mencoba mencari di reservation_documents
            valid_document_id = None
            if document_id:
                try:
                    # Cek apakah document_id ada di reservation_documents
                    cursor.execute("SELECT id FROM reservation_documents WHERE id = %s", (document_id,))
                    if cursor.fetchone():
                        valid_document_id = document_id
                except Exception as e:
                    logger.warning(f"Error checking document_id {document_id}: {e}")

            # Check for errors in SAP response to determine status
            has_errors = False
            if sap_response and 'RETURN' in sap_response:
                for msg in sap_response['RETURN']:
                    msg_type = msg.get('TYPE', '')
                    if msg_type in ['E', 'A', 'X']:  # Error, Abort, Exception
                        has_errors = True
                        break

            # Determine status
            if has_errors:
                status = 'FAILED'
            elif material_doc:
                status = 'COMPLETED'
            else:
                status = 'SUBMITTED'

            # Siapkan data untuk INSERT
            insert_data = {
                'plant': plant_supply,
                'document_no': transfer_info.get('document_no', 'TRF-' + datetime.now().strftime('%Y%m%d-%H%M%S')),
                'transfer_no': material_doc,
                'plant_supply': plant_supply,
                'plant_destination': transfer_info.get('plant_destination', ''),
                'move_type': transfer_info.get('move_type', '311'),
                'total_items': total_items,
                'total_quantity': total_quantity,
                'status': status,
                'sap_message': json.dumps(sap_response.get('RETURN', []), ensure_ascii=False, default=str) if sap_response else '',
                'remarks': transfer_info.get('remarks', ''),
                'created_by': user_id if user_id else 0,
                'created_by_name': user_name if user_name else 'SYSTEM',
                'completed_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S') if material_doc else None,
                'sap_response': json.dumps(sap_response, ensure_ascii=False, default=str) if sap_response else None,
                'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                'updated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }

            # Tambahkan document_id hanya jika valid
            if valid_document_id:
                insert_data['document_id'] = valid_document_id

            # Check struktur tabel untuk menghindari error kolom tidak ada
            try:
                cursor.execute("SHOW COLUMNS FROM reservation_transfers")
                columns_info = cursor.fetchall()
                table_columns = [col[0] for col in columns_info]

                # Filter hanya kolom yang ada di tabel
                filtered_data = {}
                for key, value in insert_data.items():
                    if key in table_columns:
                        filtered_data[key] = value

                columns = list(filtered_data.keys())
                values = list(filtered_data.values())

                if not columns:
                    logger.error("No valid columns to insert")
                    return None

                placeholders = ['%s'] * len(columns)

                sql = f"""
                    INSERT INTO reservation_transfers ({', '.join(columns)})
                    VALUES ({', '.join(placeholders)})
                """

                logger.info(f"Executing SQL with columns: {columns}")
                cursor.execute(sql, values)
                conn.commit()

                # Get the inserted ID
                transfer_id = cursor.lastrowid

                logger.info(f"Transfer saved to database with ID: {transfer_id}, Material Doc: {material_doc}, Status: {status}")

                # Save transfer items if table exists
                try:
                    self.save_transfer_items_to_db(transfer_id, items, item_results)
                except Exception as e:
                    logger.warning(f"Could not save transfer items: {e}")
                    # Continue even if items saving fails

                return {
                    'transfer_id': transfer_id,
                    'material_doc': material_doc,
                    'total_items': total_items,
                    'total_quantity': total_quantity,
                    'document_id_included': 'document_id' in filtered_data,
                    'status': status
                }

            except Exception as e:
                logger.error(f"Error preparing insert data: {e}")
                raise

        except mysql.connector.errors.IntegrityError as ie:
            # Handle foreign key constraint error secara spesifik
            logger.error(f"IntegrityError saving transfer: {ie}")
            conn.rollback()

            # Coba lagi tanpa document_id
            try:
                # Remove document_id from insert_data
                if 'document_id' in insert_data:
                    del insert_data['document_id']

                # Filter hanya kolom yang ada di tabel
                cursor.execute("SHOW COLUMNS FROM reservation_transfers")
                columns_info = cursor.fetchall()
                table_columns = [col[0] for col in columns_info]

                filtered_data = {}
                for key, value in insert_data.items():
                    if key in table_columns:
                        filtered_data[key] = value

                columns = list(filtered_data.keys())
                values = list(filtered_data.values())

                if columns:
                    placeholders = ['%s'] * len(columns)

                    sql = f"""
                        INSERT INTO reservation_transfers ({', '.join(columns)})
                        VALUES ({', '.join(placeholders)})
                    """

                    logger.info(f"Retrying INSERT without document_id. Columns: {columns}")
                    cursor.execute(sql, values)
                    conn.commit()

                    transfer_id = cursor.lastrowid
                    logger.info(f"Transfer saved (retry) with ID: {transfer_id}, Material Doc: {material_doc}")

                    return {
                        'transfer_id': transfer_id,
                        'material_doc': material_doc,
                        'total_items': total_items,
                        'total_quantity': total_quantity,
                        'document_id_included': False,
                        'retry_success': True,
                        'status': status
                    }
                else:
                    logger.error("No columns to insert after removing document_id")
                    return None

            except Exception as retry_error:
                logger.error(f"Retry also failed: {retry_error}")
                conn.rollback()
                return None

        except Exception as e:
            logger.error(f"Error saving transfer to database: {e}")
            logger.error(traceback.format_exc())
            conn.rollback()
            return None
        finally:
            cursor.close()
            conn.close()

    def save_transfer_items_to_db(self, transfer_id, items, item_results):
        """Save transfer items to reservation_transfer_items table (if exists)"""
        conn = self.get_connection()
        if not conn:
            return 0

        cursor = conn.cursor()
        saved_count = 0

        try:
            cursor.execute("SHOW TABLES LIKE 'reservation_transfer_items'")
            table_exists = cursor.fetchone()

            if not table_exists:
                logger.info("Table 'reservation_transfer_items' does not exist, skipping items save")
                return 0

            for idx, item in enumerate(items):
                # Find corresponding item result
                item_result = next((ir for ir in item_results if ir.get('item_number') == idx + 1), {})

                insert_data = {
                    'transfer_id': transfer_id,
                    'item_number': idx + 1,
                    'material_code': item.get('material_code', ''),
                    'material_code_raw': item.get('material_code_raw', item.get('material_code', '')),
                    'batch': item.get('batch', ''),
                    'batch_sloc': item.get('batch_sloc', ''),
                    'quantity': float(item.get('quantity', 0)) if item.get('quantity') else 0.0,
                    'unit': item.get('unit', 'PC'),
                    'unit_sap': 'ST' if item.get('unit', 'PC').upper() == 'PC' else item.get('unit', ''),
                    'plant_supply': item.get('plant_supply', ''),
                    'sloc_supply': item.get('batch_sloc', '').replace('SLOC:', ''),
                    'plant_destination': item.get('plant_tujuan', ''),
                    'sloc_destination': item.get('sloc_tujuan', ''),
                    'move_type': item_result.get('move_type', '311'),
                    'sales_ord': item.get('sales_ord', ''),
                    's_ord_item': item.get('s_ord_item', ''),
                    'sap_status': item_result.get('status', ''),
                    'sap_message': item_result.get('message', ''),
                    'material_formatted': item_result.get('material_formatted', False),
                    'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                    'updated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                }

                # Build columns and values
                columns = list(insert_data.keys())
                values = list(insert_data.values())
                placeholders = ['%s'] * len(columns)

                sql = f"""
                    INSERT INTO reservation_transfer_items ({', '.join(columns)})
                    VALUES ({', '.join(placeholders)})
                """

                cursor.execute(sql, values)
                saved_count += 1

            conn.commit()
            logger.info(f"Saved {saved_count} items for transfer ID: {transfer_id}")
            return saved_count

        except Exception as e:
            logger.error(f"Error saving transfer items: {e}")
            logger.error(traceback.format_exc())
            conn.rollback()
            return 0
        finally:
            cursor.close()
            conn.close()

    def update_transfer_status(self, transfer_id, status, material_doc=None, sap_response=None, errors=None):
        """Update status transfer setelah ada feedback dari SAP"""
        conn = self.get_connection()
        if not conn:
            return False

        cursor = conn.cursor()

        try:
            update_fields = {
                'status': status,
                'updated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }

            if material_doc:
                update_fields['transfer_no'] = material_doc
                update_fields['completed_at'] = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

            if sap_response:
                update_fields['sap_response'] = json.dumps(sap_response, ensure_ascii=False, default=str)
                if 'RETURN' in sap_response:
                    update_fields['sap_message'] = json.dumps(sap_response.get('RETURN', []), ensure_ascii=False, default=str)

            if errors:
                update_fields['error_details'] = json.dumps(errors, ensure_ascii=False, default=str)

            set_clause = ', '.join([f"{key} = %s" for key in update_fields.keys()])
            values = list(update_fields.values())
            values.append(transfer_id)

            sql = f"""
                UPDATE reservation_transfers
                SET {set_clause}
                WHERE id = %s
            """

            cursor.execute(sql, values)
            conn.commit()

            logger.info(f"Transfer {transfer_id} updated to status: {status}")
            return True

        except Exception as e:
            logger.error(f"Error updating transfer {transfer_id}: {e}")
            conn.rollback()
            return False
        finally:
            cursor.close()
            conn.close()

    def update_transfer_item_status(self, transfer_id, item_number, status, sap_status, sap_message):
        """Update status item transfer"""
        conn = self.get_connection()
        if not conn:
            return False

        cursor = conn.cursor()

        try:
            cursor.execute("SHOW TABLES LIKE 'reservation_transfer_items'")
            table_exists = cursor.fetchone()

            if not table_exists:
                return False

            sql = """
                UPDATE reservation_transfer_items
                SET sap_status = %s, sap_message = %s, status = %s, updated_at = NOW()
                WHERE transfer_id = %s AND item_number = %s
            """

            cursor.execute(sql, (sap_status, sap_message, status, transfer_id, item_number))
            conn.commit()

            return True

        except Exception as e:
            logger.error(f"Error updating transfer item: {e}")
            conn.rollback()
            return False
        finally:
            cursor.close()
            conn.close()

    def get_transfer_by_id(self, transfer_id):
        """Get transfer data by ID"""
        conn = self.get_connection()
        if not conn:
            return None

        cursor = conn.cursor(dictionary=True)

        try:
            sql = """
                SELECT * FROM reservation_transfers
                WHERE id = %s
            """

            cursor.execute(sql, (transfer_id,))
            transfer = cursor.fetchone()

            if transfer:
                # Get items if exists
                try:
                    cursor.execute("SHOW TABLES LIKE 'reservation_transfer_items'")
                    table_exists = cursor.fetchone()

                    if table_exists:
                        sql_items = """
                            SELECT * FROM reservation_transfer_items
                            WHERE transfer_id = %s
                            ORDER BY item_number
                        """
                        cursor.execute(sql_items, (transfer_id,))
                        items = cursor.fetchall()
                        transfer['items'] = items
                except Exception as e:
                    logger.warning(f"Could not fetch transfer items: {e}")
                    transfer['items'] = []

            return transfer

        except Exception as e:
            logger.error(f"Error getting transfer {transfer_id}: {e}")
            return None
        finally:
            cursor.close()
            conn.close()

class SAPConnector:
    def __init__(self):
        self.conn = None
        self.params = {
            'ashost': os.getenv('SAP_ASHOST', '192.168.254.154'),
            'sysnr': os.getenv('SAP_SYSNR', '01'),
            'client': os.getenv('SAP_CLIENT', '300'),
            'user': os.getenv('SAP_USERNAME', 'sapuser'),
            'passwd': os.getenv('SAP_PASSWORD', 'sappassword'),
            'lang': os.getenv('SAP_LANG', 'EN')
        }

    def connect(self):
        """Connect to SAP"""
        try:
            logger.info(f"Attempting SAP connection with params: host={self.params['ashost']}, client={self.params['client']}, user={self.params['user'][:5]}...")
            self.conn = pyrfc.Connection(**self.params)

            # Test connection
            result = self.conn.call('RFC_PING')
            logger.info("SAP Connection successful")
            return True

        except pyrfc._exception.LogonError as e:
            logger.error(f"SAP Logon failed: {e}")
            return False
        except pyrfc._exception.CommunicationError as e:
            logger.error(f"SAP Communication failed: {e}")
            return False
        except Exception as e:
            logger.error(f"SAP Connection failed: {e}")
            return False

    def disconnect(self):
        """Disconnect from SAP"""
        if self.conn:
            try:
                self.conn.close()
                logger.info("SAP connection closed")
            except Exception as e:
                logger.error(f"Error closing SAP connection: {e}")

    def get_reservation_data(self, plant: str, pro_numbers: list):
        """Get reservation data from SAP - Loop per PRO number"""
        all_data = []

        try:
            if not self.conn and not self.connect():
                return None

            for pro_number in pro_numbers:
                try:
                    result = self.conn.call(
                        'Z_FM_YMMF005',
                        P_WERKS=plant,
                        P_AUFNR=pro_number
                    )

                    if 'T_DATA1' in result:
                        data = result['T_DATA1']
                        if isinstance(data, list) and data:
                            for item in data:
                                item['PRO_NUMBER'] = pro_number
                                all_data.append(item)

                except Exception as e:
                    logger.error(f"Error getting data for PRO {pro_number}: {e}")
                    continue

            return all_data

        except Exception as e:
            logger.error(f"Error in get_reservation_data: {e}")
            return None

    def get_stock_data(self, plant: str, matnr: str):
        """Get stock data from SAP using RFC Z_FM_YMMR006NX"""
        try:
            if not self.conn and not self.connect():
                return None

            result = self.conn.call(
                'Z_FM_YMMR006NX',
                P_WERKS=plant,
                P_MATNR=matnr
            )

            if 'T_DATA' in result:
                data = result['T_DATA']
                if isinstance(data, list) and data:
                    return data
                else:
                    return []
            else:
                return []

        except Exception as e:
            logger.error(f"Error getting stock data: {e}")
            return None

# Initialize
sap = SAPConnector()
db = MySQLHandler()

@app.route('/api/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'healthy',
        'service': 'SAP Sync',
        'timestamp': datetime.now().isoformat()
    })

@app.route('/api/sap/sync', methods=['POST'])
def sync_reservations():
    start_time = time.time()

    try:
        data = request.get_json()
        plant = data.get('plant')
        pro_numbers = data.get('pro_numbers', [])
        user_id = data.get('user_id')

        if not plant or not pro_numbers:
            return jsonify({
                'success': False,
                'message': 'Plant and PRO numbers are required'
            }), 400

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

        saved_count = db.save_reservation_data(plant, pro_numbers, sap_data, user_id)
        sap.disconnect()

        processing_time = round(time.time() - start_time, 2)

        return jsonify({
            'success': True,
            'message': f'Sync completed. {saved_count} records saved from {len(pro_numbers)} PRO numbers.',
            'synced_count': saved_count,
            'total_pros': len(pro_numbers),
            'records_from_sap': len(sap_data),
            'processing_time': processing_time
        })

    except Exception as e:
        logger.error(f"Sync error: {e}")
        return jsonify({
            'success': False,
            'message': f'Sync failed: {e}'
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

        stock_data = sap.get_stock_data(plant, matnr)

        if stock_data is None:
            return jsonify({
                'success': False,
                'message': 'Failed to get stock data from SAP'
            }), 500

        processing_time = round(time.time() - start_time, 2)

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
            }
            formatted_data.append(formatted_item)

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
        logger.error(f"Stock data error: {e}")
        return jsonify({
            'success': False,
            'message': f'Failed to get stock data: {e}'
        }), 500

@app.route('/api/sap/transfer', methods=['POST'])
def create_sap_transfer():
    """Create goods movement transfer in SAP using RFC Z_RFC_GOODSMVT_PYCHAR4"""
    start_time = time.time()

    try:
        data = request.get_json()
        transfer_data = data.get('transfer_data', {})
        sap_credentials = data.get('sap_credentials', {})
        user_id = data.get('user_id')
        user_name = data.get('user_name', 'WEBUSER')

        if not transfer_data or 'items' not in transfer_data:
            return jsonify({
                'success': False,
                'message': 'Transfer data is required'
            }), 400

        items = transfer_data.get('items', [])
        if not items:
            return jsonify({
                'success': False,
                'message': 'No items in transfer'
            }), 400

        # Setup SAP connection with credentials
        sap_conn = SAPConnector()

        # Use credentials from request or environment
        if sap_credentials:
            sap_conn.params.update({
                'ashost': sap_credentials.get('ashost', os.getenv('SAP_ASHOST')),
                'sysnr': sap_credentials.get('sysnr', os.getenv('SAP_SYSNR')),
                'client': sap_credentials.get('client', os.getenv('SAP_CLIENT')),
                'user': sap_credentials.get('user', os.getenv('SAP_USERNAME')),
                'passwd': sap_credentials.get('passwd', os.getenv('SAP_PASSWORD')),
                'lang': sap_credentials.get('lang', os.getenv('SAP_LANG', 'EN'))
            })

        # Connect to SAP
        if not sap_conn.connect():
            return jsonify({
                'success': False,
                'message': 'SAP connection failed. Please check SAP credentials and connection.'
            }), 500

        try:
            # Prepare IT_ITEMS table for RFC
            it_items = []
            item_results = []

            transfer_info = transfer_data.get('transfer_info', {})
            plant_supply = transfer_info.get('plant_supply', '')

            # Jika tidak ada di transfer_info, coba dari item pertama
            if not plant_supply and items:
                plant_supply = items[0].get('plant_supply', '') if items else ''

            # Log plant_supply untuk debugging
            logger.info(f"Transfer plant_supply: {plant_supply}")

            for idx, item in enumerate(items, start=1):
                try:
                    # Ambil kode material dari item
                    material_code_raw = str(item.get('material_code', '')).strip()

                    # Format kode material dengan leading zero untuk numerik
                    if material_code_raw.isdigit():
                        # Hapus leading zero yang ada untuk menghindari double zero
                        material_code_clean = material_code_raw.lstrip('0')
                        if not material_code_clean:
                            material_code_clean = '0'
                        # Tambahkan leading zero hingga 18 karakter
                        material_code = material_code_clean.zfill(18)
                        material_code_note = f" (Format: {material_code_raw} → {material_code})"
                    else:
                        material_code = material_code_raw
                        material_code_note = " (Format asli)"

                    # Get values from item
                    quantity = float(item.get('quantity', 0))

                    # Ambil unit dari frontend, default 'PC'
                    unit_from_frontend = item.get('unit', 'PC')

                    # Jika unit dari frontend adalah 'PC', ubah menjadi 'ST' untuk SAP
                    sap_unit = 'ST' if unit_from_frontend.upper() == 'PC' else unit_from_frontend

                    # Parse batch_sloc - format: "SLOC:XXXX" or just "XXXX"
                    batch_sloc = item.get('batch_sloc', '')
                    if batch_sloc and batch_sloc.startswith('SLOC:'):
                        batch_sloc = batch_sloc.replace('SLOC:', '')

                    # Get batch from item
                    batch = item.get('batch', '')

                    # Get plant supply, plant tujuan dan sloc tujuan
                    plant_supply_item = item.get('plant_supply', plant_supply)
                    plant_tujuan = item.get('plant_tujuan', '')
                    sloc_tujuan = item.get('sloc_tujuan', '')

                    # Tentukan MOVE_TYPE berdasarkan perbandingan plant supply dan plant tujuan
                    if plant_supply_item and plant_tujuan:
                        if plant_supply_item == plant_tujuan:
                            move_type = '311'
                            move_type_note = " (transfer dalam plant yang sama)"
                        else:
                            move_type = '301'
                            move_type_note = " (transfer antar plant)"
                    else:
                        # Default jika tidak ada data plant
                        move_type = '311'
                        move_type_note = " (default, plant tidak ditentukan)"

                    # Validasi field wajib
                    if not material_code:
                        raise ValueError("Material code is required")
                    if quantity <= 0:
                        raise ValueError("Quantity must be greater than 0")
                    if not plant_supply_item:
                        raise ValueError("Plant supply is required")
                    if not batch_sloc:
                        raise ValueError("Storage location (batch_sloc) is required for material transfer")
                    if not plant_tujuan:
                        raise ValueError("Plant destination is required")
                    if not sloc_tujuan:
                        raise ValueError("SLOC destination is required")

                    # Format quantity to string (SAP expects string for ENTRY_QTY_CHAR)
                    quantity_str = f"{quantity:.3f}".rstrip('0').rstrip('.')

                    # Field SALES_ORD dan S_ORD_ITEM harus numeric string
                    sales_ord = item.get('sales_ord', '0000000000')
                    s_ord_item = item.get('s_ord_item', '000000')

                    # Pastikan hanya berisi angka dan memiliki panjang yang sesuai
                    sales_ord = ''.join(filter(str.isdigit, sales_ord)) or '0000000000'
                    s_ord_item = ''.join(filter(str.isdigit, s_ord_item)) or '000000'

                    # Pastikan panjang minimal untuk numeric string
                    if len(sales_ord) < 10:
                        sales_ord = sales_ord.zfill(10)
                    if len(s_ord_item) < 6:
                        s_ord_item = s_ord_item.zfill(6)

                    # Sesuai dengan field RFC SAP
                    sap_item = {
                        'MANDT': sap_conn.params.get('client', '300'),
                        'MATERIAL': material_code,
                        'PLANT': plant_supply_item,
                        'STGE_LOC': batch_sloc if batch_sloc else '',
                        'BATCH': batch if batch else '',
                        'MOVE_TYPE': move_type,
                        'ENTRY_QTY_CHAR': quantity_str,
                        'ENTRY_UOM': sap_unit,
                        'MOVE_PLANT': plant_tujuan,
                        'MOVE_STGE_LOC': sloc_tujuan,
                        'MOVE_BATCH': batch if batch else '',
                        'SALES_ORD': sales_ord,
                        'S_ORD_ITEM': s_ord_item
                    }
                    it_items.append(sap_item)

                    # Catat konversi unit dan format material dalam hasil item
                    unit_conversion_note = ""
                    if unit_from_frontend.upper() == 'PC':
                        unit_conversion_note = f" (Unit diubah dari PC ke ST untuk SAP)"

                    item_results.append({
                        'item_number': idx,
                        'material_code': material_code,
                        'material_code_raw': material_code_raw,
                        'plant_supply': plant_supply_item,
                        'plant_destination': plant_tujuan,
                        'move_type': move_type,
                        'move_type_note': move_type_note,
                        'status': 'PREPARED',
                        'message': f'Item prepared for transfer{material_code_note}{unit_conversion_note}{move_type_note}',
                        'unit_frontend': unit_from_frontend,
                        'unit_sap': sap_unit,
                        'material_formatted': material_code_raw.isdigit()
                    })

                except Exception as e:
                    logger.error(f"Error preparing item {idx}: {e}")
                    item_results.append({
                        'item_number': idx,
                        'material_code': item.get('material_code', ''),
                        'material_code_raw': item.get('material_code', ''),
                        'plant_supply': item.get('plant_supply', ''),
                        'plant_destination': item.get('plant_tujuan', ''),
                        'move_type': 'ERROR',
                        'move_type_note': f" (Error: {e})",
                        'status': 'ERROR',
                        'message': f'Error: {e}',
                        'unit_frontend': item.get('unit', ''),
                        'unit_sap': '',
                        'material_formatted': False
                    })
                    continue

            if not it_items:
                return jsonify({
                    'success': False,
                    'message': 'No valid items to transfer',
                    'item_results': item_results
                }), 400

            # Prepare RFC parameters
            rfc_params = {
                'IT_ITEMS': it_items,
            }

            logger.info(f"Calling RFC Z_RFC_GOODSMVT_PYCHAR4 with {len(it_items)} items")

            # Log detail items untuk debugging
            logger.info(f"Items detail to SAP: {json.dumps(it_items, indent=2, default=str)}")

            # Log konversi unit jika ada
            pc_to_st_items = [item for item in item_results if item.get('unit_frontend', '').upper() == 'PC' and item.get('unit_sap', '') == 'ST']
            if pc_to_st_items:
                logger.info(f"Converted {len(pc_to_st_items)} items from PC to ST for SAP")

            # Log format material jika ada
            formatted_materials = [item for item in item_results if item.get('material_formatted', False)]
            if formatted_materials:
                logger.info(f"Formatted {len(formatted_materials)} numeric material codes to 18 digits with leading zeros")

            # Log move_type distribution
            move_type_301 = [item for item in item_results if item.get('move_type') == '301']
            move_type_311 = [item for item in item_results if item.get('move_type') == '311']
            logger.info(f"Move Type distribution: 301 (antar plant) = {len(move_type_301)} items, 311 (dalam plant) = {len(move_type_311)} items")

            # Call SAP RFC
            result = sap_conn.conn.call('Z_RFC_GOODSMVT_PYCHAR4', **rfc_params)

            # Log respons SAP untuk debugging
            logger.info(f"SAP RFC response: {json.dumps(result, indent=2, default=str)}")

            # Check result
            if result is None:
                logger.error("SAP transfer returned None result")
                return jsonify({
                    'success': False,
                    'message': 'SAP transfer returned no result',
                    'item_results': item_results
                }), 500

            # Check for errors in RETURN table
            errors = []
            warnings = []
            if 'RETURN' in result:
                for msg in result['RETURN']:
                    msg_type = msg.get('TYPE', '')
                    msg_text = msg.get('MESSAGE', '')
                    msg_number = msg.get('MESSAGE_V2', '')

                    if msg_type in ['E', 'A', 'X']:
                        errors.append(f"{msg_type}: {msg_text} (Message: {msg_number})")
                    elif msg_type in ['W', 'I']:
                        warnings.append(f"{msg_type}: {msg_text}")

                    logger.info(f"SAP Message - Type: {msg_type}, Text: {msg_text}, Number: {msg_number}")

            # Get material document from SAP response
            material_doc = (
                result.get('MAT_DOC') or
                result.get('MATDOC') or
                result.get('MATERIALDOC') or
                result.get('EV_MATERIAL_DOC')
            )

            # Save transfer to MySQL database
            db_result = db.save_transfer_to_db(
                transfer_data=transfer_data,
                sap_response=result,
                item_results=item_results,
                user_id=user_id,
                user_name=user_name
            )

            if errors:
                error_message = 'SAP transfer failed: ' + ' | '.join(errors[:3])
                logger.error(f"Transfer failed with errors: {errors}")

                # Update transfer status to FAILED if already saved
                if db_result and db_result.get('transfer_id'):
                    db.update_transfer_status(
                        transfer_id=db_result['transfer_id'],
                        status='FAILED',
                        sap_response=result,
                        errors=errors
                    )

                return jsonify({
                    'success': False,
                    'message': error_message,
                    'errors': errors,
                    'item_results': item_results,
                    'db_saved': db_result is not None,
                    'transfer_id': db_result.get('transfer_id') if db_result else None,
                    'processing_time': round(time.time() - start_time, 2)
                }), 400

            if material_doc:
                logger.info(f"Transfer successful: Material Document {material_doc} created")

                response_data = {
                    'success': True,
                    'message': f'Material Document {material_doc} created successfully',
                    'transfer_no': material_doc,
                    'status': 'COMPLETED',
                    'item_results': item_results,
                    'processing_time': round(time.time() - start_time, 2)
                }

                if db_result is not None:
                    response_data['db_saved'] = True
                    response_data['transfer_id'] = db_result.get('transfer_id')
                    response_data['document_id_included'] = db_result.get('document_id_included', False)

                    # Update transfer dengan material_doc jika belum ada
                    if not db_result.get('material_doc'):
                        db.update_transfer_status(
                            transfer_id=db_result['transfer_id'],
                            status='COMPLETED',
                            material_doc=material_doc,
                            sap_response=result
                        )
                else:
                    response_data['db_saved'] = False
                    response_data['message'] += ' (but failed to save to database)'

                return jsonify(response_data)
            else:
                logger.warning(f"Transfer completed without material document. Checking for success...")

                # Check if any success message in RETURN table
                success_messages = []
                if 'RETURN' in result:
                    for msg in result['RETURN']:
                        msg_type = msg.get('TYPE', '')
                        msg_text = msg.get('MESSAGE', '')
                        if msg_type in ['S', 'I', 'W']:
                            success_messages.append(msg_text)

                if success_messages and not errors:
                    response_data = {
                        'success': True,
                        'message': 'Transfer submitted successfully: ' + ' | '.join(success_messages[:2]),
                        'status': 'SUBMITTED',
                        'item_results': item_results,
                        'processing_time': round(time.time() - start_time, 2)
                    }

                    if db_result is not None:
                        response_data['db_saved'] = True
                        response_data['transfer_id'] = db_result.get('transfer_id')
                    else:
                        response_data['db_saved'] = False

                    return jsonify(response_data)
                else:
                    # Update transfer status to FAILED if no material doc and no success messages
                    if db_result and db_result.get('transfer_id'):
                        db.update_transfer_status(
                            transfer_id=db_result['transfer_id'],
                            status='FAILED',
                            sap_response=result
                        )

                    return jsonify({
                        'success': False,
                        'message': 'Transfer failed: No material document created',
                        'item_results': item_results,
                        'db_saved': db_result is not None,
                        'transfer_id': db_result.get('transfer_id') if db_result else None,
                        'processing_time': round(time.time() - start_time, 2)
                    }), 400

        except pyrfc._exception.RFCError as rfc_error:
            error_code = getattr(rfc_error, 'code', 'UNKNOWN')
            error_message = getattr(rfc_error, 'message', str(rfc_error) if not isinstance(rfc_error, pyrfc._exception.RFCError) else 'RFC Error')
            error_key = getattr(rfc_error, 'key', '')

            logger.error(f"RFC Error during SAP transfer. Code: {error_code}, Message: {error_message}, Key: {error_key}")

            return jsonify({
                'success': False,
                'message': f'SAP RFC Error: {error_message}',
                'error_code': error_code,
                'error_key': error_key,
                'error_type': 'RFC_ERROR',
                'item_results': item_results
            }), 500

        except Exception as e:
            logger.error(f"Error during SAP transfer: {e}")
            logger.error(traceback.format_exc())
            return jsonify({
                'success': False,
                'message': f'SAP transfer error: {e}',
                'error_type': 'GENERAL_ERROR',
                'item_results': item_results
            }), 500

        finally:
            try:
                sap_conn.disconnect()
            except:
                pass

    except json.JSONDecodeError as e:
        logger.error(f"JSON decode error: {e}")
        return jsonify({
            'success': False,
            'message': f'Invalid JSON data: {e}'
        }), 400

    except Exception as e:
        logger.error(f"Transfer creation error: {e}")
        logger.error(traceback.format_exc())
        return jsonify({
            'success': False,
            'message': f'Transfer failed: {e}'
        }), 500

@app.route('/api/sap/transfer/update/<int:transfer_id>', methods=['PUT'])
def update_sap_transfer(transfer_id):
    """Update status transfer yang sudah ada"""
    try:
        data = request.get_json()
        status = data.get('status')
        material_doc = data.get('material_doc')
        sap_response = data.get('sap_response')
        errors = data.get('errors')

        if not status:
            return jsonify({
                'success': False,
                'message': 'Status is required'
            }), 400

        success = db.update_transfer_status(transfer_id, status, material_doc, sap_response, errors)

        if success:
            return jsonify({
                'success': True,
                'message': f'Transfer {transfer_id} updated to {status}',
                'transfer_id': transfer_id,
                'status': status
            })
        else:
            return jsonify({
                'success': False,
                'message': f'Failed to update transfer {transfer_id}'
            }), 500

    except Exception as e:
        logger.error(f"Update transfer error: {e}")
        return jsonify({
            'success': False,
            'message': f'Update failed: {e}'
        }), 500

@app.route('/api/sap/transfer/<int:transfer_id>', methods=['GET'])
def get_transfer(transfer_id):
    """Get transfer data by ID"""
    try:
        transfer = db.get_transfer_by_id(transfer_id)

        if transfer:
            return jsonify({
                'success': True,
                'transfer': transfer
            })
        else:
            return jsonify({
                'success': False,
                'message': f'Transfer {transfer_id} not found'
            }), 404

    except Exception as e:
        logger.error(f"Get transfer error: {e}")
        return jsonify({
            'success': False,
            'message': f'Failed to get transfer: {e}'
        }), 500

@app.route('/api/sap/transfer/item/update', methods=['PUT'])
def update_transfer_item():
    """Update status item transfer"""
    try:
        data = request.get_json()
        transfer_id = data.get('transfer_id')
        item_number = data.get('item_number')
        status = data.get('status')
        sap_status = data.get('sap_status')
        sap_message = data.get('sap_message')

        if not all([transfer_id, item_number, status]):
            return jsonify({
                'success': False,
                'message': 'transfer_id, item_number, and status are required'
            }), 400

        success = db.update_transfer_item_status(transfer_id, item_number, status, sap_status, sap_message)

        if success:
            return jsonify({
                'success': True,
                'message': f'Item {item_number} of transfer {transfer_id} updated',
                'transfer_id': transfer_id,
                'item_number': item_number,
                'status': status
            })
        else:
            return jsonify({
                'success': False,
                'message': f'Failed to update item {item_number} of transfer {transfer_id}'
            }), 500

    except Exception as e:
        logger.error(f"Update transfer item error: {e}")
        return jsonify({
            'success': False,
            'message': f'Update failed: {e}'
        }), 500

if __name__ == '__main__':
    print("=" * 50)
    print("SAP Transfer Service Started")
    print(f"Environment: SAP_HOST={os.getenv('SAP_ASHOST', 'Not Set')}")
    print(f"Environment: SAP_CLIENT={os.getenv('SAP_CLIENT', 'Not Set')}")
    print("=" * 50)
    app.run(host='0.0.0.0', port=5000, debug=True)
