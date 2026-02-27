<?php
// backend/api/noticias.php

require_once '../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Query para obtener noticias publicadas
    $query = "SELECT 
                id_noticia,
                titulo,
                categoria,
                descripcion_corta,
                contenido_completo,
                imagen_url,
                DATE_FORMAT(fecha_publicacion, '%d %b %Y') as fecha_publicacion,
                prioridad,
                fecha_creacion
              FROM noticias 
              WHERE estado = 'publicada'
              ORDER BY fecha_publicacion DESC, fecha_creacion DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $noticias = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Construir URL completa para la imagen si existe
        $imagen_url = null;
        if (!empty($row['imagen_url'])) {
            // Si la ruta ya es absoluta, usarla tal cual
            if (strpos($row['imagen_url'], 'http') === 0) {
                $imagen_url = $row['imagen_url'];
            } else {
                // Construir URL completa
                $imagen_url = 'https://m.nubix.gt/app-uba/backend/' . $row['imagen_url'];
            }
        }

        $noticias[] = [
            'id_noticia' => (int) $row['id_noticia'],
            'titulo' => $row['titulo'],
            'categoria' => $row['categoria'],
            'descripcion_corta' => $row['descripcion_corta'],
            'contenido_completo' => $row['contenido_completo'],
            'imagen_url' => $imagen_url,
            'fecha_publicacion' => $row['fecha_publicacion'],
            'prioridad' => $row['prioridad']
        ];
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $noticias,
        'total' => count($noticias)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener noticias: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
