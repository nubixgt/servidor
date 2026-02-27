<?php
/**
 * API para actualizar usuario
 * Endpoint: PUT /api/usuarios/actualizar.php
 * 
 * Body JSON:
 * {
 *   "id": 1,
 *   "nombre": "Juan Pérez Actualizado",
 *   "usuario": "jperez",
 *   "contrasena": "nuevapassword123",  // Opcional
 *   "rol": "admin",
 *   "estado": "activo"
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';

// Solo permitir PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Obtener datos JSON
$data = json_decode(file_get_contents("php://input"));

// Validar que el JSON sea válido
if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Datos inválidos o JSON mal formado'
    ]);
    exit();
}

// Validar datos requeridos
if (empty($data->id) || empty($data->nombre) || empty($data->usuario) || empty($data->rol) || empty($data->estado)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID, nombre, usuario, rol y estado son requeridos'
    ]);
    exit();
}

// Validar rol
if (!in_array($data->rol, ['admin', 'vendedor'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Rol inválido. Debe ser "admin" o "vendedor"'
    ]);
    exit();
}

// Validar estado
if (!in_array($data->estado, ['activo', 'De Baja'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Estado inválido. Debe ser "activo" o "De Baja"'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el usuario existe
    $checkQuery = "SELECT id FROM usuarios WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $data->id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
        exit();
    }

    // Verificar si el nombre de usuario ya existe en otro registro
    $checkUserQuery = "SELECT id FROM usuarios WHERE usuario = :usuario AND id != :id";
    $checkUserStmt = $db->prepare($checkUserQuery);
    $checkUserStmt->bindParam(':usuario', $data->usuario);
    $checkUserStmt->bindParam(':id', $data->id);
    $checkUserStmt->execute();

    if ($checkUserStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'El nombre de usuario ya existe'
        ]);
        exit();
    }

    // Preparar query de actualización
    if (!empty($data->contrasena)) {
        // Si se proporciona contraseña, actualizarla también
        $contrasenaHash = password_hash($data->contrasena, PASSWORD_BCRYPT);
        $query = "UPDATE usuarios 
                  SET nombre = :nombre, usuario = :usuario, contrasena = :contrasena, 
                      rol = :rol, estado = :estado 
                  WHERE id = :id";
    } else {
        // Si no se proporciona contraseña, no actualizarla
        $query = "UPDATE usuarios 
                  SET nombre = :nombre, usuario = :usuario, rol = :rol, estado = :estado 
                  WHERE id = :id";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':usuario', $data->usuario);
    $stmt->bindParam(':rol', $data->rol);
    $stmt->bindParam(':estado', $data->estado);

    if (!empty($data->contrasena)) {
        $stmt->bindParam(':contrasena', $contrasenaHash);
    }

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => [
                'id' => $data->id,
                'nombre' => $data->nombre,
                'usuario' => $data->usuario,
                'rol' => $data->rol,
                'estado' => $data->estado
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar usuario'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}