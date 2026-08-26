import re
import os

input_file = r'c:\xampp\htdocs\unificacion_maga\Databases\votaciones-congreso (2).sql'
output_file = r'c:\xampp\htdocs\unificacion_maga\Databases\votaciones_recientes_para_importar.sql'

# Mapeo de tablas antiguas a nuevas
table_mapping = {
    'bloques': 'votaciones_bloques',
    'congresistas': 'votaciones_congresistas',
    'eventos_votacion': 'votaciones_eventos',
    'resumen_eventos': 'votaciones_resumen_eventos',
    'votos': 'votaciones_votos',
    'historial_bloques': 'votaciones_historial_bloques'
}

with open(input_file, 'r', encoding='utf-8') as f_in, open(output_file, 'w', encoding='utf-8') as f_out:
    f_out.write("SET FOREIGN_KEY_CHECKS = 0;\n")
    for line in f_in:
        # Reemplazar los nombres de las tablas en CREATE, INSERT, ALTER, etc.
        for old_table, new_table in table_mapping.items():
            # Patrón para coincidencias exactas con backticks
            line = re.sub(rf'`{old_table}`', f'`{new_table}`', line)
            # También para los ALTER TABLE, DROP TABLE, etc que pueden o no tener backticks pero mejor ser seguros
            # (Aunque en los dumps de phpmyadmin usualmente tienen backticks)
        
        # Eliminar cláusulas DEFINER que causan errores de permisos (Error #1227)
        line = re.sub(r'DEFINER=`[^`]+`@`[^`]+`\s*', '', line)
        line = re.sub(r'DEFINER=[^\s]+\s*', '', line)
        # Detectar si es un CREATE TABLE para inyectar DROP TABLE IF EXISTS antes
        if line.startswith('CREATE TABLE `'):
            match = re.search(r'CREATE TABLE `([^`]+)`', line)
            if match:
                f_out.write(f"DROP TABLE IF EXISTS `{match.group(1)}`;\n")
            
        f_out.write(line)
    f_out.write("\nSET FOREIGN_KEY_CHECKS = 1;\n")

print(f"Dump adaptado generado en: {output_file}")
