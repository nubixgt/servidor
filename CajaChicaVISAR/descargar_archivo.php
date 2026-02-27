<?php
/**
 * Descarga segura de archivos de bitácora
 * Uso: descargar_archivo.php?id=123
 */

require_once 'config.php';
require_once 'auth.php';

// Requerir autenticación
requiereLogin();

$archivo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($archivo_id <= 0) {
    http_response_code(400);
    die('ID de archivo no válido');
}

try {
    $db = getDB();
    
    // Obtener información del archivo
    $stmt = $db->prepare("SELECT * FROM bitacora_archivos WHERE id = ?");
    $stmt->execute([$archivo_id]);
    $archivo = $stmt->fetch();
    
    if (!$archivo) {
        http_response_code(404);
        die('Archivo no encontrado');
    }
    
    $ruta_archivo = __DIR__ . '/uploads/bitacora/' . $archivo['nombre_archivo'];
    
    if (!file_exists($ruta_archivo)) {
        http_response_code(404);
        die('El archivo físico no existe');
    }
    
    // Determinar Content-Type
    $content_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    $content_type = $content_types[$archivo['extension']] ?? 'application/octet-stream';
    
    // Headers para descarga
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $archivo['nombre_original'] . '"');
    header('Content-Length: ' . filesize($ruta_archivo));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Enviar archivo
    readfile($ruta_archivo);
    exit();
    
} catch(Exception $e) {
    http_response_code(500);
    die('Error al descargar el archivo');
}
?>