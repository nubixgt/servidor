import re

sql_file = r'c:\xampp\htdocs\unificacion_maga\Databases\vider_maga.sql'

with open(sql_file, 'r', encoding='utf-8') as f:
    content = f.read()

# Buscamos patrones como: CREATE TABLE `nombre` (
# y lo reemplazamos por:
# DROP TABLE IF EXISTS `nombre`;
# CREATE TABLE `nombre` (

pattern = r'CREATE TABLE `([^`]+)` \('

def replacer(match):
    table_name = match.group(1)
    return f'DROP TABLE IF EXISTS `{table_name}`;\nCREATE TABLE `{table_name}` ('

new_content = re.sub(pattern, replacer, content)

with open(sql_file, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Archivo SQL actualizado con DROP TABLE IF EXISTS.")
