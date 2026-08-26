<?php
// api/alertas/listar.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../utils/Response.php';

try {
    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    // Obtener parámetros opcionales
    $region = isset($_GET['region']) ? $_GET['region'] : null;

    // Construir query
    $query = "SELECT 
        id, titulo, descripcion_corta, descripcion_detallada,
        tipo_alerta, nivel_severidad, region, icono,
        fecha_emision, fecha_vigencia, estado
    FROM clima_alertas
    WHERE estado = 'Activa'
      AND fecha_vigencia >= NOW()";

    // Agregar filtro por región si se especifica
    if ($region) {
        $query .= " AND region = :region";
    }

    // Ordenar por severidad (ALTA primero) y luego por fecha
    $query .= " ORDER BY 
        FIELD(nivel_severidad, 'ALTA', 'MEDIA', 'BAJA'),
        fecha_emision DESC";

    $stmt = $conn->prepare($query);

    // Bind parameters
    if ($region) {
        $stmt->bindParam(':region', $region);
    }

    $stmt->execute();
    $clima_alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear fechas para mejor legibilidad
    foreach ($clima_alertas as &$alerta) {
        $alerta['fecha_emision'] = date('Y-m-d H:i:s', strtotime($alerta['fecha_emision']));
        $alerta['fecha_vigencia'] = date('Y-m-d H:i:s', strtotime($alerta['fecha_vigencia']));
    }

    Response::success([
        'alertas' => $alertas,
        'total' => count($alertas),
    ]);

} catch (PDOException $e) {
    error_log("Error en listar alertas: " . $e->getMessage());
    Response::error('Error al obtener alertas: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log("Error inesperado en listar alertas: " . $e->getMessage());
    Response::error('Error inesperado: ' . $e->getMessage(), 500);
}
