<?php
/**
 * Página de inicio del sistema
 * Redirige al login o al dashboard según el estado de autenticación
 */

require_once 'config/db.php';
require_once 'includes/funciones.php';

// Verificar si el usuario está autenticado
if (estaAutenticado() && usuarioActivo()) {
    // Redirigir al dashboard
    header('Location: vistas/dashboard.php');
} else {
    // Redirigir al login
    header('Location: vistas/login.php');
}

exit();
?>