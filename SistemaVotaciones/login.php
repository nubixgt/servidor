<?php
// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizar($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
            $stmt->execute([$username]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                $_SESSION['login_time'] = time();
                
                // Actualizar último acceso
                $stmt = $db->prepare("UPDATE usuarios SET fecha_ultimo_acceso = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                // Registrar log de acceso
                $stmt = $db->prepare("INSERT INTO log_accesos (usuario_id, ip_address, user_agent) VALUES (?, ?, ?)");
                $stmt->execute([
                    $usuario['id'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                
                // Guardar sesión activa
                $stmt = $db->prepare("INSERT INTO sesiones_activas (usuario_id, session_id, ip_address) VALUES (?, ?, ?)");
                $stmt->execute([
                    $usuario['id'],
                    session_id(),
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (Exception $e) {
            $error = 'Error al iniciar sesión. Por favor intente nuevamente.';
            error_log('Error login: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Votaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --dark-gradient: linear-gradient(180deg, #1B5BA8 0%, #0F3A6B 100%);
            --primary: #1B5BA8;
            --primary-light: #3E7BC4;
            --dark: #0F3A6B;
            --accent: #2E6BA8;
            --text-light: #f8fafc;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        html, body {
            height: 100%;
            width: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--dark-gradient);
            overflow: hidden;
        }

        /* ==================== BACKGROUND ANIMADO ==================== */
        .login-container {
            position: relative;
            height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Elementos flotantes de fondo */
        .bg-element {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }

        .bg-element-1 {
            width: 400px;
            height: 400px;
            background: rgba(62, 123, 196, 0.3);
            top: -200px;
            left: -200px;
            animation-delay: 0s;
        }

        .bg-element-2 {
            width: 350px;
            height: 350px;
            background: rgba(27, 91, 168, 0.3);
            bottom: -175px;
            right: -175px;
            animation-delay: 2s;
            animation-direction: reverse;
        }

        .bg-element-3 {
            width: 300px;
            height: 300px;
            background: rgba(62, 123, 196, 0.25);
            top: 50%;
            left: 10%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) translateX(0px) scale(1);
            }
            50% {
                transform: translateY(-40px) translateX(30px) scale(1.1);
            }
        }

        /* Líneas animadas */
        .animated-line {
            position: absolute;
            background: linear-gradient(90deg, transparent, rgba(62, 123, 196, 0.4), transparent);
            pointer-events: none;
        }

        .line-1 {
            width: 300px;
            height: 1px;
            top: 30%;
            left: -100px;
            animation: slideRight 8s ease-in-out infinite;
        }

        .line-2 {
            width: 250px;
            height: 1px;
            bottom: 40%;
            right: -100px;
            animation: slideLeft 10s ease-in-out infinite;
        }

        @keyframes slideRight {
            0% { left: -100px; opacity: 0; }
            50% { opacity: 1; }
            100% { left: 100vw; opacity: 0; }
        }

        @keyframes slideLeft {
            0% { right: -100px; opacity: 0; }
            50% { opacity: 1; }
            100% { right: 100vw; opacity: 0; }
        }

        /* ==================== CARD LOGIN ==================== */
        .login-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2rem;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3), 
                        0 0 1px rgba(255, 255, 255, 0.5) inset;
            animation: slideUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: perspective(1000px) rotateX(0deg);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) perspective(1000px) rotateX(10deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) perspective(1000px) rotateX(0deg);
            }
        }

        /* ==================== HEADER ==================== */
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }

        .logo-box {
            width: 110px;
            height: 110px;
            margin: 0 auto 1.25rem;
            background: rgba(62, 123, 196, 0.15);
            border: 2px solid rgba(62, 123, 196, 0.4);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 40px rgba(27, 91, 168, 0.25),
                        0 0 20px rgba(62, 123, 196, 0.2) inset;
            animation: float 5s ease-in-out infinite;
            position: relative;
            overflow: hidden;
            padding: 10px;
            backdrop-filter: blur(10px);
        }

        .logo-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(62, 123, 196, 0.4) 0%, transparent 70%);
            animation: rotate 8s linear infinite;
            z-index: 0;
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            z-index: 1;
            position: relative;
            filter: drop-shadow(0 4px 12px rgba(27, 91, 168, 0.3));
        }

        .logo-box i {
            font-size: 2.5rem;
            color: white;
            z-index: 1;
            position: relative;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-header h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .login-header p {
            font-size: 0.95rem;
            color: #64748b;
            margin: 0;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ==================== ALERT ==================== */
        .alert-box {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: shake 0.5s ease-in-out;
            border-left: 4px solid rgba(255, 255, 255, 0.5);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-box i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* ==================== FORM GROUPS ==================== */
        .form-group {
            margin-bottom: 1.25rem;
            animation: fadeInUp 0.6s ease-out both;
        }

        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.5s; }

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
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
        }

        .form-input::placeholder {
            color: #cbd5e1;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 3px rgba(27, 91, 168, 0.1),
                        0 0 20px rgba(27, 91, 168, 0.2);
            transform: translateY(-2px);
        }

        .form-input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus + .form-input-icon {
            color: var(--primary-light);
            transform: translateY(-50%) scale(1.1);
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 0;
        }

        .toggle-password:hover {
            color: var(--primary);
            transform: translateY(-50%) scale(1.15);
        }

        /* ==================== OPCIONES ==================== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            animation: fadeInUp 0.6s ease-out 0.6s both;
        }

        .remember-check {
            display: flex;
            align-items: center;
            cursor: pointer;
            gap: 0.5rem;
            user-select: none;
        }

        .remember-check input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .remember-check span {
            color: #475569;
            font-weight: 500;
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        /* ==================== BOTÓN LOGIN ==================== */
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--dark-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px rgba(27, 91, 168, 0.4);
            animation: fadeInUp 0.6s ease-out 0.7s both;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-family: 'Poppins', sans-serif;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .btn-login:hover::before {
            transform: translateX(100%);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(27, 91, 168, 0.5);
            background: linear-gradient(180deg, #2E6BA8 0%, #0F3A6B 100%);
        }

        .btn-login:active {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(27, 91, 168, 0.3);
        }

        .btn-login.loading {
            opacity: 0.8;
            cursor: not-allowed;
        }

        .btn-login.loading .btn-text {
            opacity: 0;
        }

        .spinner {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-text {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* ==================== DIVIDER ==================== */
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            animation: fadeInUp 0.6s ease-out 0.8s both;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .divider-text {
            padding: 0 1rem;
            font-size: 0.85rem;
            color: #94a3b8;
            white-space: nowrap;
        }

        /* ==================== FOOTER ==================== */
        .login-footer {
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
            animation: fadeInUp 0.6s ease-out 0.9s both;
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 800px) {
            .login-card {
                max-width: 85%;
                padding: 2.5rem 2rem;
                margin: 0 auto;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }

            .logo-box {
                width: 95px;
                height: 95px;
            }
        }

        @media (max-width: 600px) {
            .login-card {
                max-width: 92%;
                padding: 1.75rem 1.25rem;
                margin: 0 auto;
                border-radius: 20px;
            }

            .login-header h2 {
                font-size: 1.3rem;
                margin-bottom: 0.3rem;
            }

            .login-header p {
                font-size: 0.8rem;
            }

            .logo-box {
                width: 80px;
                height: 80px;
                margin: 0 auto 1rem;
            }

            .logo-box i {
                font-size: 2rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-label {
                font-size: 0.8rem;
                margin-bottom: 0.4rem;
            }

            .form-input {
                padding: 0.65rem 0.875rem 0.65rem 2.25rem;
                font-size: 0.85rem;
            }

            .form-options {
                font-size: 0.8rem;
                margin-bottom: 1.25rem;
                gap: 0.5rem;
            }

            .btn-login {
                padding: 0.85rem;
                font-size: 0.9rem;
                gap: 0.5rem;
            }

            .divider {
                margin: 1.25rem 0;
            }

            .divider-text {
                font-size: 0.75rem;
                padding: 0 0.5rem;
            }

            .login-footer {
                font-size: 0.8rem;
            }

            .bg-element {
                display: none;
            }

            .animated-line {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                max-width: 95%;
                padding: 1.5rem 1.25rem;
                margin: 0 auto;
                border-radius: 16px;
            }

            .login-header h2 {
                font-size: 1.25rem;
                margin-bottom: 0.4rem;
            }

            .login-header p {
                font-size: 0.8rem;
            }

            .logo-box {
                width: 75px;
                height: 75px;
                margin: 0 auto 1rem;
            }

            .form-label {
                font-size: 0.8rem;
                margin-bottom: 0.5rem;
            }

            .form-input {
                padding: 0.7rem 0.875rem 0.7rem 2.25rem;
                font-size: 0.85rem;
                border-radius: 10px;
            }

            .form-input-icon {
                left: 0.75rem;
                font-size: 1rem;
            }

            .toggle-password {
                right: 0.75rem;
                font-size: 0.9rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-options {
                flex-direction: column;
                gap: 0.75rem;
                margin-bottom: 1.25rem;
                font-size: 0.8rem;
            }

            .remember-check {
                gap: 0.4rem;
            }

            .remember-check input {
                width: 16px;
                height: 16px;
            }

            .remember-check span {
                font-size: 0.8rem;
            }

            .forgot-link {
                font-size: 0.8rem;
            }

            .btn-login {
                padding: 0.85rem;
                font-size: 0.9rem;
                gap: 0.5rem;
            }

            .btn-text {
                gap: 0.4rem;
            }

            .btn-text i {
                font-size: 0.9rem;
            }

            .divider {
                margin: 1.25rem 0;
            }

            .divider-line {
                height: 1px;
            }

            .divider-text {
                font-size: 0.75rem;
                padding: 0 0.5rem;
            }

            .divider-text i {
                font-size: 0.8rem;
            }

            .login-footer {
                font-size: 0.8rem;
            }

            .login-footer a {
                font-size: 0.8rem;
            }

            .alert-box {
                padding: 0.875rem;
                font-size: 0.85rem;
                border-radius: 10px;
                margin-bottom: 1.25rem;
            }

            .alert-box i {
                font-size: 1.1rem;
                min-width: 20px;
            }
        }

        @media (max-width: 380px) {
            .login-card {
                max-width: 97%;
                padding: 1.25rem 1rem;
                margin: 0 auto;
            }

            .login-header h2 {
                font-size: 1.1rem;
            }

            .logo-box {
                width: 65px;
                height: 65px;
            }

            .form-input {
                padding: 0.65rem 0.75rem 0.65rem 2rem;
                font-size: 0.8rem;
            }

            .btn-login {
                padding: 0.8rem;
                font-size: 0.85rem;
            }
        }

        /* ==================== UTILIDADES ==================== */
        .text-center {
            text-align: center;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Elementos de fondo animados -->
        <div class="bg-element bg-element-1"></div>
        <div class="bg-element bg-element-2"></div>
        <div class="bg-element bg-element-3"></div>
        <div class="animated-line line-1"></div>
        <div class="animated-line line-2"></div>

        <!-- Card de Login -->
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-box">
                    <img src="logo-congreso.jpg" alt="Logo Congreso" class="logo-img">
                </div>
                <h2>Bienvenido</h2>
                <p>Sistema de Votaciones - Congreso</p>
            </div>

            <!-- Alert de Error -->
            <?php if ($error): ?>
                <div class="alert-box">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form method="POST" action="" class="login-form">
                <!-- Usuario -->
                <div class="form-group">
                    <label class="form-label">Usuario o Email</label>
                    <div class="form-input-wrapper">
                        <i class="bi bi-person-fill form-input-icon"></i>
                        <input 
                            type="text" 
                            name="username" 
                            class="form-input" 
                            placeholder="usuario@congreso.gob"
                            required 
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <div class="form-input-wrapper">
                        <i class="bi bi-lock-fill form-input-icon"></i>
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-input" 
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            class="toggle-password" 
                            onclick="togglePassword()"
                            title="Mostrar/Ocultar contraseña"
                        >
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="form-options">
                    <label class="remember-check">
                        <input type="checkbox" name="remember">
                        <span>Recordarme</span>
                    </label>
                    <a href="#" class="forgot-link">¿Olvidó su contraseña?</a>
                </div>

                <!-- Botón Login -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <div class="spinner"></div>
                    <span class="btn-text">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Iniciar Sesión
                    </span>
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <div class="divider-line"></div>
                <div class="divider-text">
                    <i class="bi bi-shield-check"></i> Acceso seguro
                </div>
                <div class="divider-line"></div>
            </div>

            <!-- Footer -->
            <div class="login-footer">
                ¿No tiene una cuenta? <a href="#">Contacte al administrador</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle de contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye-fill');
                icon.classList.add('bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash-fill');
                icon.classList.add('bi-eye-fill');
            }
        }

        // Efecto de loading en el botón
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Validación en tiempo real
        const usernameInput = document.querySelector('input[name="username"]');
        const passwordInput = document.getElementById('password');

        usernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                passwordInput.focus();
            }
        });

        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('.login-form').submit();
            }
        });

        // Animación de entrada suave
        window.addEventListener('load', function() {
            document.body.style.opacity = '1';
        });
    </script>
</body>
</html>