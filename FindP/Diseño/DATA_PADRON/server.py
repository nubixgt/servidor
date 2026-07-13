#!/usr/bin/env python3
import http.server
import socketserver
import json
import sqlite3
import urllib.parse
import os
import re

PORT = 8088
DB_PATH = 'padron.db'

class PadronAPIHandler(http.server.BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        # Silenciar los logs estándar para mantener limpia la consola
        pass

    def do_GET(self):
        parsed_url = urllib.parse.urlparse(self.path)
        path = parsed_url.path
        query_params = urllib.parse.parse_qs(parsed_url.query)

        # Endpoint de la API para búsquedas
        if path == '/api/search':
            self.handle_search(query_params)
            return

        # Servir archivos estáticos del frontend
        if path == '/' or path == '/index.html':
            self.serve_file('web/index.html', 'text/html')
            return
        elif path == '/index.css':
            self.serve_file('web/index.css', 'text/css')
            return
        elif path == '/index.js':
            self.serve_file('web/index.js', 'application/javascript')
            return
        
        # 404 Not Found
        self.send_error(404, "Not Found")

    def serve_file(self, filepath, content_type):
        if not os.path.exists(filepath):
            self.send_error(404, "File Not Found")
            return
        try:
            with open(filepath, 'rb') as f:
                content = f.read()
            self.send_response(200)
            self.send_header('Content-Type', content_type)
            self.send_header('Content-Length', len(content))
            self.end_headers()
            self.wfile.write(content)
        except Exception as e:
            self.send_error(500, f"Internal Server Error: {str(e)}")

    def handle_search(self, params):
        query = params.get('q', [''])[0].strip()
        
        if not query:
            self.send_json_response({"results": [], "count": 0, "status": "empty_query"})
            return

        if not os.path.exists(DB_PATH):
            self.send_json_response(
                {"error": "Base de datos no encontrada. Por favor ejecuta el script de indexación 'create_db.py' primero."}, 
                status_code=500
            )
            return

        # Limpiar y clasificar la consulta
        # Si contiene solo dígitos y espacios (ej. DPI), buscamos por DPI
        clean_query = re.sub(r'\s+', '', query)
        is_dpi = clean_query.isdigit()

        try:
            conn = sqlite3.connect(DB_PATH)
            conn.row_factory = sqlite3.Row
            cursor = conn.cursor()

            results = []
            
            if is_dpi:
                # Búsqueda exacta por DPI (limpiando espacios)
                cursor.execute(
                    "SELECT departamento, municipio, aldea, nombre, dpi, edad FROM padron WHERE dpi_clean = ? LIMIT 100", 
                    (clean_query,)
                )
                rows = cursor.fetchall()
                results = [dict(row) for row in rows]
            else:
                # Búsqueda por Nombre Completo usando FTS5
                # Normalizar la entrada para FTS5: pedro lopez -> pedro* AND lopez*
                words = [w for w in re.split(r'\s+', query) if w]
                # Escapar términos y añadir comodín para búsquedas parciales
                formatted_words = []
                for w in words:
                    # Reemplazar comillas dobles y caracteres especiales
                    clean_w = re.sub(r'[^\w]', '', w)
                    if clean_w:
                        formatted_words.append(f'"{clean_w}"*')
                
                if formatted_words:
                    fts_query = " AND ".join(formatted_words)
                    
                    # Consulta JOIN optimizada entre la tabla virtual FTS y la tabla padron
                    sql = """
                        SELECT p.departamento, p.municipio, p.aldea, p.nombre, p.dpi, p.edad 
                        FROM padron_fts f 
                        JOIN padron p ON p.id = f.rowid 
                        WHERE f.nombre MATCH ? 
                        LIMIT 100
                    """
                    cursor.execute(sql, (fts_query,))
                    rows = cursor.fetchall()
                    results = [dict(row) for row in rows]
                else:
                    results = []

            # Verificar si superamos el límite para alertar al usuario
            has_more = len(results) >= 100

            self.send_json_response({
                "results": results,
                "count": len(results),
                "has_more": has_more,
                "is_dpi": is_dpi
            })

            conn.close()

        except Exception as e:
            self.send_json_response({"error": f"Error en la base de datos: {str(e)}"}, status_code=500)

    def send_json_response(self, data, status_code=200):
        try:
            response_bytes = json.dumps(data, ensure_ascii=False).encode('utf-8')
            self.send_response(status_code)
            self.send_header('Content-Type', 'application/json; charset=utf-8')
            self.send_header('Content-Length', len(response_bytes))
            self.send_header('Access-Control-Allow-Origin', '*')  # Habilitar CORS
            self.end_headers()
            self.wfile.write(response_bytes)
        except Exception as e:
            # Fallback en caso de que falle el envío del json
            self.send_response(500)
            self.end_headers()
            self.wfile.write(str(e).encode('utf-8'))

def run_server():
    # Habilitar reutilización de dirección para evitar errores "address already in use" al reiniciar rápido
    socketserver.TCPServer.allow_reuse_address = True
    with socketserver.TCPServer(("", PORT), PadronAPIHandler) as httpd:
        print(f"\nServidor ejecutándose exitosamente.")
        print(f"-> Local: http://localhost:{PORT}")
        print(f"Presiona Ctrl+C para detener el servidor.")
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\nServidor detenido por el usuario.")

if __name__ == '__main__':
    run_server()
