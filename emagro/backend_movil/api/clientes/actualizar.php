<?php
/**
 * API para actualizar cliente
 * Endpoint: PUT /api/clientes/actualizar.php
 * 
 * Body JSON:
 * {
 *   "id": 1,
 *   "nombre": "Juan Pérez Actualizado",
 *   "nit": "11652646-7",
 *   "telefono": "4528-9012",
 *   "departamento": "Guatemala",
 *   "municipio": "Guatemala",
 *   "direccion": "Zona 1, 5ta Avenida",
 *   "email": "juan@example.com",
 *   "bloquear_ventas": "no"
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

// Validar datos requeridos
if (
    empty($data->id) || empty($data->nombre) || empty($data->nit) || empty($data->telefono) ||
    empty($data->departamento) || empty($data->municipio) || empty($data->direccion)
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

// Validar bloquear_ventas
$bloquearVentas = isset($data->bloquear_ventas) ? $data->bloquear_ventas : 'no';
if (!in_array($bloquearVentas, ['si', 'no'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Bloquear ventas debe ser "si" o "no"'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el cliente existe
    $checkQuery = "SELECT id FROM clientes WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $data->id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Cliente no encontrado'
        ]);
        exit();
    }

    // Verificar si el NIT ya existe en otro cliente (excepto CF)
    if ($data->nit !== 'CF') {
        $checkNitQuery = "SELECT id FROM clientes WHERE nit = :nit AND id != :id";
        $checkNitStmt = $db->prepare($checkNitQuery);
        $checkNitStmt->bindParam(':nit', $data->nit);
        $checkNitStmt->bindParam(':id', $data->id);
        $checkNitStmt->execute();

        if ($checkNitStmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Ya existe otro cliente con este NIT'
            ]);
            exit();
        }
    }

    // Actualizar cliente
    $query = "UPDATE clientes 
              SET nombre = :nombre, nit = :nit, telefono = :telefono, 
                  departamento = :departamento, municipio = :municipio, 
                  direccion = :direccion, email = :email, bloquear_ventas = :bloquear_ventas
              WHERE id = :id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':nit', $data->nit);
    $stmt->bindParam(':telefono', $data->telefono);
    $stmt->bindParam(':departamento', $data->departamento);
    $stmt->bindParam(':municipio', $data->municipio);
    $stmt->bindParam(':direccion', $data->direccion);

    $email = !empty($data->email) ? $data->email : null;
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':bloquear_ventas', $bloquearVentas);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'data' => [
                'id' => $data->id,
                'nombre' => $data->nombre,
                'nit' => $data->nit,
                'telefono' => $data->telefono,
                'bloquear_ventas' => $bloquearVentas
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar cliente'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
