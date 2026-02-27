<?php
// web/modules/admin/servicios/guardar.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$servicios_ofrecidos = trim($_POST['servicios_ofrecidos'] ?? '');
$latitud = floatval($_POST['latitud'] ?? 0);
$longitud = floatval($_POST['longitud'] ?? 0);
$estado = $_POST['estado'] ?? 'activo';
$creado_por = $_SESSION['usuario_id'];

// Validaciones
if (empty($nombre) || empty($telefono) || empty($direccion) || 
    empty($servicios_ofrecidos) || $latitud == 0 || $longitud == 0) {
    $_SESSION['error'] = 'Todos los campos obligatorios deben estar completos';
    header("Location: crear.php");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Procesar imagen si se subió
    $imagen_url = null;
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['imagen']['type'];
        $fileSize = $_FILES['imagen']['size'];
        
        // Validar tipo y tamaño
        if (!in_array($fileType, $allowed)) {
            $_SESSION['error'] = 'Solo se permiten imágenes JPG, JPEG o PNG';
            header("Location: crear.php");
            exit;
        }
        
        if ($fileSize > 2 * 1024 * 1024) { // 2MB
            $_SESSION['error'] = 'La imagen no debe superar 2MB';
            header("Location: crear.php");
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
            $imagen_url = 'uploads/servicios/' . $nombreArchivo;
        }
    }
    
    // Insertar en la base de datos
    $sql = "INSERT INTO servicios_autorizados 
            (nombre_servicio, direccion, latitud, longitud, telefono, 
             servicios_ofrecidos, imagen_url, estado, creado_por) 
            VALUES 
            (:nombre, :direccion, :latitud, :longitud, :telefono, 
             :servicios, :imagen, :estado, :creado_por)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':latitud', $latitud);
    $stmt->bindParam(':longitud', $longitud);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':servicios', $servicios_ofrecidos);
    $stmt->bindParam(':imagen', $imagen_url);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':creado_por', $creado_por);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Servicio registrado exitosamente';
        header("Location: index.php");
    } else {
        $_SESSION['error'] = 'Error al guardar el servicio';
        header("Location: crear.php");
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    header("Location: crear.php");
}
exit;
?>