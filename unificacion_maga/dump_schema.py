import re

sql_file = r'c:\xampp\htdocs\unificacion_maga\Databases\vider_maga.sql'

with open(sql_file, 'r', encoding='utf-8') as f:
    content = f.read()

# Find all CREATE TABLE statements and their columns
pattern = r'CREATE TABLE `([^`]+)` \((.*?)\) ENGINE='
tables = re.findall(pattern, content, re.DOTALL)

for table_name, schema in tables:
    print(f"--- {table_name} ---")
    lines = schema.strip().split('\n')
    for line in lines[:5]:
        print(line.strip())
    if len(lines) > 5: print("...")
    print("\n")
