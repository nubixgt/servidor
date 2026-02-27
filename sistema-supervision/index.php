<?php
// index.php
require_once 'config/config.php';

// Si está logueado, redirigir al dashboard correspondiente
if (isLoggedIn()) {
    redirectByRole();
} else {
    // Si no está logueado, redirigir al login
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}
?>