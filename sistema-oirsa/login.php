<?php
session_start();
require_once 'config/database.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: modules/admin/dashboard.php');
    exit();
}

// Procesar el login si es una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, completa todos los campos'
        ]);
        exit();
    }

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ? AND activo = 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];

        echo json_encode([
            'success' => true,
            'message' => '¡Inicio de sesión exitoso!',
            'redirect' => 'modules/admin/dashboard.php'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos'
        ]);
    }

    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Oirsa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/login.css">
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Burbujas flotantes -->
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>

    <div class="login-container">
        <div class="login-box">
            <div class="glass-reflection"></div>

            <div class="login-header">
                <div class="logo">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h1 class="login-title">Bienvenido</h1>
                <p class="login-subtitle">Sistema de Gestión de Contratos - OIRSA</p>
            </div>

            <form id="loginForm" method="POST" class="login-form">
                <div class="form-group">
                    <label for="usuario">
                        Usuario
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario"
                            autocomplete="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        Contraseña
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña"
                            autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    <i class="fa-solid fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>&copy; 2026 OIRSA - Todos los derechos reservados</p>
            </div>
        </div>
    </div>

    <script src="js/login.js"></script>
</body>

</html>