<?php
// backend/api/calificar_servicio.php

require_once '../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Leer datos JSON del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validar datos requeridos
    if (!isset($data['id_servicio']) || !isset($data['calificacion'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos requeridos: id_servicio y calificacion'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id_servicio = (int) $data['id_servicio'];
    $nueva_calificacion = (float) $data['calificacion'];

    // Validar que la calificación esté entre 1 y 5
    if ($nueva_calificacion < 1 || $nueva_calificacion > 5) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'La calificación debe estar entre 1 y 5'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Iniciar transacción
    $db->beginTransaction();

    // Obtener calificación actual y total de calificaciones
    $query = "SELECT calificacion, total_calificaciones 
              FROM servicios_autorizados 
              WHERE id_servicio = :id_servicio";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_servicio', $id_servicio, PDO::PARAM_INT);
    $stmt->execute();

    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$servicio) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Servicio no encontrado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $calificacion_actual = (float) $servicio['calificacion'];
    $total_calificaciones = (int) $servicio['total_calificaciones'];

    // Calcular nueva calificación promedio
    // Fórmula: ((calificación_actual * total) + nueva_calificación) / (total + 1)
    $suma_total = ($calificacion_actual * $total_calificaciones) + $nueva_calificacion;
    $nuevo_total = $total_calificaciones + 1;
    $nuevo_promedio = $suma_total / $nuevo_total;

    // Redondear a 1 decimal
    $nuevo_promedio = round($nuevo_promedio, 1);

    // Actualizar en la base de datos
    $update_query = "UPDATE servicios_autorizados 
                     SET calificacion = :calificacion,
                         total_calificaciones = :total_calificaciones
                     WHERE id_servicio = :id_servicio";

    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':calificacion', $nuevo_promedio, PDO::PARAM_STR);
    $update_stmt->bindParam(':total_calificaciones', $nuevo_total, PDO::PARAM_INT);
    $update_stmt->bindParam(':id_servicio', $id_servicio, PDO::PARAM_INT);
    $update_stmt->execute();

    // Confirmar transacción
    $db->commit();

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '¡Gracias por tu calificación!',
        'data' => [
            'nueva_calificacion' => (float) $nuevo_promedio,
            'total_calificaciones' => (int) $nuevo_total
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar calificación: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
