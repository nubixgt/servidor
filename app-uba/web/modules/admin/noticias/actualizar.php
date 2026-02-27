<?php
// web/modules/admin/noticias/actualizar.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método no permitido';
    header("Location: index.php");
    exit;
}

// Validar ID
$id_noticia = $_POST['id_noticia'] ?? 0;

if ($id_noticia <= 0) {
    $_SESSION['error'] = 'ID de noticia inválido';
    header("Location: index.php");
    exit;
}

// Validar campos requeridos
$campos_requeridos = ['titulo', 'categoria', 'descripcion_corta', 'contenido_completo', 'fecha_publicacion', 'estado', 'prioridad'];
foreach ($campos_requeridos as $campo) {
    if (empty($_POST[$campo])) {
        $_SESSION['error'] = "El campo $campo es obligatorio";
        header("Location: editar.php?id=$id_noticia");
        exit;
    }
}

// Obtener datos del formulario
$titulo = trim($_POST['titulo']);
$categoria = $_POST['categoria'];
$descripcion_corta = trim($_POST['descripcion_corta']);
$contenido_completo = trim($_POST['contenido_completo']);
$fecha_publicacion = $_POST['fecha_publicacion'];
$estado = $_POST['estado'];
$prioridad = $_POST['prioridad'];
$imagen_actual = $_POST['imagen_actual'] ?? '';

// Validaciones adicionales
if (strlen($titulo) < 10) {
    $_SESSION['error'] = 'El título debe tener al menos 10 caracteres';
    header("Location: editar.php?id=$id_noticia");
    exit;
}

if (strlen($descripcion_corta) < 20) {
    $_SESSION['error'] = 'La descripción corta debe tener al menos 20 caracteres';
    header("Location: editar.php?id=$id_noticia");
    exit;
}

if (strlen($contenido_completo) < 50) {
    $_SESSION['error'] = 'El contenido completo debe tener al menos 50 caracteres';
    header("Location: editar.php?id=$id_noticia");
    exit;
}

// Validar categoría
$categorias_validas = ['Campaña', 'Rescate', 'Legislación', 'Alerta', 'Evento', 'Otro'];
if (!in_array($categoria, $categorias_validas)) {
    $_SESSION['error'] = 'Categoría no válida';
    header("Location: editar.php?id=$id_noticia");
    exit;
}

// Validar estado
$estados_validos = ['publicada', 'borrador', 'archivada'];
if (!in_array($estado, $estados_validos)) {
    $_SESSION['error'] = 'Estado no válido';
    header("Location: editar.php?id=$id_noticia");
    exit;
}

// Validar prioridad
$prioridades_validas = ['normal', 'importante', 'urgente'];
if (!in_array($prioridad, $prioridades_validas)) {
    $_SESSION['error'] = 'Prioridad no válida';
    header("Location: editar.php?id=$id_noticia");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Usar imagen actual por defecto
    $imagen_url = $imagen_actual;
    
    // Procesar nueva imagen si se subió
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = $_FILES['imagen']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Tipo de archivo no válido. Solo se permiten imágenes JPG, PNG o WEBP');
        }
        
        // Validar tamaño (máximo 2MB)
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($_FILES['imagen']['size'] > $max_size) {
            throw new Exception('La imagen no debe superar los 2MB');
        }
        
        // Crear directorio si no existe
        $uploadDir = __DIR__ . '/../../../../backend/uploads/noticias/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único para la imagen
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
            
            $imagen_url = 'uploads/noticias/' . $nombreArchivo;
        }
    }
    
    // Actualizar noticia en la base de datos
    $sql = "UPDATE noticias SET
            titulo = :titulo,
            categoria = :categoria,
            descripcion_corta = :descripcion_corta,
            contenido_completo = :contenido_completo,
            imagen_url = :imagen,
            fecha_publicacion = :fecha_publicacion,
            estado = :estado,
            prioridad = :prioridad
            WHERE id_noticia = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':categoria', $categoria);
    $stmt->bindParam(':descripcion_corta', $descripcion_corta);
    $stmt->bindParam(':contenido_completo', $contenido_completo);
    $stmt->bindParam(':imagen', $imagen_url);
    $stmt->bindParam(':fecha_publicacion', $fecha_publicacion);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':prioridad', $prioridad);
    $stmt->bindParam(':id', $id_noticia);
    
    if ($stmt->execute()) {
        // Confirmar transacción
        $db->commit();
        
        $_SESSION['success'] = 'Noticia actualizada exitosamente';
        header("Location: ver.php?id=$id_noticia");
    } else {
        throw new Exception('Error al actualizar la noticia');
    }
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    // Eliminar nueva imagen si se subió y hubo error
    if (isset($rutaDestino) && file_exists($rutaDestino)) {
        unlink($rutaDestino);
    }
    
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    header("Location: editar.php?id=$id_noticia");
}
exit;
?>