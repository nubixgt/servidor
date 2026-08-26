<?php
// backend_movil/api/auth/verify.php
// Endpoint para verificar token y obtener datos del usuario

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

    // Obtener datos actualizados del usuario
    $query = "SELECT 
                id,
                NombreCompleto,
                Usuario,
                DPI,
                Telefono,
                Departamento,
                Municipio,
                Rol,
                Estado
              FROM usuarios 
              WHERE id = :id 
              LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();

    $usuarioData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe
    if (!$usuarioData) {
        Response::unauthorized('Usuario no encontrado');
    }

    // Verificar estado del usuario
    if ($usuarioData['Estado'] !== 'Activo') {
        Response::forbidden('Usuario ' . strtolower($usuarioData['Estado']));
    }

    // Verificar rol
    if ($usuarioData['Rol'] !== 'Tecnico') {
        Response::forbidden('Acceso denegado');
    }

    // Preparar respuesta
    $response = [
        'usuario' => [
            'id' => (int) $usuarioData['id'],
            'nombreCompleto' => $usuarioData['NombreCompleto'],
            'usuario' => $usuarioData['Usuario'],
            'dpi' => $usuarioData['DPI'],
            'telefono' => $usuarioData['Telefono'],
            'departamento' => $usuarioData['Departamento'],
            'municipio' => $usuarioData['Municipio'],
            'rol' => $usuarioData['Rol'],
            'estado' => $usuarioData['Estado']
        ]
    ];

    Response::success($response, 'Token válido');

} catch (PDOException $e) {
    error_log("Error en verify: " . $e->getMessage());
    Response::serverError('Error al procesar la solicitud');
} catch (Exception $e) {
    error_log("Error en verify: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>