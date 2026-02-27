<?php
// backend/api/uploads.php
require_once '../config/database.php';

// Manejar solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Configuración de límites
$MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB por archivo
$ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$ALLOWED_DOC_TYPES = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];
$ALLOWED_MEDIA_TYPES = ['audio/mpeg', 'audio/mp3', 'video/mp4', 'video/mpeg'];

try {
    // Verificar que se haya enviado un archivo
    if (!isset($_FILES['archivo'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
        exit;
    }
    
    // Verificar el tipo de archivo (dpi, fachada, evidencia)
    if (!isset($_POST['tipo'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no especificado']);
        exit;
    }
    
    $tipo = $_POST['tipo']; // 'dpi', 'fachada', 'evidencia'
    $file = $_FILES['archivo'];
    
    // Validar errores de carga
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error al subir el archivo: ' . $file['error']]);
        exit;
    }
    
    // Validar tamaño
    if ($file['size'] > $MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (máximo 10MB)']);
        exit;
    }
    
    // Obtener información del archivo
    $fileType = mime_content_type($file['tmp_name']);
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validar tipo de archivo según categoría
    $allowed = false;
    if (in_array($fileType, $ALLOWED_IMAGE_TYPES)) {
        $allowed = true;
        $tipoArchivo = 'imagen';
    } elseif (in_array($fileType, $ALLOWED_DOC_TYPES)) {
        $allowed = true;
        $tipoArchivo = 'doc';
    } elseif (in_array($fileType, $ALLOWED_MEDIA_TYPES)) {
        $allowed = true;
        $tipoArchivo = strpos($fileType, 'audio') !== false ? 'audio' : 'video';
    }
    
    if (!$allowed) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
        exit;
    }
    
    // Determinar carpeta de destino
    switch ($tipo) {
        case 'dpi':
            $uploadDir = '../uploads/dpi/';
            break;
        case 'fachada':
            $uploadDir = '../uploads/fachadas/';
            break;
        case 'evidencia':
            $uploadDir = '../uploads/evidencias/';
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tipo de destino no válido']);
            exit;
    }
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generar nombre único para el archivo
    $nombreUnico = uniqid() . '_' . time() . '.' . $fileExtension;
    $rutaDestino = $uploadDir . $nombreUnico;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
        // Obtener tamaño en KB
        $tamanioKB = round($file['size'] / 1024, 2);
        
        // Construir URL completa del archivo
        $urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $rutaRelativa = str_replace('../', '/app-uba/backend/', $rutaDestino);
        $urlCompleta = $urlBase . $rutaRelativa;
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Archivo subido exitosamente',
            'data' => [
                'nombre_original' => $file['name'],
                'nombre_archivo' => $nombreUnico,
                'ruta_archivo' => $rutaDestino,
                'url' => $urlCompleta,
                'tipo_archivo' => $tipoArchivo,
                'tamanio_kb' => $tamanioKB,
                'extension' => $fileExtension
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>