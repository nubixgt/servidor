<?php
require_once 'config.php';
require_once 'auth.php';

// Requerir autenticación
requiereLogin();

// ===== PROTECCIÓN: Solo administradores pueden eliminar =====
if (!esAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para eliminar vales. Solo los administradores pueden realizar esta acción.';
    header('Location: listar_vales.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de vale no especificado';
    header("Location: listar_vales.php");
    exit();
}

$vale_id = intval($_GET['id']);

try {
    $db = getDB();
    
    // Obtener información del vale antes de eliminar
    $stmt = $db->prepare("SELECT numero_vale FROM vales WHERE id = ?");
    $stmt->execute([$vale_id]);
    $vale = $stmt->fetch();
    
    if (!$vale) {
        $_SESSION['error'] = 'Vale no encontrado';
        header("Location: listar_vales.php");
        exit();
    }
    
    // Registrar en bitácora antes de eliminar (opcional)
    $usuario = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : 'Sistema';
    $stmt = $db->prepare("
        INSERT INTO bitacora_vales (vale_id, numero_vale, usuario, accion, observacion)
        VALUES (?, ?, ?, 'ELIMINADO', 'Vale eliminado del sistema')
    ");
    $stmt->execute([$vale_id, $vale['numero_vale'], $usuario]);
    
    // Eliminar archivos de bitácora asociados (si existen)
    $stmt = $db->prepare("SELECT nombre_archivo FROM bitacora_archivos WHERE vale_id = ?");
    $stmt->execute([$vale_id]);
    $archivos = $stmt->fetchAll();
    
    foreach ($archivos as $archivo) {
        $ruta = __DIR__ . '/uploads/bitacora/' . $archivo['nombre_archivo'];
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }
    
    // Eliminar registros de archivos de bitácora
    $stmt = $db->prepare("DELETE FROM bitacora_archivos WHERE vale_id = ?");
    $stmt->execute([$vale_id]);
    
    // Eliminar registros de bitácora
    $stmt = $db->prepare("DELETE FROM bitacora_vales WHERE vale_id = ?");
    $stmt->execute([$vale_id]);
    
    // Eliminar el vale
    $stmt = $db->prepare("DELETE FROM vales WHERE id = ?");
    $stmt->execute([$vale_id]);
    
    $_SESSION['success'] = 'Vale ' . $vale['numero_vale'] . ' eliminado exitosamente';
    header("Location: listar_vales.php");
    exit();
    
} catch(Exception $e) {
    $_SESSION['error'] = 'Error al eliminar el vale: ' . $e->getMessage();
    header("Location: listar_vales.php");
    exit();
}
?>