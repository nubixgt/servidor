<?php
/**
 * api_procesar.php - API para procesar PDFs uno por uno via AJAX
 * VERSION 2.0 - Corregido problema de timeout
 * 
 * CORRECCIONES PRINCIPALES:
 * - Configuración de timeout al inicio (línea 1)
 * - Sin límite de tiempo de ejecución
 * - Mejor manejo de errores
 */

// ====================================================================
// CONFIGURACIÓN CRÍTICA - DEBE ESTAR PRIMERO
// ====================================================================
@ini_set('max_execution_time', 0);  // Sin límite
@set_time_limit(0);  // Sin límite
@ini_set('max_input_time', 600);
@ini_set('memory_limit', '512M');
@ini_set('max_file_uploads', 1);

require_once 'config.php';
require_once 'procesar.php';

header('Content-Type: application/json');
header('X-Accel-Buffering: no'); // Desactivar buffering en nginx

// Deshabilitar output buffering
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 0);
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo no permitido']);
    exit;
}

try {
    // Reforzar configuración de tiempo
    @set_time_limit(0);
    
    // Validar que se envió un archivo
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'No se recibió un archivo válido';
        
        // Proporcionar más detalles del error
        if (isset($_FILES['pdf_file']['error'])) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede upload_max_filesize en php.ini',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
                UPLOAD_ERR_EXTENSION => 'Extensión de PHP detuvo la carga'
            ];
            
            $errorCode = $_FILES['pdf_file']['error'];
            if (isset($uploadErrors[$errorCode])) {
                $errorMsg .= ': ' . $uploadErrors[$errorCode];
            }
        }
        
        throw new Exception($errorMsg);
    }
    
    $archivo = $_FILES['pdf_file'];
    
    // Validar tamaño
    if ($archivo['size'] > MAX_FILE_SIZE) {
        throw new Exception('El archivo es demasiado grande (máximo 10MB)');
    }
    
    // Validar extensión
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new Exception('Solo se permiten archivos PDF');
    }
    
    // Guardar archivo temporal con nombre único
    $nombreUnico = uniqid('votacion_', true) . '.pdf';
    $rutaDestino = UPLOAD_DIR . $nombreUnico;
    
    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Log del inicio del procesamiento
    $logFile = __DIR__ . '/logs/api_procesar.log';
    $logMsg = date('Y-m-d H:i:s') . " - Procesando: " . $archivo['name'] . " (" . round($archivo['size']/1024/1024, 2) . " MB)\n";
    @file_put_contents($logFile, $logMsg, FILE_APPEND);
    
    // Procesar el PDF
    $startTime = microtime(true);
    $procesador = new ProcesadorCongreso();
    $resultado = $procesador->procesarPDF($rutaDestino);
    $executionTime = round(microtime(true) - $startTime, 2);
    
    // Agregar información adicional
    $resultado['archivo_original'] = $archivo['name'];
    $resultado['timestamp'] = date('Y-m-d H:i:s');
    $resultado['tiempo_total'] = $executionTime . 's';
    $resultado['tamano_archivo'] = round($archivo['size'] / 1024 / 1024, 2) . ' MB';
    
    // Log del resultado
    $logMsg = date('Y-m-d H:i:s') . " - Completado: " . $archivo['name'] . " - " . ($resultado['success'] ? 'OK' : 'ERROR') . " - {$executionTime}s\n";
    @file_put_contents($logFile, $logMsg, FILE_APPEND);
    
    // Opcional: Eliminar archivo temporal después de procesar
    // @unlink($rutaDestino);
    
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage(),
        'archivo_original' => isset($_FILES['pdf_file']['name']) ? $_FILES['pdf_file']['name'] : 'Desconocido',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Log del error
    $logFile = __DIR__ . '/logs/api_procesar.log';
    $logMsg = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    @file_put_contents($logFile, $logMsg, FILE_APPEND);
    
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}