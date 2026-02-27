<?php
/**
 * API para crear usuario
 * Endpoint: POST /api/usuarios/crear.php
 * 
 * Body JSON:
 * {
 *   "nombre": "Juan Pérez",
 *   "usuario": "jperez",
 *   "contrasena": "password123",
 *   "rol": "vendedor",
 *   "estado": "activo"
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
if (empty($data->nombre) || empty($data->usuario) || empty($data->contrasena) || empty($data->rol)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nombre, usuario, contraseña y rol son requeridos'
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

// Validar estado (opcional, por defecto 'activo')
$estado = isset($data->estado) ? $data->estado : 'activo';
if (!in_array($estado, ['activo', 'De Baja'])) {
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

    // Verificar si el usuario ya existe
    $checkQuery = "SELECT id FROM usuarios WHERE usuario = :usuario";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':usuario', $data->usuario);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'El nombre de usuario ya existe'
        ]);
        exit();
    }

    // Hashear contraseña
    $contrasenaHash = password_hash($data->contrasena, PASSWORD_BCRYPT);

    // Insertar usuario
    $query = "INSERT INTO usuarios (nombre, usuario, contrasena, rol, estado) 
              VALUES (:nombre, :usuario, :contrasena, :rol, :estado)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':usuario', $data->usuario);
    $stmt->bindParam(':contrasena', $contrasenaHash);
    $stmt->bindParam(':rol', $data->rol);
    $stmt->bindParam(':estado', $estado);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => [
                'id' => $db->lastInsertId(),
                'nombre' => $data->nombre,
                'usuario' => $data->usuario,
                'rol' => $data->rol,
                'estado' => $estado
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear usuario'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}