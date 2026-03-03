<?php
/**
 * API: Subir archivo Excel
 * VIDER - MAGA Guatemala
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/config.php';

try {
    // Log de inicio
    logError('Upload iniciado', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'files' => isset($_FILES['file']) ? 'presente' : 'ausente',
        'upload_path' => UPLOAD_PATH
    ]);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
    }
    
    // Verificar que el directorio de uploads existe y es escribible
    if (!is_dir(UPLOAD_PATH)) {
        if (!@mkdir(UPLOAD_PATH, 0755, true)) {
            logError('No se pudo crear directorio de uploads: ' . UPLOAD_PATH);
            jsonResponse([
                'success' => false, 
                'message' => 'Error de configuración: No se puede crear el directorio de uploads'
            ], 500);
        }
    }
    
    if (!is_writable(UPLOAD_PATH)) {
        logError('Directorio de uploads no escribible: ' . UPLOAD_PATH);
        jsonResponse([
            'success' => false, 
            'message' => 'Error de configuración: El directorio de uploads no tiene permisos de escritura'
        ], 500);
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal en el servidor',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
        ];
        
        $errorCode = isset($_FILES['file']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
        $message = $errorMessages[$errorCode] ?? 'Error desconocido al subir archivo (código: ' . $errorCode . ')';
        
        logError('Error de upload: ' . $message, [
            'error_code' => $errorCode,
            'files_isset' => isset($_FILES['file'])
        ]);
        
        jsonResponse(['success' => false, 'message' => $message], 400);
    }
    
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    
    logError('Archivo recibido', [
        'name' => $fileName,
        'size' => $fileSize,
        'tmp' => $fileTmp
    ]);
    
    // Validar extensión
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        jsonResponse([
            'success' => false, 
            'message' => 'Formato no válido. Solo se permiten archivos: ' . implode(', ', ALLOWED_EXTENSIONS)
        ], 400);
    }
    
    // Validar tamaño
    if ($fileSize > MAX_FILE_SIZE) {
        jsonResponse([
            'success' => false,
            'message' => 'El archivo excede el tamaño máximo de 50MB'
        ], 400);
    }
    
    // Crear nombre único
    $uniqueName = 'import_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $uploadPath = UPLOAD_PATH . $uniqueName;
    
    logError('Intentando guardar archivo', ['destino' => $uploadPath]);
    
    // Mover archivo
    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        $lastError = error_get_last();
        logError('Error al mover archivo', [
            'from' => $fileTmp,
            'to' => $uploadPath,
            'error' => $lastError ? $lastError['message'] : 'desconocido'
        ]);
        jsonResponse([
            'success' => false,
            'message' => 'Error al guardar el archivo en el servidor'
        ], 500);
    }
    
    logError('Archivo guardado exitosamente', ['path' => $uploadPath]);
    
    $db = Database::getInstance();
    
    // Registrar importación
    $importId = $db->insert('importaciones', [
        'nombre_archivo' => $fileName,
        'usuario' => 'admin',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'estado' => 'procesando'
    ]);
    
    logError('Importación registrada', ['import_id' => $importId]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Archivo subido correctamente',
        'import_id' => $importId,
        'file_name' => $fileName,
        'file_path' => $uploadPath
    ]);
    
} catch (Exception $e) {
    logError('Excepción en upload: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse([
        'success' => false,
        'message' => 'Error al procesar el archivo: ' . $e->getMessage()
    ], 500);
}
