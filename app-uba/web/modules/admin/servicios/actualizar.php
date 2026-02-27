<?php
// web/modules/admin/servicios/actualizar.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Obtener datos del formulario
$id_servicio = intval($_POST['id_servicio'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$servicios_ofrecidos = trim($_POST['servicios_ofrecidos'] ?? '');
$latitud = floatval($_POST['latitud'] ?? 0);
$longitud = floatval($_POST['longitud'] ?? 0);
$estado = $_POST['estado'] ?? 'activo';
$imagen_actual = $_POST['imagen_actual'] ?? '';

// Validaciones
if ($id_servicio <= 0) {
    $_SESSION['error'] = 'ID de servicio inválido';
    header("Location: index.php");
    exit;
}

if (empty($nombre) || empty($telefono) || empty($direccion) || 
    empty($servicios_ofrecidos) || $latitud == 0 || $longitud == 0) {
    $_SESSION['error'] = 'Todos los campos obligatorios deben estar completos';
    header("Location: editar.php?id=$id_servicio");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Procesar nueva imagen si se subió
    $imagen_url = $imagen_actual;
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['imagen']['type'];
        $fileSize = $_FILES['imagen']['size'];
        
        // Validar tipo y tamaño
        if (!in_array($fileType, $allowed)) {
            $_SESSION['error'] = 'Solo se permiten imágenes JPG, JPEG o PNG';
            header("Location: editar.php?id=$id_servicio");
            exit;
        }
        
        if ($fileSize > 2 * 1024 * 1024) { // 2MB
            $_SESSION['error'] = 'La imagen no debe superar 2MB';
            header("Location: editar.php?id=$id_servicio");
            exit;
        }
        
        // Crear carpeta si no existe
        $uploadDir = __DIR__ . '/../../../../backend/uploads/servicios/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;
        
        // Mover archivo
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            // Eliminar imagen anterior si existe
            if (!empty($imagen_actual)) {
                $rutaAnterior = __DIR__ . '/../../../../backend/' . $imagen_actual;
                if (file_exists($rutaAnterior)) {
                    unlink($rutaAnterior);
                }
            }
            
            $imagen_url = 'uploads/servicios/' . $nombreArchivo;
        }
    }
    
    // Actualizar servicio
    $sqlUpdate = "UPDATE servicios_autorizados SET
                  nombre_servicio = :nombre,
                  direccion = :direccion,
                  latitud = :latitud,
                  longitud = :longitud,
                  telefono = :telefono,
                  servicios_ofrecidos = :servicios,
                  imagen_url = :imagen,
                  estado = :estado
                  WHERE id_servicio = :id";
    
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':nombre', $nombre);
    $stmtUpdate->bindParam(':direccion', $direccion);
    $stmtUpdate->bindParam(':latitud', $latitud);
    $stmtUpdate->bindParam(':longitud', $longitud);
    $stmtUpdate->bindParam(':telefono', $telefono);
    $stmtUpdate->bindParam(':servicios', $servicios_ofrecidos);
    $stmtUpdate->bindParam(':imagen', $imagen_url);
    $stmtUpdate->bindParam(':estado', $estado);
    $stmtUpdate->bindParam(':id', $id_servicio);
    $stmtUpdate->execute();
    
    // Confirmar transacción
    $db->commit();
    
    $_SESSION['success'] = 'Servicio actualizado exitosamente';
    header("Location: ver.php?id=$id_servicio");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['error'] = 'Error al actualizar el servicio: ' . $e->getMessage();
    header("Location: editar.php?id=$id_servicio");
    exit;
}
?>