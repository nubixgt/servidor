<?php
/**
 * Cerrar Sesión
 * MAGA - Sistema de Vales de Caja Chica
 */

require_once 'config.php';
require_once 'auth.php';

// Cerrar la sesión
cerrarSesion();

// Redirigir al login con mensaje
header('Location: login.php?logout=1');
exit();
?>