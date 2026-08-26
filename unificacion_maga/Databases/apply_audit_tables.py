import mysql.connector
import os

config = {
    'user': 'u991565456_maga_un',
    'password': 'NR4bWu~u7B&o',
    'host': 'localhost',
    'database': 'u991565456_maga_un',
}

try:
    conn = mysql.connector.connect(**config)
    cursor = conn.cursor()
    
    with open('c:/xampp/htdocs/unificacion_maga/Databases/add_audit_notif_tables.sql', 'r', encoding='utf-8') as f:
        sql = f.read()
        
    for statement in sql.split(';'):
        if statement.strip():
            cursor.execute(statement)
            print("Executed:", statement[:50], "...")
            
    conn.commit()
    print("Tablas creadas correctamente.")
except Exception as e:
    print("Error:", e)
finally:
    if 'cursor' in locals():
        cursor.close()
    if 'conn' in locals() and conn.is_connected():
        conn.close()
