<?php
// backend_movil/api/auth/login.php
// Endpoint para iniciar sesión

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Auth.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido. Use POST', 405);
}

try {
    // Obtener datos del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error('JSON inválido');
    }

    // Validar campos requeridos
    Response::validateRequired($data, ['usuario', 'contrasena']);

    // Sanitizar datos
    $usuario = Auth::sanitize($data['usuario']);
    $contrasena = $data['contrasena']; // No sanitizar la contraseña para no alterar caracteres especiales

    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    // Buscar usuario
    $query = "SELECT 
                id,
                NombreCompleto,
                Usuario,
                Contrasena,
                DPI,
                Telefono,
                Departamento,
                Municipio,
                Rol,
                Estado
              FROM usuarios 
              WHERE Usuario = :usuario 
              LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();

    $usuarioData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe
    if (!$usuarioData) {
        Response::unauthorized('Usuario o contraseña incorrectos');
    }

    // Verificar contraseña
    $passwordValid = false;

    // Intentar verificar como hash bcrypt
    if (password_verify($contrasena, $usuarioData['Contrasena'])) {
        $passwordValid = true;
    }
    // Si falla, verificar como texto plano (para compatibilidad temporal)
    else if ($contrasena === $usuarioData['Contrasena']) {
        $passwordValid = true;

        // Actualizar a hash para próxima vez
        $hash = Auth::hashPassword($contrasena);
        $updateQuery = "UPDATE usuarios SET Contrasena = :hash WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':hash', $hash);
        $updateStmt->bindParam(':id', $usuarioData['id']);
        $updateStmt->execute();
    }

    if (!$passwordValid) {
        Response::unauthorized('Usuario o contraseña incorrectos');
    }

    // Verificar estado del usuario
    if ($usuarioData['Estado'] !== 'Activo') {
        Response::forbidden('Usuario ' . strtolower($usuarioData['Estado']) . '. Contacte al administrador.');
    }

    // Verificar rol (solo técnicos pueden usar la app móvil)
    if ($usuarioData['Rol'] !== 'Tecnico') {
        Response::forbidden('Solo usuarios con rol de Técnico pueden acceder a la aplicación móvil. Por favor, use la página web.');
    }

    // Actualizar último acceso
    $updateAccessQuery = "UPDATE usuarios SET UltimoAcceso = NOW() WHERE id = :id";
    $updateAccessStmt = $conn->prepare($updateAccessQuery);
    $updateAccessStmt->bindParam(':id', $usuarioData['id']);
    $updateAccessStmt->execute();

    // Generar token
    $token = Auth::generateToken($usuarioData['id'], $usuarioData['Usuario']);

    // Preparar respuesta (sin contraseña)
    $response = [
        'token' => $token,
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

    Response::success($response, 'Inicio de sesión exitoso');

} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    Response::serverError('Error al procesar la solicitud');
} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>