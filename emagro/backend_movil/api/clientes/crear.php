<?php
/**
 * API para crear cliente
 * Endpoint: POST /api/clientes/crear.php
 * 
 * Body JSON:
 * {
 *   "nombre": "Juan Pérez",
 *   "nit": "11652646-7",
 *   "telefono": "4528-9012",
 *   "departamento": "Guatemala",
 *   "municipio": "Guatemala",
 *   "direccion": "Zona 1, 5ta Avenida",
 *   "email": "juan@example.com",
 *   "usuario_id": 1
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
if (
    empty($data->nombre) || empty($data->nit) || empty($data->telefono) ||
    empty($data->departamento) || empty($data->municipio) || empty($data->direccion) ||
    empty($data->usuario_id)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Todos los campos son requeridos excepto email'
    ]);
    exit();
}

// Validar formato de NIT (11652646-7 o CF)
if ($data->nit !== 'CF' && !preg_match('/^\d{8}-\d{1}$/', $data->nit)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Formato de NIT inválido. Debe ser XXXXXXXX-X o "CF"'
    ]);
    exit();
}

// Validar formato de teléfono (4528-9012)
if (!preg_match('/^\d{4}-\d{4}$/', $data->telefono)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Formato de teléfono inválido. Debe ser XXXX-XXXX'
    ]);
    exit();
}

// Validar email si se proporciona
if (!empty($data->email) && !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Formato de email inválido'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el NIT ya existe (excepto CF)
    if ($data->nit !== 'CF') {
        $checkQuery = "SELECT id FROM clientes WHERE nit = :nit";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':nit', $data->nit);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Ya existe un cliente con este NIT'
            ]);
            exit();
        }
    }

    // Insertar cliente
    $query = "INSERT INTO clientes (nombre, nit, telefono, departamento, municipio, direccion, email, usuario_id) 
              VALUES (:nombre, :nit, :telefono, :departamento, :municipio, :direccion, :email, :usuario_id)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':nit', $data->nit);
    $stmt->bindParam(':telefono', $data->telefono);
    $stmt->bindParam(':departamento', $data->departamento);
    $stmt->bindParam(':municipio', $data->municipio);
    $stmt->bindParam(':direccion', $data->direccion);

    $email = !empty($data->email) ? $data->email : null;
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':usuario_id', $data->usuario_id);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'data' => [
                'id' => $db->lastInsertId(),
                'nombre' => $data->nombre,
                'nit' => $data->nit,
                'telefono' => $data->telefono,
                'departamento' => $data->departamento,
                'municipio' => $data->municipio
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear cliente'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}