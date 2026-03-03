import os
import re
import pytesseract
import pandas as pd
from pdf2image import convert_from_path
from datetime import datetime

# CONFIGURACIÓN
PDF_DIR = "pdfs"           # Carpeta con los archivos PDF
OUTPUT_FILE = "votaciones.xlsx"  # Archivo Excel de salida
TESSERACT_PATH = r"C:\Program Files\Tesseract-OCR\tesseract.exe"  # Cambia según tu instalación

pytesseract.pytesseract.tesseract_cmd = TESSERACT_PATH

# === FUNCIONES ===
def ocr_pdf_to_text(pdf_path):
    """Convierte todas las páginas de un PDF a texto usando OCR."""
    try:
        images = convert_from_path(pdf_path, dpi=300)
        full_text = ""
        for i, img in enumerate(images):
            text = pytesseract.image_to_string(img, lang="spa")
            full_text += f"\n--- PAGE {i+1} ---\n{text}"
        return full_text
    except Exception as e:
        print(f"⚠️ Error OCR en {pdf_path}: {e}")
        return ""


def parse_votacion_text(text):
    """Extrae datos estructurados desde el texto OCR del PDF."""
    evento = {}
    votos = []

    # Buscar encabezado del evento
    m_evento = re.search(r"EVENTO\s+DE\s+VOTACI[ÓO]N\s*#\s*(\d+)", text, re.I)
    if m_evento:
        evento["numero_evento"] = m_evento.group(1)

    m_sesion = re.search(r"SESI[ÓO]N\s+No\.\s*(\d+)", text, re.I)
    if m_sesion:
        evento["sesion"] = m_sesion.group(1)

    m_fecha = re.search(r"(\d{2})[-/](\d{2})[-/](\d{4})\s*(\d{2}):(\d{2})", text)
    if m_fecha:
        evento["fecha_hora"] = f"{m_fecha.group(3)}-{m_fecha.group(2)}-{m_fecha.group(1)} {m_fecha.group(4)}:{m_fecha.group(5)}"
    else:
        evento["fecha_hora"] = None

    # Buscar título del evento (APROBACIÓN / ELECCIÓN / DESIGNACIÓN)
    m_titulo = re.search(r"(APROBACI[ÓO]N|ELECCI[ÓO]N|DESIGNACI[ÓO]N)\s+[A-ZÁÉÍÓÚÑa-záéíóúñ\s,]+", text)
    if m_titulo:
        evento["titulo"] = m_titulo.group(0).strip()
    else:
        evento["titulo"] = "Sin título"

    # Buscar filas de votos (No, Nombre, Bloque, Voto)
    lineas = text.splitlines()
    for linea in lineas:
        linea = linea.strip()
        # Coincidencias tipo: "23  JUAN PÉREZ  UNE  A FAVOR"
        m = re.match(
            r"^(\d+)\s+([A-ZÁÉÍÓÚÑa-záéíóúñ\'\.\s]+)\s+([A-ZÁÉÍÓÚÑa-záéíóúñ\s]+)\s+(A\s+FAVOR|EN\s+CONTRA|AUSENTE|LICENCIA|ABSTENCION)",
            linea, re.I)
        if m:
            votos.append({
                "numero": int(m.group(1)),
                "nombre": m.group(2).strip(),
                "bloque": m.group(3).strip(),
                "voto": m.group(4).upper()
            })

    return evento, votos


def procesar_carpeta():
    """Procesa todos los PDFs en la carpeta y los exporta a Excel."""
    registros = []
    for file in os.listdir(PDF_DIR):
        if not file.lower().endswith(".pdf"):
            continue
        pdf_path = os.path.join(PDF_DIR, file)
        print(f"📄 Procesando: {file}")

        texto = ocr_pdf_to_text(pdf_path)
        if not texto.strip():
            print(f"⚠️ No se extrajo texto de {file}")
            continue

        evento, votos = parse_votacion_text(texto)
        if not votos:
            print(f"⚠️ No se detectaron votos en {file}")
            continue

        for v in votos:
            registros.append({
                "archivo": file,
                "evento_numero": evento.get("numero_evento"),
                "sesion": evento.get("sesion"),
                "fecha_hora": evento.get("fecha_hora"),
                "titulo": evento.get("titulo"),
                "congresista": v["nombre"],
                "bloque": v["bloque"],
                "voto": v["voto"]
            })

    # Exportar a Excel
    if registros:
        df = pd.DataFrame(registros)
        df.to_excel(OUTPUT_FILE, index=False)
        print(f"✅ Datos exportados a {OUTPUT_FILE} ({len(df)} filas)")
    else:
        print("⚠️ No se generaron registros válidos.")


if __name__ == "__main__":
    inicio = datetime.now()
    procesar_carpeta()
    print(f"⏱️ Tiempo total: {datetime.now() - inicio}")
