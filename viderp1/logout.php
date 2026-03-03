<?php
/**
 * Logout - VIDER
 * MAGA Guatemala
 */

require_once 'includes/auth.php';

// Cerrar sesión
logout();

// Redirigir al login con mensaje
header('Location: login.php?msg=Ha cerrado sesión correctamente');
exit;