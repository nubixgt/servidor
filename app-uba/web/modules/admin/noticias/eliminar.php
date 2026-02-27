<?php
// web/modules/admin/noticias/eliminar.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

// Validar ID
$id_noticia = $_GET['id'] ?? 0;

if ($id_noticia <= 0) {
    $_SESSION['error'] = 'ID de noticia inválido';
    header("Location: index.php");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener información de la noticia antes de eliminarla
    $sqlGetNoticia = "SELECT imagen_url FROM noticias WHERE id_noticia = :id";
    $stmtGet = $db->prepare($sqlGetNoticia);
    $stmtGet->bindParam(':id', $id_noticia);
    $stmtGet->execute();
    
    if ($stmtGet->rowCount() == 0) {
        $_SESSION['error'] = 'Noticia no encontrada';
        header("Location: index.php");
        exit;
    }
    
    $noticia = $stmtGet->fetch();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Eliminar la noticia de la base de datos
    $sqlDelete = "DELETE FROM noticias WHERE id_noticia = :id";
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bindParam(':id', $id_noticia);
    $stmtDelete->execute();
    
    // Eliminar imagen si existe
    if (!empty($noticia['imagen_url'])) {
        $rutaImagen = __DIR__ . '/../../../../backend/' . $noticia['imagen_url'];
        if (file_exists($rutaImagen)) {
            unlink($rutaImagen);
        }
    }
    
    // Confirmar transacción
    $db->commit();
    
    $_SESSION['success'] = 'Noticia eliminada exitosamente';
    header("Location: index.php");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['error'] = 'Error al eliminar la noticia: ' . $e->getMessage();
    header("Location: index.php");
    exit;
}
?>