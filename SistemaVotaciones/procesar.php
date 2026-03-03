<?php
/**
 * PROCESADOR FINAL OPTIMIZADO - SISTEMA DE VOTACIONES CONGRESO GT
 * Versión 4.2 - CORREGIDO PROBLEMA DE EXTRACCIÓN DE TÍTULO
 * 
 * CORRECCIONES PRINCIPALES:
 * - Mejorada extracción del título del evento (múltiples líneas)
 * - Limpieza de fecha/hora del título
 * - Mejor manejo de caracteres especiales
 */

// ====================================================================
// CONFIGURACIÓN CRÍTICA DE PHP - DEBE ESTAR AL INICIO
// ====================================================================
@ini_set('max_execution_time', 0);  // Sin límite
@set_time_limit(0);  // Sin límite
@ini_set('max_input_time', 600);
@ini_set('memory_limit', '512M');
@ini_set('max_file_uploads', 100);

require_once 'config.php';

class ProcesadorCongreso {
    private $db;
    private $uploadDir;
    private $logDir;
    private $tmpDir;
    private $logFile;
    private $pythonScript;
    private $pythonCmd = null;

    public function __construct() {
        // Reforzar configuración de tiempo
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
        
        $this->db        = getDB();
        $this->uploadDir = __DIR__ . '/uploads';
        $this->logDir    = __DIR__ . '/logs';
        $this->tmpDir    = $this->logDir . '/tmp';
        
        if (!is_dir($this->logDir))    mkdir($this->logDir, 0777, true);
        if (!is_dir($this->tmpDir))    mkdir($this->tmpDir, 0777, true);
        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0777, true);
        
        $this->logFile = $this->logDir . '/procesar.log';
        $this->pythonScript = $this->tmpDir . '/extraer_pdf.py';
        
        $this->log("🔧 Procesador inicializado (v4.2 - Título corregido)");
        
        // Detectar Python disponible
        $this->detectarPython();
    }
    
    private function detectarPython() {
        // Intentar diferentes comandos de Python
        $comandos = ['python3', 'python', 'py'];
        
        foreach ($comandos as $cmd) {
            // Añadir ruta completa de Windows si es necesario
            $test = @shell_exec("$cmd --version 2>&1");
            if ($test && stripos($test, 'python') !== false) {
                $this->pythonCmd = $cmd;
                $this->log("✅ Python encontrado: $cmd (versión: " . trim($test) . ")");
                return;
            }
        }
        
        // Intentar rutas comunes de Windows
        $rutasWindows = [
            'C:\\Python312\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Python310\\python.exe',
            'C:\\Python39\\python.exe',
            'C:\\Program Files\\Python312\\python.exe',
            'C:\\Program Files\\Python311\\python.exe',
            'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
            'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
        ];
        
        foreach ($rutasWindows as $ruta) {
            if (file_exists($ruta)) {
                $test = @shell_exec("\"$ruta\" --version 2>&1");
                if ($test && stripos($test, 'python') !== false) {
                    $this->pythonCmd = "\"$ruta\"";
                    $this->log("✅ Python encontrado en: $ruta");
                    return;
                }
            }
        }
        
        $this->log("⚠️ Python no detectado automáticamente");
        $this->pythonCmd = 'python'; // Default fallback
    }

    private function log($msg) {
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        @file_put_contents($this->logFile, $linea, FILE_APPEND);
        if (php_sapi_name() === 'cli') {
            echo $linea;
        }
    }

    public function procesarPDF($ruta) {
        // Reforzar límite de tiempo al inicio de cada procesamiento
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
        
        try {
            $this->log("=== Procesando: " . basename($ruta) . " ===");

            if (!file_exists($ruta)) {
                throw new Exception("Archivo no encontrado: $ruta");
            }

            // Verificar tamaño del archivo
            $fileSize = filesize($ruta);
            $this->log("📊 Tamaño del archivo: " . round($fileSize / 1024 / 1024, 2) . " MB");

            // Crear script Python
            $this->crearScriptPython();

            // Extraer datos con timeout extendido
            $jsonFile = $this->tmpDir . '/datos_' . time() . '_' . uniqid() . '.json';
            
            // Escapar rutas para Windows
            $pythonScriptEscaped = escapeshellarg($this->pythonScript);
            $rutaEscaped = escapeshellarg($ruta);
            $jsonFileEscaped = escapeshellarg($jsonFile);
            
            $cmd = $this->pythonCmd . " $pythonScriptEscaped $rutaEscaped $jsonFileEscaped 2>&1";
            
            $this->log("🐍 Ejecutando Python: " . $this->pythonCmd);
            $this->log("📝 Comando: $cmd");
            
            $startTime = microtime(true);
            exec($cmd, $output, $code);
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $this->log("⏱️ Tiempo de ejecución Python: {$executionTime}s");
            
            if ($code !== 0) {
                $this->log("❌ Error Python (código $code): " . implode("\n", $output));
                throw new Exception("Error al ejecutar script Python: " . implode(" ", $output));
            }

            if (!file_exists($jsonFile)) {
                throw new Exception("No se generó el archivo JSON. Output: " . implode(" ", $output));
            }

            $jsonContent = file_get_contents($jsonFile);
            $datos = json_decode($jsonContent, true);
            
            @unlink($jsonFile);

            if (!$datos || !isset($datos['evento']) || !isset($datos['votos'])) {
                throw new Exception("Datos inválidos en JSON. Contenido: " . substr($jsonContent, 0, 200));
            }

            $this->log("✅ Extraídos " . count($datos['votos']) . " votos");
            $this->log("📋 Título: " . $datos['evento']['titulo']);

            // Contar por tipo
            $conteo = [];
            foreach ($datos['votos'] as $v) {
                $conteo[$v['voto']] = ($conteo[$v['voto']] ?? 0) + 1;
            }
            
            foreach ($conteo as $tipo => $cant) {
                $this->log("  - $tipo: $cant");
            }

            // Guardar en BD
            $eventoId = $this->guardarEvento($datos['evento'], basename($ruta));
            $this->guardarVotos($eventoId, $datos['votos']);
            $this->calcularResumen($eventoId);

            $mensaje = "Evento procesado: " . $datos['evento']['titulo'];
            $this->log("✅ COMPLETADO");

            return [
                'success' => true,
                'mensaje' => $mensaje,
                'total_votos' => count($datos['votos']),
                'evento_id' => $eventoId,
                'desglose_votos' => $conteo,
                'tiempo_ejecucion' => $executionTime
            ];

        } catch (Exception $e) {
            $this->log("❌ Error: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => "Error al procesar el PDF",
                'error' => $e->getMessage(),
                'total_votos' => 0
            ];
        }
    }

    private function crearScriptPython() {
        $script = <<<'PYTHON'
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import sys
import json
import pdfplumber
import re

def limpiar_titulo(texto):
    """
    Limpia el título del evento eliminando fecha/hora y espacios extra
    """
    # Eliminar fecha y hora (formato: DD-MM-YYYY HH:MM:SS o similar)
    texto = re.sub(r'\d{2}[-/]\d{2}[-/]\d{4}\s+\d{2}:\d{2}(:\d{2})?', '', texto)
    
    # Eliminar "Fecha y Hora:" y similares
    texto = re.sub(r'Fecha\s+y\s+Hora\s*:', '', texto, flags=re.I)
    
    # Eliminar "SESIÓN No. XX" si aparece al final
    texto = re.sub(r'SESI[ÓO]N\s+No\.?\s*\d+\s*$', '', texto, flags=re.I)
    
    # Eliminar saltos de línea y espacios múltiples
    texto = re.sub(r'\s+', ' ', texto)
    
    # Eliminar espacios al inicio y final
    texto = texto.strip()
    
    return texto

def extraer_titulo_evento(texto):
    """
    Extrae el título completo del evento de manera más robusta
    """
    # Buscar el patrón completo del título en múltiples líneas
    # Patrón: APROBACIÓN/ELECCIÓN/DESIGNACIÓN ... SESIÓN No. XX
    patron = r'(APROBACI[ÓO]N|ELECCI[ÓO]N|DESIGNACI[ÓO]N)\s+DE\s+[^\n]*(?:\n[^\n]*?)*?(?=\s*SESI[ÓO]N\s+No\.|\s*Fecha\s+y\s+Hora|$)'
    
    match = re.search(patron, texto, re.I | re.DOTALL)
    
    if match:
        titulo_completo = match.group(0)
        titulo_limpio = limpiar_titulo(titulo_completo)
        return titulo_limpio
    
    # Segundo intento: Buscar solo la palabra clave con contexto limitado
    patron2 = r'(APROBACI[ÓO]N|ELECCI[ÓO]N|DESIGNACI[ÓO]N)\s+DE\s+[A-ZÁÉÍÓÚÑ\s,\(\)]+(?=\s*\d{2}[-/]\d{2}[-/]\d{4}|$)'
    match2 = re.search(patron2, texto, re.I)
    
    if match2:
        titulo_completo = match2.group(0)
        titulo_limpio = limpiar_titulo(titulo_completo)
        return titulo_limpio
    
    # Tercer intento: Capturar desde EVENTO hasta antes de la tabla
    patron3 = r'EVENTO\s+DE\s+VOTACI[ÓO]N\s*#?\s*\d+\s*(.*?)(?=\s*No\.\s+NOMBRE|$)'
    match3 = re.search(patron3, texto, re.I | re.DOTALL)
    
    if match3:
        contenido = match3.group(1)
        # Buscar APROBACIÓN/ELECCIÓN/DESIGNACIÓN dentro de este contenido
        patron_tipo = r'(APROBACI[ÓO]N|ELECCI[ÓO]N|DESIGNACI[ÓO]N)[^\n]*(?:\n[^\n]*?)*?(?=\s*SESI[ÓO]N|$)'
        match_tipo = re.search(patron_tipo, contenido, re.I | re.DOTALL)
        if match_tipo:
            titulo_completo = match_tipo.group(0)
            titulo_limpio = limpiar_titulo(titulo_completo)
            return titulo_limpio
    
    return 'Sin título'

def extraer_datos(pdf_path):
    datos = {'evento': {}, 'votos': []}
    
    with pdfplumber.open(pdf_path) as pdf:
        # Extraer texto de la primera página
        first_page_text = pdf.pages[0].extract_text()
        
        # Extraer número de evento
        m = re.search(r'EVENTO\s+DE\s+VOTACI[ÓO]N\s*#?\s*(\d+)', first_page_text, re.I)
        if m:
            datos['evento']['numero'] = m.group(1)
        
        # Extraer número de sesión
        m = re.search(r'SESI[ÓO]N\s+No\.?\s*(\d+)', first_page_text, re.I)
        if m:
            datos['evento']['sesion'] = m.group(1)
        
        # Extraer fecha y hora
        m = re.search(r'(\d{2})-(\d{2})-(\d{4})\s+(\d{2}):(\d{2}):(\d{2})', first_page_text)
        if m:
            datos['evento']['fecha_hora'] = f"{m.group(3)}-{m.group(2)}-{m.group(1)} {m.group(4)}:{m.group(5)}:{m.group(6)}"
        
        # Extraer título usando la nueva función mejorada
        titulo = extraer_titulo_evento(first_page_text)
        datos['evento']['titulo'] = titulo
        
        # Extraer votos de todas las páginas
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
PYTHON;

        file_put_contents($this->pythonScript, $script);
        chmod($this->pythonScript, 0755);
    }

    private function guardarEvento($e, $archivo) {
        // Reforzar límite de tiempo
        @set_time_limit(0);
        
        $stmt = $this->db->prepare("
            INSERT INTO eventos_votacion (numero_evento, titulo, sesion_numero, fecha_hora, archivo_origen)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                titulo=VALUES(titulo), 
                fecha_hora=VALUES(fecha_hora),
                archivo_origen=VALUES(archivo_origen)
        ");
        $stmt->execute([
            $e['numero'] ?? 'N/A',
            $e['titulo'] ?? 'Sin título',
            $e['sesion'] ?? null,
            $e['fecha_hora'] ?? date('Y-m-d H:i:s'),
            $archivo
        ]);
        
        $id = $this->db->lastInsertId();
        if ($id == 0) {
            $stmt = $this->db->prepare("SELECT id FROM eventos_votacion WHERE numero_evento = ?");
            $stmt->execute([$e['numero'] ?? 'N/A']);
            $id = $stmt->fetchColumn();
        }
        
        return $id;
    }

    private function guardarCongresista($nombre) {
        $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $nombre)), 'UTF-8');
        
        $stmt = $this->db->prepare("SELECT id FROM congresistas WHERE nombre_normalizado = ?");
        $stmt->execute([$norm]);
        
        if ($r = $stmt->fetch()) {
            return $r['id'];
        }
        
        $stmt = $this->db->prepare("INSERT INTO congresistas (nombre, nombre_normalizado) VALUES (?, ?)");
        $stmt->execute([$nombre, $norm]);
        
        return $this->db->lastInsertId();
    }

    private function guardarBloque($nombre) {
        $nombreNormalizado = trim(preg_replace('/\s+/', ' ', $nombre));
        
        $stmt = $this->db->prepare("SELECT id FROM bloques WHERE UPPER(nombre) = UPPER(?)");
        $stmt->execute([$nombreNormalizado]);
        
        if ($r = $stmt->fetch()) {
            return $r['id'];
        }
        
        $stmt = $this->db->prepare("INSERT INTO bloques (nombre) VALUES (?)");
        $stmt->execute([$nombreNormalizado]);
        
        return $this->db->lastInsertId();
    }

    private function guardarVotos($eventoId, $votos) {
        // Reforzar límite de tiempo
        @set_time_limit(0);
        
        $stmt = $this->db->prepare("DELETE FROM votos WHERE evento_id = ?");
        $stmt->execute([$eventoId]);
        
        $stmt = $this->db->prepare("
            INSERT INTO votos (evento_id, congresista_id, bloque_id, voto, numero_orden)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($votos as $v) {
            $congressistaId = $this->guardarCongresista($v['nombre']);
            $bloqueId = $this->guardarBloque($v['bloque']);
            
            $stmt->execute([
                $eventoId, 
                $congressistaId, 
                $bloqueId, 
                $v['voto'], 
                $v['numero']
            ]);
        }
        
        $this->log("  💾 Guardados " . count($votos) . " votos");
    }

    private function calcularResumen($eventoId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) total,
                   SUM(voto='A FAVOR') favor,
                   SUM(voto='EN CONTRA') contra,
                   SUM(voto='AUSENTE') aus,
                   SUM(voto='LICENCIA') lic,
                   SUM(VOTO IN ('ABSTENCION', 'ABSTENCIÓN')) abs
            FROM votos WHERE evento_id=?
        ");
        $stmt->execute([$eventoId]);
        $c = $stmt->fetch();

        $res = 'PENDIENTE';
        if ($c['favor'] > $c['contra']) $res = 'APROBADO';
        elseif ($c['contra'] > $c['favor']) $res = 'RECHAZADO';
        elseif ($c['favor'] == $c['contra'] && $c['favor'] > 0) $res = 'EMPATE';

        $stmt = $this->db->prepare("
            INSERT INTO resumen_eventos (evento_id, total_votos, votos_favor, votos_contra, votos_ausentes, votos_licencia, votos_abstencion, resultado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_votos=VALUES(total_votos),
                votos_favor=VALUES(votos_favor),
                votos_contra=VALUES(votos_contra),
                votos_ausentes=VALUES(votos_ausentes),
                votos_licencia=VALUES(votos_licencia),
                votos_abstencion=VALUES(votos_abstencion),
                resultado=VALUES(resultado)
        ");
        $stmt->execute([$eventoId, $c['total'], $c['favor'], $c['contra'], $c['aus'], $c['lic'], $c['abs'], $res]);
        
        $this->log("  📊 Resumen: {$c['total']} votos - $res");
    }

    public function procesarCarpeta() {
        // Reforzar límite de tiempo
        @set_time_limit(0);
        
        $archivos = glob($this->uploadDir . '/*.pdf');
        $ok = 0;
        $total = count($archivos);
        
        $this->log("📁 Procesando $total archivos...");
        
        foreach ($archivos as $i => $f) {
            $this->log("Archivo " . ($i + 1) . "/$total");
            $r = $this->procesarPDF($f);
            if ($r['success']) $ok++;
        }
        
        $this->log("✔️ Procesados $ok/$total");
    }
}

// CLI mode
if (php_sapi_name() === 'cli') {
    $proc = new ProcesadorCongreso();
    
    if (isset($argv[1])) {
        $resultado = $proc->procesarPDF($argv[1]);
        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        $proc->procesarCarpeta();
    }
}