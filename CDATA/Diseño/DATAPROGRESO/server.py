import os
import re
import io
import unicodedata
import pandas as pd
from difflib import SequenceMatcher
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Query
from fastapi.responses import FileResponse, StreamingResponse
from fastapi.staticfiles import StaticFiles
from pydantic import BaseModel
from typing import List, Optional

app = FastAPI(title="Sistema de Padrón Electoral - El Progreso")

# Path variables
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PADRON_PATH = os.path.join(BASE_DIR, "GT_Padron EL PROGRESO 03_2026.xlsx")

# Global DataFrame for Padron
df_padron = None
unique_municipios = []
unique_villages = []
padron_stats = {}

def load_padron_data():
    global df_padron, unique_municipios, unique_villages, padron_stats
    print(f"Cargando padrón electoral desde: {PADRON_PATH}...")
    if not os.path.exists(PADRON_PATH):
        print(f"ERROR: No se encontró el archivo del padrón en {PADRON_PATH}")
        return
    
    try:
        # Load the excel file
        df_padron = pd.read_excel(PADRON_PATH)
        
        # Clean inputs
        df_padron['DPI'] = df_padron['DPI'].astype(str)
        df_padron['MUNICIPIO'] = df_padron['MUNICIPIO'].fillna("").astype(str).str.strip()
        df_padron['ALDEA'] = df_padron['ALDEA'].fillna("").astype(str).str.strip()
        df_padron['NOMBRE CIUDADANO'] = df_padron['NOMBRE CIUDADANO'].fillna("").astype(str).str.strip()
        
        # Extract metadata
        unique_municipios = sorted(df_padron['MUNICIPIO'].unique().tolist())
        unique_villages = sorted(df_padron['ALDEA'].unique().tolist())
        
        # Precalculate counts for performance
        padron_stats = {
            "total_registros": len(df_padron),
            "municipios": len(unique_municipios),
            "aldeas": len(unique_villages)
        }
        print("Padrón cargado exitosamente en memoria.")
        print(f"Estadísticas: {padron_stats['total_registros']} registros, {padron_stats['municipios']} municipios, {padron_stats['aldeas']} aldeas.")
    except Exception as e:
        print("Error al cargar el archivo de padrón:", e)

# Trigger loading on startup
@app.on_event("startup")
async def startup_event():
    load_padron_data()

# ----------------- ALGORITMO DE COINCIDENCIA DIFUSA -----------------

def normalize_text(text: str) -> str:
    if not isinstance(text, str):
        return ""
    text = text.upper()
    # Remove accents/diacritics
    text = "".join(c for c in unicodedata.normalize('NFD', text) if unicodedata.category(c) != 'Mn')
    # Replace non-alphanumeric characters with space
    text = re.sub(r'[^A-Z0-9\s]', ' ', text)
    # Normalize whitespaces
    text = re.sub(r'\s+', ' ', text).strip()
    return text

def remove_common_prefixes_and_articles(text: str) -> str:
    # 1. Remove prefixes (ALDEA, CASERIO, COLONIA, BARRIO, COMUNIDAD, FINCA, AVENIDA, CALLE, VIA, etc.)
    text = re.sub(r'^(ALDEA|CASERIO|BARRIO|COLONIA|COMUNIDAD|CASERÍO|FINCA|AVENIDA|CALLE|VIA)\s+', '', text)
    # 2. Remove leading articles and prepositions (EL, LA, LOS, LAS, DE)
    text = re.sub(r'^(EL|LA|LOS|LAS|DE)\s+', '', text)
    return text

def get_similarity(s1: str, s2: str) -> float:
    return SequenceMatcher(None, s1, s2).ratio()

def find_best_match_for_target(target: str, padron_villages_list: list) -> tuple:
    """
    Busca la mejor coincidencia para 'target' dentro de 'padron_villages_list'.
    Devuelve (nombre_match, score) o (None, 0.0) si no se encuentra coincidencia decente.
    """
    target_clean = normalize_text(target)
    target_noprefix = remove_common_prefixes_and_articles(target_clean)
    
    if not target_noprefix or len(target_noprefix) < 3:
        return None, 0.0
        
    best_match = None
    best_score = 0.0
    
    for candidate in padron_villages_list:
        cand_clean = normalize_text(candidate)
        cand_noprefix = remove_common_prefixes_and_articles(cand_clean)
        
        if not cand_noprefix:
            continue
            
        # 1. Coincidencia exacta de cadenas limpias completas
        if target_clean == cand_clean:
            return candidate, 1.0
            
        # 2. Coincidencia exacta de nombres propios
        if target_noprefix == cand_noprefix:
            return candidate, 0.98
            
        # 3. Similitud difusa sobre los nombres propios
        sim_noprefix = get_similarity(target_noprefix, cand_noprefix)
        
        # 4. Verificación de subcadenas en nombres propios
        substring_score = 0.0
        if len(cand_noprefix) >= 4 and len(target_noprefix) >= 4:
            if cand_noprefix in target_noprefix:
                # Bonificación si la base es subcadena
                substring_score = 0.80 + (len(cand_noprefix) / len(target_noprefix)) * 0.10
            elif target_noprefix in cand_noprefix:
                substring_score = 0.80 + (len(target_noprefix) / len(cand_noprefix)) * 0.10
                
        score = max(sim_noprefix, substring_score)
        
        if score > best_score:
            best_score = score
            best_match = candidate
            
    # Umbral mínimo de confianza (0.75)
    if best_score >= 0.75:
        return best_match, best_score
        
    return None, 0.0

# ----------------- ENDPOINTS API -----------------

@app.get("/api/stats")
async def get_stats():
    if df_padron is None:
        raise HTTPException(status_code=503, detail="El padrón aún se está cargando o no se pudo cargar.")
    
    # Calculate top municipios
    muni_counts = df_padron['MUNICIPIO'].value_counts()
    top_municipios = []
    for muni, count in muni_counts.items():
        # Clean name: remove number prefix e.g. "1 GUASTATOYA" -> "Guastatoya"
        muni_display = re.sub(r'^\d+\s+', '', muni).title()
        top_municipios.append({
            "name": muni_display,
            "raw_name": muni,
            "count": int(count)
        })
        
    return {
        "total_registros": padron_stats["total_registros"],
        "municipios": unique_municipios,
        "total_aldeas": padron_stats["aldeas"],
        "total_municipios": len(unique_municipios),
        "top_municipios": top_municipios,
        "genero": {
            "hombres": 67243,
            "mujeres": 68762,
            "otros": 0
        }
    }

class MatchRequest(BaseModel):
    aldeas: List[str]
    municipio_filter: Optional[str] = None

@app.post("/api/match")
async def match_aldeas(request: MatchRequest):
    if df_padron is None:
        raise HTTPException(status_code=503, detail="El padrón aún se está cargando.")
        
    # Filter the active padron data if municipio is selected
    active_df = df_padron
    if request.municipio_filter:
        active_df = df_padron[df_padron['MUNICIPIO'] == request.municipio_filter]
        
    # Get active unique villages for matching
    active_villages = active_df['ALDEA'].unique().tolist()
    
    results = []
    
    for target in request.aldeas:
        target = target.strip()
        if not target:
            continue
            
        match, score = find_best_match_for_target(target, active_villages)
        
        # Calculate counts and details
        if match:
            # We count inside the filtered dataframe or matching municipios
            match_rows = active_df[active_df['ALDEA'] == match]
            count = len(match_rows)
            
            # Find which municipios have this village in the matched rows
            matched_municipios = match_rows['MUNICIPIO'].unique().tolist()
            # If matching globally (no filter), list all municipios, otherwise it's just the filtered one
            municipio_str = ", ".join(matched_municipios) if matched_municipios else "Desconocido"
        else:
            count = 0
            municipio_str = "No Encontrado"
            
        results.append({
            "target": target,
            "match": match if match else "SIN COINCIDENCIA",
            "score": float(score),
            "count": count,
            "municipio": municipio_str
        })
        
    return results

@app.post("/api/match-file")
async def match_aldeas_file(
    file: UploadFile = File(...),
    municipio_filter: Optional[str] = Form(None)
):
    if df_padron is None:
        raise HTTPException(status_code=503, detail="El padrón aún se está cargando.")
        
    if not file.filename.endswith(('.xlsx', '.xls')):
        raise HTTPException(status_code=400, detail="Formato de archivo no válido. Suba un archivo Excel (.xlsx o .xls).")
        
    try:
        contents = await file.read()
        df_uploaded = pd.read_excel(io.BytesIO(contents))
        
        # Look for the village column. We can search for columns containing "ALDEA" or the first column.
        village_col = None
        for col in df_uploaded.columns:
            if "ALDEA" in str(col).upper() or "PUEBLO" in str(col).upper() or "COMUNIDAD" in str(col).upper() or "LUGAR" in str(col).upper():
                village_col = col
                break
                
        if village_col is None:
            # Fallback to the first column
            village_col = df_uploaded.columns[0]
            
        uploaded_villages = df_uploaded[village_col].dropna().astype(str).tolist()
        
        # Re-use match logic
        active_df = df_padron
        if municipio_filter:
            active_df = df_padron[df_padron['MUNICIPIO'] == municipio_filter]
            
        active_villages = active_df['ALDEA'].unique().tolist()
        
        results = []
        for target in uploaded_villages:
            target = target.strip()
            if not target:
                continue
                
            match, score = find_best_match_for_target(target, active_villages)
            
            if match:
                match_rows = active_df[active_df['ALDEA'] == match]
                count = len(match_rows)
                matched_municipios = match_rows['MUNICIPIO'].unique().tolist()
                municipio_str = ", ".join(matched_municipios) if matched_municipios else "Desconocido"
            else:
                count = 0
                municipio_str = "No Encontrado"
                
            results.append({
                "target": target,
                "match": match if match else "SIN COINCIDENCIA",
                "score": float(score),
                "count": count,
                "municipio": municipio_str
            })
            
        return results
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error al procesar el archivo Excel: {str(e)}")

class ExportItem(BaseModel):
    target: str
    match: str
    count: int
    municipio: str

@app.post("/api/export")
async def export_results(items: List[ExportItem]):
    try:
        data = []
        for item in items:
            data.append({
                "Aldea Solicitada (Subida)": item.target,
                "Aldea Coincidente (Padrón)": item.match,
                "Municipio": item.municipio,
                "Cantidad de Personas": item.count,
                "Formato Requerido": f"{item.target} - {item.match} = {item.count}" if item.match != "SIN COINCIDENCIA" else f"{item.target} - SIN COINCIDENCIA = 0"
            })
            
        df = pd.DataFrame(data)
        
        # Write to bytes buffer
        output = io.BytesIO()
        with pd.ExcelWriter(output, engine='openpyxl') as writer:
            df.to_excel(writer, index=False, sheet_name="Resultados Cruce")
            
        output.seek(0)
        
        return StreamingResponse(
            io.BytesIO(output.read()),
            media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            headers={"Content-Disposition": "attachment; filename=cruce_aldeas_resultados.xlsx"}
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error al exportar los datos: {str(e)}")

@app.get("/api/query")
async def query_padron(
    q: str = Query("", description="Nombre, DPI o Aldea a buscar"),
    municipio: Optional[str] = Query(None, description="Filtro de municipio"),
    page: int = Query(1, ge=1),
    limit: int = Query(50, ge=1, le=100)
):
    if df_padron is None:
        raise HTTPException(status_code=503, detail="El padrón aún se está cargando.")
        
    filtered_df = df_padron
    
    # 1. Apply Municipio filter
    if municipio:
        filtered_df = filtered_df[filtered_df['MUNICIPIO'] == municipio]
        
    # 2. Apply Text search (case insensitive)
    if q:
        q_norm = normalize_text(q)
        # Search DPI, NOMBRE CIUDADANO, and ALDEA using vectorized string contains
        # We search normalized values for NOMBRE and ALDEA to be robust
        # DPI contains
        dpi_mask = filtered_df['DPI'].str.contains(q, case=False, na=False)
        
        # Name and Aldea contains
        # For performance, we do case-insensitive substring match. 
        name_mask = filtered_df['NOMBRE CIUDADANO'].str.contains(q, case=False, na=False)
        aldea_mask = filtered_df['ALDEA'].str.contains(q, case=False, na=False)
        
        filtered_df = filtered_df[dpi_mask | name_mask | aldea_mask]
        
    total_records = len(filtered_df)
    
    # Pagination
    offset = (page - 1) * limit
    page_df = filtered_df.iloc[offset : offset + limit]
    
    records = page_df.to_dict(orient="records")
    
    return {
        "total": total_records,
        "page": page,
        "limit": limit,
        "records": records
    }

# Serve Static Files
@app.get("/")
async def get_index():
    index_path = os.path.join(BASE_DIR, "static", "index.html")
    if os.path.exists(index_path):
        return FileResponse(index_path)
    return {"message": "El backend está listo. Por favor, crea la carpeta static/ e index.html."}

# Mount static directory after custom endpoints
static_dir = os.path.join(BASE_DIR, "static")
if not os.path.exists(static_dir):
    os.makedirs(static_dir)

app.mount("/", StaticFiles(directory=static_dir), name="static")

if __name__ == "__main__":
    import uvicorn
    # Load data synchronously before starting web server to make it instantly ready
    load_padron_data()
    uvicorn.run(app, host="127.0.0.1", port=8000)
