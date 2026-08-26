import re

def check_integrity():
    source_path = r'c:\Users\cvall\Desktop\GitHub\unificacion_maga\Databases\a_migrar.sql'
    dest_path = r'c:\Users\cvall\Desktop\GitHub\unificacion_maga\Databases\presupuesto_detallado.sql'
    
    # 1. Sumar totales en a_migrar.sql (ejecucion_ministerios año 2025)
    with open(source_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    min_matches = re.findall(r"\((\d+),(\d+),2025,([\d\.-]+),([\d\.-]+),([\d\.-]+),([\d\.-]+),([\d\.-]+),([\d\.-]+),([\d\.-]+),[^,]+,'(\d{4}-\d{2}-\d{2})'\)", content)
    
    sum_orig_asignado = sum(float(m[2]) for m in min_matches)
    sum_orig_vigente = sum(float(m[4]) for m in min_matches)
    sum_orig_devengado = sum(float(m[5]) for m in min_matches)

    # 2. Sumar totales en presupuesto_detallado.sql (IDs 1-14 año 2025)
    with open(dest_path, 'r', encoding='utf-8') as f:
        dest_content = f.read()
    
    # Buscamos el bloque de values y extraemos los de IDs 1-14
    # (ID,2025,ASIGNADO,MODIFICADO,VIGENTE,DEVENGADO,...)
    dest_matches = re.findall(r"\((\d+),2025,([\d\.-]+),([\d\.-]+),([\d\.-]+),([\d\.-]+),", dest_content)
    
    # Solo los que corresponden a Ministerios (1-14)
    sum_dest_asignado = sum(float(m[1]) for m in dest_matches if int(m[0]) <= 14)
    sum_dest_vigente = sum(float(m[3]) for m in dest_matches if int(m[0]) <= 14)
    sum_dest_devengado = sum(float(m[4]) for m in dest_matches if int(m[0]) <= 14)

    print(f"--- VERIFICACIÓN DE TOTALES (MINISTERIOS 2025) ---")
    print(f"ORIGEN  (a_migrar): Asignado: {sum_orig_asignado:,.2f}, Vigente: {sum_orig_vigente:,.2f}, Devengado: {sum_orig_devengado:,.2f}")
    print(f"DESTINO (detallado): Asignado: {sum_dest_asignado:,.2f}, Vigente: {sum_dest_vigente:,.2f}, Devengado: {sum_dest_devengado:,.2f}")
    
    if sum_orig_asignado == sum_dest_asignado and sum_orig_devengado == sum_dest_devengado:
        print("\n✅ ¡CUADRE PERFECTO! La data es idéntica.")
    else:
        print("\n❌ Error: Los totales no coinciden.")

check_integrity()
