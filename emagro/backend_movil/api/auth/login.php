<?php
/**
 * API de Login
 * Endpoint: POST /api/auth/login.php
 * 
 * Body JSON:
 * {
 *   "usuario": "admin",
 *   "contrasena": "password"
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

// Validar que se recibieron los datos
if (empty($data->usuario) || empty($data->contrasena)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Usuario y contraseña son requeridos'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Buscar usuario
    $query = "SELECT id, nombre, usuario, contrasena, rol, estado 
              FROM usuarios 
              WHERE usuario = :usuario 
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $data->usuario);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();

        // Verificar estado del usuario
        if ($row['estado'] !== 'activo') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario dado de baja. Contacte al administrador.'
            ]);
            exit();
        }

        // Verificar contraseña
        if (password_verify($data->contrasena, $row['contrasena'])) {
            // Login exitoso
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'id' => $row['id'],
                    'nombre' => $row['nombre'],
                    'usuario' => $row['usuario'],
                    'rol' => $row['rol']
                ]
            ]);
        } else {
            // Contraseña incorrecta
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ]);
        }
    } else {
        // Usuario no encontrado
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>