<?php
// api/actualizar_registro.php
require_once '../config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$id = $_POST['id'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$telefono_americano = trim($_POST['telefono_americano'] ?? '');
$como_se_entero = trim($_POST['como_se_entero'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');

// Validaciones - Solo nombre, apellido y como_se_entero son obligatorios
if (empty($id) || empty($nombre) || empty($apellido) || empty($como_se_entero)) {
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
    exit();
}

// Validar formato de teléfono guatemalteco si se proporcionó
if (!empty($telefono) && !preg_match('/^\d{4}-\d{4}$/', $telefono)) {
    echo json_encode(['success' => false, 'message' => 'Formato de teléfono guatemalteco inválido']);
    exit();
}

// Validar correo si se proporcionó
if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Formato de correo inválido']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Actualizar solo si es del usuario actual
    $query = "UPDATE registros 
              SET nombre = :nombre, 
                  apellido = :apellido,
                  telefono = :telefono,
                  telefono_americano = :telefono_americano,
                  como_se_entero = :como_se_entero,
                  correo = :correo, 
                  comentario = :comentario 
              WHERE id = :id AND usuario_id = :usuario_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':apellido', $apellido);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':telefono_americano', $telefono_americano);
    $stmt->bindParam(':como_se_entero', $como_se_entero);
    $stmt->bindParam(':correo', $correo);
    $stmt->bindParam(':comentario', $comentario);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Registro actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar o no tienes permisos']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
}
?>