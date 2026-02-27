<?php
// backend/api/servicios.php

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

    // Query para obtener servicios autorizados activos
    $query = "SELECT 
                id_servicio,
                nombre_servicio,
                direccion,
                latitud,
                longitud,
                telefono,
                servicios_ofrecidos,
                calificacion,
                total_calificaciones,
                imagen_url,
                fecha_creacion
              FROM servicios_autorizados 
              WHERE estado = 'activo'
              ORDER BY calificacion DESC, nombre_servicio ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $servicios = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Construir URL completa para la imagen si existe
        $imagen_url = null;
        if (!empty($row['imagen_url'])) {
            // Si la ruta ya es absoluta, usarla tal cual
            if (strpos($row['imagen_url'], 'http') === 0) {
                $imagen_url = $row['imagen_url'];
            } else {
                // Construir URL completa
                $imagen_url = 'http://159.65.168.91/AppUBA/backend/' . $row['imagen_url'];
            }
        }

        $servicios[] = [
            'id_servicio' => (int) $row['id_servicio'],
            'nombre_servicio' => $row['nombre_servicio'],
            'direccion' => $row['direccion'],
            'latitud' => $row['latitud'] ? (float) $row['latitud'] : null,
            'longitud' => $row['longitud'] ? (float) $row['longitud'] : null,
            'telefono' => $row['telefono'],
            'servicios_ofrecidos' => $row['servicios_ofrecidos'],
            'calificacion' => $row['calificacion'] ? (float) $row['calificacion'] : 0.0,
            'total_calificaciones' => (int) $row['total_calificaciones'],
            'imagen_url' => $imagen_url
        ];
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $servicios,
        'total' => count($servicios)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener servicios: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
