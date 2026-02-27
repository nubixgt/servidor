<?php
// web/modules/admin/servicios/eliminar.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

$id_servicio = $_GET['id'] ?? 0;

if ($id_servicio <= 0) {
    $_SESSION['error'] = 'ID de servicio inválido';
    header("Location: index.php");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener información del servicio antes de eliminarlo
    $sqlGetServicio = "SELECT imagen_url FROM servicios_autorizados WHERE id_servicio = :id";
    $stmtGet = $db->prepare($sqlGetServicio);
    $stmtGet->bindParam(':id', $id_servicio);
    $stmtGet->execute();
    
    if ($stmtGet->rowCount() == 0) {
        $_SESSION['error'] = 'Servicio no encontrado';
        header("Location: index.php");
        exit;
    }
    
    $servicio = $stmtGet->fetch();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Eliminar el servicio de la base de datos
    $sqlDelete = "DELETE FROM servicios_autorizados WHERE id_servicio = :id";
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bindParam(':id', $id_servicio);
    $stmtDelete->execute();
    
    // Eliminar imagen si existe
    if (!empty($servicio['imagen_url'])) {
        $rutaImagen = __DIR__ . '/../../../../backend/' . $servicio['imagen_url'];
        if (file_exists($rutaImagen)) {
            unlink($rutaImagen);
        }
    }
    
    // Confirmar transacción
    $db->commit();
    
    $_SESSION['success'] = 'Servicio eliminado exitosamente';
    header("Location: index.php");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['error'] = 'Error al eliminar el servicio: ' . $e->getMessage();
    header("Location: index.php");
    exit;
}
?>