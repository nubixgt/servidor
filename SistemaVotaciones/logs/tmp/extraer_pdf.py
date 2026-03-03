#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import sys
import json
import pdfplumber
import re

def extraer_datos(pdf_path):
    datos = {'evento': {}, 'votos': []}
    
    with pdfplumber.open(pdf_path) as pdf:
        # Metadata
        first_page_text = pdf.pages[0].extract_text()
        
        m = re.search(r'EVENTO\s+DE\s+VOTACI[ÓO]N\s*#?\s*(\d+)', first_page_text, re.I)
        if m:
            datos['evento']['numero'] = m.group(1)
        
        m = re.search(r'SESI[ÓO]N\s+No\.?\s*(\d+)', first_page_text, re.I)
        if m:
            datos['evento']['sesion'] = m.group(1)
        
        m = re.search(r'(\d{2})-(\d{2})-(\d{4})\s+(\d{2}):(\d{2}):(\d{2})', first_page_text)
        if m:
            datos['evento']['fecha_hora'] = f"{m.group(3)}-{m.group(2)}-{m.group(1)} {m.group(4)}:{m.group(5)}:{m.group(6)}"
        
        m = re.search(r'(APROBACI[ÓO]N|ELECCI[ÓO]N|DESIGNACI[ÓO]N)[^\n]{10,200}', first_page_text, re.I)
        if m:
            datos['evento']['titulo'] = m.group(0).strip()
        else:
            datos['evento']['titulo'] = 'Sin título'
        
        # Extraer votos
        for page in pdf.pages:
            tables = page.extract_tables()
            
            for table in tables:
                if not table or len(table) < 2:
                    continue
                
                header = table[0]
                
                # Buscar índices de columnas
                idx_numero = idx_nombre = idx_bloque = idx_voto = None
                
                for i, col in enumerate(header):
                    col_str = str(col).upper() if col else ''
                    if 'NO' in col_str and idx_numero is None:
                        idx_numero = i
                    elif 'NOMBRE' in col_str:
                        idx_nombre = i
                    elif 'BLOQUE' in col_str:
                        idx_bloque = i
                    elif 'VOTO' in col_str:
                        idx_voto = i
                
                if None in [idx_numero, idx_nombre, idx_bloque, idx_voto]:
                    continue
                
                # Procesar filas
                for row in table[1:]:
                    if len(row) < max(idx_numero, idx_nombre, idx_bloque, idx_voto) + 1:
                        continue
                    
                    num_str = str(row[idx_numero]).strip() if row[idx_numero] else ''
                    nombre = str(row[idx_nombre]).strip() if row[idx_nombre] else ''
                    bloque = str(row[idx_bloque]).strip() if row[idx_bloque] else ''
                    voto = str(row[idx_voto]).strip() if row[idx_voto] else ''
                    
                    if not num_str or not num_str.isdigit():
                        continue
                    
                    if voto not in ['A FAVOR', 'EN CONTRA', 'AUSENTE', 'LICENCIA', 'ABSTENCION', 'ABSTENCIÓN']:
                        continue
                    
                    bloque = re.sub(r'\s+', ' ', bloque).strip()
                    
                    if voto in ['ABSTENCION', 'ABSTENCIÓN']:
                        voto = 'ABSTENCION'
                    
                    palabras = [p for p in nombre.split() if len(p) >= 2]
                    if len(palabras) < 2:
                        continue
                    
                    datos['votos'].append({
                        'numero': int(num_str),
                        'nombre': nombre,
                        'bloque': bloque if bloque else 'INDEPENDIENTE',
                        'voto': voto
                    })
    
    return datos

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Error: Se requieren 2 argumentos", file=sys.stderr)
        sys.exit(1)
    
    try:
        datos = extraer_datos(sys.argv[1])
        with open(sys.argv[2], 'w', encoding='utf-8') as f:
            json.dump(datos, f, ensure_ascii=False, indent=2)
        sys.exit(0)
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)