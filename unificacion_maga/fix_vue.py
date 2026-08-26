import os
import re

VUE_DIR = r"C:\xampp\htdocs\unificacion_maga\Frontend\src\views\admin\visan"

replacements = [
    (r"import axios from 'axios'", "import api from '@/services/api'"),
    (r"axios\.get\(import\.meta\.env\.VITE_API_BASE \+ '/index\.php'", "api.get('/visan/dashboard'"),
    (r"axios\.get\(import\.meta\.env\.VITE_API_BASE \+ '/tabla_datos\.php'", "api.get('/visan/tabla'"),
    (r"axios\.get\(import\.meta\.env\.VITE_API_BASE \+ '/historial_cambios\.php'", "api.get('/visan/historial'"),
    (r"axios\.post\(import\.meta\.env\.VITE_API_BASE \+ '/editar_datos\.php'", "api.post('/visan/editar'"),
    (r"axios\.get\(import\.meta\.env\.VITE_API_BASE \+ '/editar_datos\.php'", "api.get('/visan/editar'"),
    (r"axios\.post\(import\.meta\.env\.VITE_API_BASE \+ '/importar_excel\.php'", "api.post('/visan/importar'")
]

for filename in os.listdir(VUE_DIR):
    if filename.endswith(".vue"):
        path = os.path.join(VUE_DIR, filename)
        with open(path, "r", encoding="utf-8") as f:
            content = f.read()
        
        for old, new in replacements:
            content = re.sub(old, new, content)
            
        with open(path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Fixed {filename}")
