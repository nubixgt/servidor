<?php
require_once 'config.php';
session_start();

// Verificar parámetros
if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    $_SESSION['error'] = "Parámetros inválidos";
    header("Location: listar_vales.php");
    exit();
}

$vale_id = intval($_GET['id']);
$nuevo_estado = $_GET['estado'];

// Validar estado
if (!in_array($nuevo_estado, ['PENDIENTE', 'LIQUIDADO'])) {
    $_SESSION['error'] = "Estado inválido";
    header("Location: listar_vales.php");
    exit();
}

try {
    $db = getDB();
    
    // Verificar que el vale existe
    $stmt = $db->prepare("SELECT numero_vale FROM vales WHERE id = ?");
    $stmt->execute([$vale_id]);
    $vale = $stmt->fetch();
    
    if (!$vale) {
        $_SESSION['error'] = "Vale no encontrado";
        header("Location: listar_vales.php");
        exit();
    }
    
    // Actualizar estado
    $stmt = $db->prepare("UPDATE vales SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $vale_id]);
    
    $emoji = $nuevo_estado === 'LIQUIDADO' ? '✅' : '⏳';
    $_SESSION['success'] = "$emoji Estado del vale " . htmlspecialchars($vale['numero_vale']) . " cambiado a $nuevo_estado";
    
} catch(Exception $e) {
    $_SESSION['error'] = "Error al cambiar el estado: " . $e->getMessage();
}

header("Location: listar_vales.php");
exit();
?>