#!/usr/bin/env python3
"""
Extractor de datos del padrón electoral (ListadoGTCompleto.pdf) a CSV corregido.
Maneja transiciones de Departamento, Municipio y Comunidad que se extienden
a través de límites de página y evita la concatenación errónea de nombres
con encabezados de comunidad.
"""

import fitz  # PyMuPDF
import csv
import re
import time
import sys

PDF_PATH = "ListadoGTCompleto.pdf"
CSV_PATH = "ListadoGTCompleto.csv"
PROGRESS_INTERVAL = 1000

DPI_PATTERN = re.compile(r'^\d{4}\s+\d{5}\s+\d{4}$')
TIME_PATTERN = re.compile(r'^\d{2}:\d{2}:\d{2}$')
DATE_PATTERN = re.compile(r'^\d{2}/\d{2}/\d{4}$')
PAGE_FOOTER_PATTERN = re.compile(r'^Página \d+ de \d+$')

def is_header_footer(line):
    s = line.strip()
    if not s:
        return True
    if TIME_PATTERN.match(s) or DATE_PATTERN.match(s):
        return True
    if s in ('IDENTIFICACION', 'EDAD', 'NOMBRE CIUDADANO', 
             'LISTADO DE CIUDADANOS DE TODA LA REPUBLICA', 
             'ACTUALIZADO A LA FECHA',
             'DESGLOSADO POR DEPARTAMENTOS, MUNICIPIOS Y COMUNIDADES'):
        return True
    if PAGE_FOOTER_PATTERN.match(s) or s.startswith('Autorizado por'):
        return True
    return False

def main():
    print(f"Abriendo PDF: {PDF_PATH}")
    doc = fitz.open(PDF_PATH)
    total_pages = len(doc)
    print(f"Total de páginas: {total_pages}")

    start_time = time.time()

    # Estado del extractor
    current_dept = ""
    current_muni = ""
    current_aldea = ""

    pending_dept = True
    pending_muni = True
    pending_comunidad = True

    record_count = 0

    with open(CSV_PATH, 'w', newline='', encoding='utf-8') as csvfile:
        writer = csv.writer(csvfile)
        writer.writerow(['DEPARTAMENTO', 'MUNICIPIO', 'ALDEA', 'NOMBRE', 'DPI', 'EDAD'])

        for page_num in range(total_pages):
            try:
                page = doc[page_num]
                text = page.get_text()
                lines = text.split('\n')

                # Filtrar cabeceras y pies de página
                clean_lines = [l.strip() for l in lines if not is_header_footer(l)]

                j = 0
                while j < len(clean_lines):
                    line = clean_lines[j]

                    # 1. Detectar marcadores de transición
                    if "TOTAL DE EMPADRONADOS EN ESTA COMUNIDAD" in line:
                        pending_comunidad = True
                        j += 1
                        continue
                    elif "TOTAL DE EMPADRONADOS EN ESTE MUNICIPIO" in line:
                        pending_muni = True
                        pending_comunidad = True
                        j += 1
                        continue
                    elif "TOTAL DE EMPADRONADOS EN ESTE DEPARTAMENTO" in line:
                        pending_dept = True
                        pending_muni = True
                        pending_comunidad = True
                        j += 1
                        continue
                    elif "TOTAL DE EMPADRONADOS EN LA REPUBLICA" in line:
                        j += 1
                        continue

                    # Saltar totales numéricos
                    if re.match(r'^[\d,]+$', line):
                        j += 1
                        continue

                    # 2. Procesar transiciones pendientes
                    if pending_dept:
                        # Esperamos: Código Dept (num), Nombre Dept (texto), Código Muni (num), Nombre Muni (texto), Nombre Aldea (texto)
                        if j < len(clean_lines) and clean_lines[j].isdigit():
                            j += 1  # Saltar código departamento
                        if j < len(clean_lines):
                            current_dept = clean_lines[j]
                            j += 1
                        if j < len(clean_lines) and clean_lines[j].isdigit():
                            j += 1  # Saltar código municipio
                        if j < len(clean_lines):
                            current_muni = clean_lines[j]
                            j += 1
                        if j < len(clean_lines):
                            current_aldea = clean_lines[j]
                            j += 1
                        pending_dept = False
                        pending_muni = False
                        pending_comunidad = False
                        continue

                    if pending_muni:
                        # Esperamos: Código Muni (num), Nombre Muni (texto), Nombre Aldea (texto)
                        if j < len(clean_lines) and clean_lines[j].isdigit():
                            j += 1  # Saltar código municipio
                        if j < len(clean_lines):
                            current_muni = clean_lines[j]
                            j += 1
                        if j < len(clean_lines):
                            current_aldea = clean_lines[j]
                            j += 1
                        pending_muni = False
                        pending_comunidad = False
                        continue

                    if pending_comunidad:
                        # Esperamos: Nombre Aldea (texto)
                        if j < len(clean_lines):
                            current_aldea = clean_lines[j]
                            j += 1
                        pending_comunidad = False
                        continue

                    # 3. Procesar registros de ciudadanos
                    # Buscar patrones de 1 o 2 líneas
                    found_record = False

                    # Registro de 1 línea de nombre: Nombre, Edad, DPI
                    if j + 2 < len(clean_lines):
                        name1 = clean_lines[j]
                        age_str = clean_lines[j+1]
                        dpi_str = clean_lines[j+2]
                        if age_str.isdigit() and 0 <= int(age_str) <= 150 and DPI_PATTERN.match(dpi_str):
                            writer.writerow([current_dept, current_muni, current_aldea, name1, dpi_str, age_str])
                            record_count += 1
                            j += 3
                            found_record = True
                            continue

                    # Registro de 2 líneas de nombre: Nombre1, Nombre2, Edad, DPI
                    if j + 3 < len(clean_lines):
                        name1 = clean_lines[j]
                        name2 = clean_lines[j+1]
                        age_str = clean_lines[j+2]
                        dpi_str = clean_lines[j+3]
                        if age_str.isdigit() and 0 <= int(age_str) <= 150 and DPI_PATTERN.match(dpi_str):
                            full_name = f"{name1} {name2}"
                            writer.writerow([current_dept, current_muni, current_aldea, full_name, dpi_str, age_str])
                            record_count += 1
                            j += 4
                            found_record = True
                            continue

                    # Si no coincide con nada, avanzamos para no quedar en bucle
                    j += 1

            except Exception as e:
                print(f"Error en página {page_num}: {e}", file=sys.stderr)

            # Mostrar progreso
            if (page_num + 1) % PROGRESS_INTERVAL == 0:
                elapsed = time.time() - start_time
                pps = (page_num + 1) / elapsed
                remaining = (total_pages - page_num - 1) / pps
                print(f"Progreso: {page_num + 1:,}/{total_pages:,} ({(page_num+1)/total_pages*100:.1f}%) | "
                      f"Registros: {record_count:,} | "
                      f"Resta: {remaining/60:.0f} min", flush=True)

    elapsed = time.time() - start_time
    print(f"\nEXTRACCIÓN COMPLETADA en {elapsed/60:.2f} minutos.")
    print(f"Total de registros: {record_count:,}")
    print(f"Archivo CSV: {CSV_PATH}")

if __name__ == '__main__':
    main()
