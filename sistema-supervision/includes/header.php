<?php
// includes/header.php
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Navbar CSS según rol ✨ ACTUALIZADO -->
    <?php if (isset($_SESSION['rol'])): ?>
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/navbar_admin.css">
        <?php elseif ($_SESSION['rol'] === 'tecnico'): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/navbar_tecnico.css">
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- CSS Adicionales -->
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <?php if (strpos($css, 'http://') === 0 || strpos($css, 'https://') === 0): ?>
                <!-- CSS Externo (CDN) -->
                <link rel="stylesheet" href="<?php echo $css; ?>">
            <?php else: ?>
                <!-- CSS Local -->
                <link rel="stylesheet" href="<?php echo SITE_URL . $css; ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Ajustar el contenido principal cuando hay sidebar */
        <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'tecnico')): ?>
        body {
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 1024px) {
            body {
                margin-left: 0;
            }
        }
        <?php endif; ?>
    </style>
    
    <!-- ✨ NUEVO: SweetAlert2 JS (necesario para session-manager) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    
    <!-- ✨ NUEVO: Session Manager - Solo para usuarios logueados -->
    <?php if (isLoggedIn()): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/session-manager.js"></script>
    <?php endif; ?>
</head>
<body>