<?php
// backend_movil/api/registros/detalle.php
// Endpoint para obtener el detalle de un registro climático

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

    // Validar parámetro ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        Response::error('ID de registro requerido');
    }

    $registroId = (int) $_GET['id'];

    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    // Obtener registro
    $query = "SELECT 
                r.*,
                u.NombreCompleto as nombre_tecnico,
                u.Usuario as usuario_tecnico
              FROM clima_registros r
              INNER JOIN usuarios u ON r.id_usuario = u.id
              WHERE r.id = :id AND r.id_usuario = :id_usuario
              LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $registroId);
    $stmt->bindParam(':id_usuario', $userId);
    $stmt->execute();

    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si existe y pertenece al técnico
    if (!$registro) {
        Response::notFound('Registro no encontrado o no tienes permiso para verlo');
    }

    // Obtener fotografías
    $queryFotos = "SELECT 
                    id,
                    nombre_archivo,
                    ruta_archivo,
                    orden,
                    fecha_subida
                   FROM clima_fotos 
                   WHERE id_registro = :id_registro
                   ORDER BY orden ASC";

    $stmtFotos = $conn->prepare($queryFotos);
    $stmtFotos->bindParam(':id_registro', $registroId);
    $stmtFotos->execute();

    $registro['fotografias'] = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

    // Convertir tipos de datos
    $registro['id'] = (int) $registro['id'];
    $registro['id_usuario'] = (int) $registro['id_usuario'];
    $registro['latitud'] = (float) $registro['latitud'];
    $registro['longitud'] = (float) $registro['longitud'];
    $registro['temperatura'] = $registro['temperatura'] ? (float) $registro['temperatura'] : null;
    $registro['humedad'] = $registro['humedad'] ? (float) $registro['humedad'] : null;
    $registro['precipitacion'] = $registro['precipitacion'] ? (float) $registro['precipitacion'] : null;
    $registro['viento'] = $registro['viento'] ? (float) $registro['viento'] : null;
    $registro['sincronizado'] = (bool) $registro['sincronizado'];

    Response::success(['registro' => $registro], 'Registro obtenido exitosamente');

} catch (PDOException $e) {
    error_log("Error en detalle registro: " . $e->getMessage());
    Response::serverError('Error al obtener el registro');
} catch (Exception $e) {
    error_log("Error en detalle registro: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>