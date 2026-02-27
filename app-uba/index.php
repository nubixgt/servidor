<?php
// web/index.php
require_once 'web/config/database.php';

// Si ya tiene sesión activa, redirigir según su rol
if (isset($_SESSION['usuario_id'])) {
    $rol = $_SESSION['usuario_rol'];

    // Redirigir según el rol
    switch ($rol) {
        case 'admin':
            header('Location: web/modules/admin/dashboard.php');
            break;
        case 'tecnico_1':
            header('Location: web/modules/tecnico_1/dashboard.php');
            break;
        case 'tecnico_2':
            header('Location: web/modules/tecnico_2/dashboard.php');
            break;
        case 'tecnico_3':
            header('Location: web/modules/tecnico_3/dashboard.php');
            break;
        case 'tecnico_4':
            header('Location: web/modules/tecnico_4/dashboard.php');
            break;
        case 'tecnico_5':
            header('Location: web/modules/tecnico_5/dashboard.php');
            break;
        default:
            header('Location: login.php');
            break;
    }
    exit;
}

// Si no tiene sesión, mostrar login
header("Location: login.php");
exit;
?>