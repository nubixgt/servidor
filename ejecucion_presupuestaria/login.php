<?php
/**
 * Login - Sistema de Ejecución Presupuestaria
 * Ministerio de Agricultura, Ganadería y Alimentación
 */

require_once 'config/database.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($password, $usuario['password'])) {
                if (!$usuario['activo']) {
                    $error = 'Su cuenta está desactivada. Contacte al administrador.';
                } else {
                    // Login exitoso
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    $_SESSION['usuario_rol'] = $usuario['rol'];

                    // Actualizar último acceso
                    $stmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                    $stmt->execute([$usuario['id']]);

                    // Registrar en bitácora
                    try {
                        $stmt = $db->prepare("INSERT INTO bitacora (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, ip_address, user_agent) VALUES (?, 'usuarios', ?, 'INSERT', ?, ?, ?)");
                        $stmt->execute([
                            $usuario['id'],
                            $usuario['id'],
                            json_encode(['tipo' => 'LOGIN', 'accion' => 'Inicio de sesión']),
                            $_SERVER['REMOTE_ADDR'] ?? 'localhost',
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    } catch (Exception $e) {
                        // Continuar con el login
                    }

                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Credenciales incorrectas. Verifique su email y contraseña.';
            }
        } catch (Exception $e) {
            $error = 'Error del sistema. Intente nuevamente.';
            if (DEBUG_MODE) {
                $error .= ' (' . $e->getMessage() . ')';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iniciar Sesión - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #1a365d;
            --primary-light: #2c5282;
            --primary-dark: #0d1b2a;
            --accent: #3182ce;
            --accent-light: #63b3ed;
            --success: #38a169;
            --danger: #e53e3e;
            --white: #ffffff;
            --gray-100: #f7fafc;
            --gray-200: #edf2f7;
            --gray-400: #a0aec0;
            --gray-600: #4a5568;
            --gray-800: #1a202c;
        }

        [data-theme="dark"] {
            --primary: #7c6aef;
            --primary-light: #9f8fff;
            --primary-dark: #1e1b33;
            --accent: #a78bfa;
            --accent-light: #c4b5fd;
            --gray-100: #13111c;
            --gray-200: #1c1a29;
            --gray-400: #6b6880;
            --gray-600: #c9c6d9;
            --gray-800: #f4f3f7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow-x: hidden;
        }

        /* Fondo animado con ondas */
        .bg-waves {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,160L48,176C96,192,192,224,288,213.3C384,203,480,149,576,138.7C672,128,768,160,864,181.3C960,203,1056,213,1152,197.3C1248,181,1344,139,1392,117.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') repeat-x;
            animation: wave 25s linear infinite;
        }

        .wave:nth-child(2) {
            bottom: 10px;
            opacity: 0.5;
            animation-duration: 20s;
            animation-direction: reverse;
        }

        .wave:nth-child(3) {
            bottom: 20px;
            opacity: 0.3;
            animation-duration: 15s;
        }

        @keyframes wave {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        /* Partículas brillantes */
        .sparkles {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .sparkle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            animation: sparkle 3s ease-in-out infinite;
        }

        @keyframes sparkle {

            0%,
            100% {
                opacity: 0;
                transform: scale(0);
            }

            50% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Contenedor principal */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            animation: slideIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Card con efecto 3D */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            backdrop-filter: blur(20px);
            overflow: hidden;
            transform-style: preserve-3d;
            transition: transform 0.3s ease;
        }

        [data-theme="dark"] .login-card {
            background: rgba(28, 26, 41, 0.95);
        }

        .login-card:hover {
            transform: perspective(1000px) rotateX(2deg) translateY(-5px);
        }

        /* Header con animación de gradiente */
        .login-header {
            background: linear-gradient(-45deg, var(--primary-dark), var(--primary), var(--primary-light), var(--accent));
            background-size: 400% 400%;
            animation: gradientFlow 8s ease infinite;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        @keyframes gradientFlow {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Logo con efecto de levitación y brillo */
        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(99, 179, 237, 0.6) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            animation: glow 2s ease-in-out infinite alternate;
            z-index: 0;
        }

        @keyframes glow {
            0% {
                opacity: 0.5;
                transform: translate(-50%, -50%) scale(0.8);
            }

            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.2);
            }
        }

        .logo-img {
            position: relative;
            width: 90px;
            height: auto;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
            animation: levitate 3s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes levitate {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            25% {
                transform: translateY(-8px) rotate(2deg);
            }

            75% {
                transform: translateY(-8px) rotate(-2deg);
            }
        }

        /* Anillo orbital */
        .orbit-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 130px;
            height: 130px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: orbit 10s linear infinite;
        }

        .orbit-ring::before {
            content: '';
            position: absolute;
            top: -5px;
            left: 50%;
            width: 10px;
            height: 10px;
            background: var(--accent-light);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-light);
        }

        @keyframes orbit {
            from {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .login-header h1 {
            color: var(--white);
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.875rem;
            font-weight: 400;
        }

        /* Formulario */
        .login-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .form-group:nth-child(1) {
            animation-delay: 0.1s;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.2s;
        }

        .form-group:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--gray-200);
            border-radius: 14px;
            font-size: 1rem;
            font-family: inherit;
            background: var(--gray-100);
            color: var(--gray-800);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] .form-input {
            background: var(--gray-200);
            border-color: rgba(167, 139, 250, 0.2);
        }

        .form-input:hover {
            border-color: var(--accent);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.2);
            transform: translateY(-2px);
        }

        .form-input:focus+.input-icon,
        .input-group:focus-within .input-icon {
            color: var(--accent);
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--accent);
            transform: translateY(-50%) scale(1.2);
        }

        /* Botón con efecto shimmer */
        .btn-login {
            width: 100%;
            padding: 1rem 2rem;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: var(--white);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease forwards;
            animation-delay: 0.4s;
            opacity: 0;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(26, 54, 93, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-login i {
            transition: transform 0.3s ease;
        }

        .btn-login:hover i {
            transform: translateX(5px);
        }

        /* Alertas con animación */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: shake 0.5s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(229, 62, 62, 0.1), rgba(252, 129, 129, 0.1));
            color: var(--danger);
            border: 1px solid rgba(229, 62, 62, 0.3);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(56, 161, 105, 0.1), rgba(104, 211, 145, 0.1));
            color: var(--success);
            border: 1px solid rgba(56, 161, 105, 0.3);
        }

        /* Footer */
        .login-footer {
            padding: 1.25rem 2rem;
            background: var(--gray-100);
            border-top: 1px solid var(--gray-200);
            text-align: center;
        }

        [data-theme="dark"] .login-footer {
            background: rgba(19, 17, 28, 0.5);
        }

        .login-footer p {
            font-size: 0.75rem;
            color: var(--gray-400);
        }

        /* Theme toggle con animación */
        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 100;
            width: 50px;
            height: 50px;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(360deg) scale(1.1);
        }

        /* Loading */
        .btn-login.loading {
            pointer-events: none;
        }

        .btn-login.loading .btn-text {
            opacity: 0;
        }

        .btn-login.loading i {
            display: none;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive mejorado */
        @media (max-width: 480px) {
            body {
                padding: 0.75rem;
            }

            .login-container {
                max-width: 100%;
            }

            .login-card {
                border-radius: 20px;
            }

            .login-header {
                padding: 2rem 1.5rem;
            }

            .logo-img {
                width: 70px;
            }

            .logo-glow {
                width: 90px;
                height: 90px;
            }

            .orbit-ring {
                width: 100px;
                height: 100px;
            }

            .login-header h1 {
                font-size: 1.25rem;
            }

            .login-header p {
                font-size: 0.8rem;
            }

            .login-body {
                padding: 1.5rem;
            }

            .form-input {
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
                font-size: 16px;
                /* Previene zoom en iOS */
            }

            .btn-login {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }

            .login-footer {
                padding: 1rem 1.5rem;
            }

            .theme-toggle {
                width: 44px;
                height: 44px;
                top: 0.75rem;
                right: 0.75rem;
            }
        }

        @media (max-width: 360px) {
            .login-header {
                padding: 1.5rem 1rem;
            }

            .logo-img {
                width: 60px;
            }

            .login-header h1 {
                font-size: 1.1rem;
            }

            .login-body {
                padding: 1.25rem;
            }

            .form-group {
                margin-bottom: 1.25rem;
            }
        }

        /* Landscape mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .login-header {
                padding: 1.25rem;
            }

            .logo-container {
                margin-bottom: 0.5rem;
            }

            .logo-img {
                width: 50px;
            }

            .orbit-ring,
            .logo-glow {
                display: none;
            }

            .login-body {
                padding: 1rem 1.5rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Ondas de fondo -->
    <div class="bg-waves">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>

    <!-- Partículas brillantes -->
    <div class="sparkles" id="sparkles"></div>

    <!-- Toggle tema -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar tema">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <div class="logo-glow"></div>
                    <div class="orbit-ring"></div>
                    <img src="assets/img/maga_logo.png" alt="MAGA" class="logo-img" onerror="this.style.display='none'">
                </div>
                <h1><?= INSTITUCION_SIGLAS ?></h1>
                <p>Sistema de Ejecución Presupuestaria</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <div class="input-group">
                            <input type="email" name="email" class="form-input" placeholder="usuario@maga.gob.gt"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-input"
                                placeholder="••••••••" required autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="pwdIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="btnLogin">
                        <span class="btn-text">Iniciar Sesión</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <p>© <?= date('Y') ?> <?= INSTITUCION ?></p>
            </div>
        </div>
    </div>

    <script>
        // Crear partículas brillantes
        function createSparkles() {
            const container = document.getElementById('sparkles');
            for (let i = 0; i < 30; i++) {
                const sparkle = document.createElement('div');
                sparkle.className = 'sparkle';
                sparkle.style.left = Math.random() * 100 + '%';
                sparkle.style.top = Math.random() * 100 + '%';
                sparkle.style.animationDelay = Math.random() * 3 + 's';
                sparkle.style.animationDuration = (Math.random() * 2 + 2) + 's';
                container.appendChild(sparkle);
            }
        }
        createSparkles();

        // Toggle tema
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('themeIcon');
            const isDark = html.getAttribute('data-theme') === 'dark';

            html.setAttribute('data-theme', isDark ? 'light' : 'dark');
            icon.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
        }

        // Cargar tema guardado
        (function () {
            const saved = localStorage.getItem('theme');
            if (saved === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.getElementById('themeIcon').className = 'fas fa-sun';
            }
        })();

        // Toggle contraseña
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('pwdIcon');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        }

        // Loading en submit
        document.getElementById('loginForm').addEventListener('submit', function () {
            document.getElementById('btnLogin').classList.add('loading');
        });
    </script>
</body>

</html>