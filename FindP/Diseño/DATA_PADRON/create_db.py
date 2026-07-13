#!/usr/bin/env python3
import sqlite3
import csv
import time
import os
import re

CSV_PATH = 'ListadoGTCompleto.csv'
DB_PATH = 'padron.db'

def clean_dpi_func(dpi):
    if not dpi:
        return ''
    return re.sub(r'\D', '', dpi)

def main():
    if not os.path.exists(CSV_PATH):
        print(f"Error: No se encontró el archivo {CSV_PATH}")
        return

    print(f"Iniciando creación de la base de datos SQLite en: {DB_PATH}")
    start_time = time.time()

    # Si ya existe, lo eliminamos para recrearlo limpio
    if os.path.exists(DB_PATH):
        print(f"Eliminando base de datos existente {DB_PATH} para reconstruirla...")
        os.remove(DB_PATH)

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    # Optimizaciones de rendimiento de SQLite
    cursor.execute("PRAGMA synchronous = OFF;")
    cursor.execute("PRAGMA journal_mode = OFF;")
    cursor.execute("PRAGMA temp_store = MEMORY;")
    cursor.execute("PRAGMA cache_size = -1000000;")  # ~1GB de cache

    # Crear tabla principal
    print("Creando tabla 'padron'...")
    cursor.execute("""
        CREATE TABLE padron (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            departamento TEXT,
            municipio TEXT,
            aldea TEXT,
            nombre TEXT,
            dpi TEXT,
            dpi_clean TEXT,
            edad INTEGER
        );
    """)
    conn.commit()

    print("Importando registros del CSV (esto puede tomar 1-2 minutos)...")
    insert_sql = """
        INSERT INTO padron (departamento, municipio, aldea, nombre, dpi, dpi_clean, edad)
        VALUES (?, ?, ?, ?, ?, ?, ?);
    """
    
    batch_size = 100000
    batch = []
    total_inserted = 0

    with open(CSV_PATH, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        # Leer cabecera
        header = next(reader)
        # Mapear columnas: DEPARTAMENTO,MUNICIPIO,ALDEA,NOMBRE,DPI,EDAD
        col_indices = {col.upper(): idx for idx, col in enumerate(header)}
        
        dept_idx = col_indices.get('DEPARTAMENTO', 0)
        muni_idx = col_indices.get('MUNICIPIO', 1)
        aldea_idx = col_indices.get('ALDEA', 2)
        nombre_idx = col_indices.get('NOMBRE', 3)
        dpi_idx = col_indices.get('DPI', 4)
        edad_idx = col_indices.get('EDAD', 5)

        cursor.execute("BEGIN TRANSACTION;")
        for row in reader:
            if not row:
                continue
            
            dept = row[dept_idx]
            muni = row[muni_idx]
            aldea = row[aldea_idx]
            nombre = row[nombre_idx]
            dpi = row[dpi_idx]
            dpi_clean = clean_dpi_func(dpi)
            
            try:
                edad = int(row[edad_idx])
            except ValueError:
                edad = 0

            batch.append((dept, muni, aldea, nombre, dpi, dpi_clean, edad))

            if len(batch) >= batch_size:
                cursor.executemany(insert_sql, batch)
                total_inserted += len(batch)
                batch = []
                print(f"  Insertados: {total_inserted:,} registros...")

        if batch:
            cursor.executemany(insert_sql, batch)
            total_inserted += len(batch)
            batch = []

        conn.commit()
    
    print(f"Total importado en tabla principal: {total_inserted:,} registros.")

    # Crear índices estándar
    print("Creando índice para búsquedas por DPI (dpi_clean)...")
    cursor.execute("CREATE INDEX idx_padron_dpi_clean ON padron(dpi_clean);")
    conn.commit()

    # Crear tabla de búsqueda FTS5
    print("Creando tabla virtual de búsqueda de texto (padron_fts)...")
    cursor.execute("""
        CREATE VIRTUAL TABLE padron_fts USING fts5(
            nombre,
            content='padron',
            content_rowid='id',
            tokenize='unicode61 remove_diacritics 1'
        );
    """)
    conn.commit()

    # Popular tabla FTS5
    print("Llenando índice de búsqueda de texto FTS5 (esto puede tomar 1 minuto)...")
    fts_start = time.time()
    cursor.execute("INSERT INTO padron_fts(rowid, nombre) SELECT id, nombre FROM padron;")
    conn.commit()
    print(f"  Índice FTS5 llenado en {time.time() - fts_start:.2f} segundos.")

    # Optimizar FTS5
    print("Optimizando base de datos (VACUUM / OPTIMIZE)...")
    cursor.execute("INSERT INTO padron_fts(padron_fts) VALUES('optimize');")
    conn.commit()
    
    conn.close()

    elapsed = time.time() - start_time
    print(f"\nProceso terminado con éxito en {elapsed:.2f} segundos!")
    print(f"Base de datos SQLite lista en: {DB_PATH}")

if __name__ == '__main__':
    main()
