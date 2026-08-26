<?php
// backend_movil/api/registros/listar.php
// Endpoint para listar registros climáticos del técnico autenticado

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Auth.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido. Use GET', 405);
}

try {
    // Verificar autenticación
    $userId = Auth::requireAuth();

    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    // Parámetros de paginación
    $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 50;
    $pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
    $offset = ($pagina - 1) * $limite;

    // Filtros opcionales
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;

    // Construir query con filtros
    $whereConditions = ['id_usuario = :id_usuario'];
    $params = [':id_usuario' => $userId];

    if ($fechaDesde) {
        $whereConditions[] = 'fecha_registro >= :fecha_desde';
        $params[':fecha_desde'] = $fechaDesde;
    }

    if ($fechaHasta) {
        $whereConditions[] = 'fecha_registro <= :fecha_hasta';
        $params[':fecha_hasta'] = $fechaHasta;
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Contar total de registros
    $queryCount = "SELECT COUNT(*) as total FROM clima_registros WHERE $whereClause";
    $stmtCount = $conn->prepare($queryCount);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtener registros
    $query = "SELECT 
                id,
                fecha_registro,
                latitud,
                longitud,
                direccion,
                temperatura,
                humedad,
                precipitacion,
                viento,
                categoria,
                condicion_climatica,
                desastre_natural,
                observaciones,
                fecha_creacion
              FROM clima_registros 
              WHERE $whereClause
              ORDER BY fecha_registro DESC
              LIMIT :limite OFFSET :offset";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener fotos para cada registro
    foreach ($registros as &$registro) {
        $queryFotos = "SELECT 
                        id,
                        nombre_archivo,
                        ruta_archivo,
                        orden
                       FROM clima_fotos 
                       WHERE id_registro = :id_registro
                       ORDER BY orden ASC";

        $stmtFotos = $conn->prepare($queryFotos);
        $stmtFotos->bindParam(':id_registro', $registro['id']);
        $stmtFotos->execute();

        $registro['fotografias'] = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

        // Convertir tipos de datos
        $registro['id'] = (int) $registro['id'];
        $registro['latitud'] = (float) $registro['latitud'];
        $registro['longitud'] = (float) $registro['longitud'];
        $registro['temperatura'] = $registro['temperatura'] ? (float) $registro['temperatura'] : null;
        $registro['humedad'] = $registro['humedad'] ? (float) $registro['humedad'] : null;
        $registro['precipitacion'] = $registro['precipitacion'] ? (float) $registro['precipitacion'] : null;
        $registro['viento'] = $registro['viento'] ? (float) $registro['viento'] : null;
    }

    // Calcular total de páginas
    $totalPaginas = ceil($totalRegistros / $limite);

    // Preparar respuesta
    $response = [
        'registros' => $registros,
        'total' => (int) $totalRegistros,
        'pagina' => $pagina,
        'total_paginas' => $totalPaginas,
        'limite' => $limite
    ];

    Response::success($response, 'Registros obtenidos exitosamente');

} catch (PDOException $e) {
    error_log("Error en listar registros: " . $e->getMessage());
    Response::serverError('Error al obtener los registros');
} catch (Exception $e) {
    error_log("Error en listar registros: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>