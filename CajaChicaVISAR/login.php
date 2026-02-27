<?php
require_once 'config.php';
require_once 'auth.php';

// Si ya está logueado, redirigir al sistema
if (estaLogueado()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        $resultado = verificarLogin($usuario, $password);
        
        if ($resultado['success']) {
            iniciarSesion($resultado['user']);
            header('Location: index.php');
            exit();
        } else {
            $error = $resultado['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Vales MAGA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --maga-azul-oscuro: #1e3a5f;
            --maga-azul-medio: #2a4a6f;
            --maga-cyan: #0abde3;
            --maga-cyan-claro: #48d1ff;
            --maga-cyan-oscuro: #0097c7;
            --color-primario: #1e3a5f;
            --color-acento: #0abde3;
            --color-exito: #10b981;
            --color-peligro: #ef4444;
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --bg-blanco: #ffffff;
            --sombra-grande: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --transicion: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 50%, var(--maga-cyan-oscuro) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: gradientShift 15s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            background: var(--bg-blanco);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: var(--sombra-grande);
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--color-primario) 0%, var(--maga-cyan) 50%, var(--maga-cyan-claro) 100%);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 35px;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .logo-container img {
            max-width: 320px;
            height: auto;
            transition: var(--transicion);
        }
        
        .logo-container img:hover {
            transform: scale(1.02);
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 8px;
        }
        
        .login-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 8px;
        }
        
        .login-title p {
            font-size: 14px;
            color: var(--color-texto-claro);
        }
        
        .system-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 12px;
            box-shadow: 0 4px 15px rgba(10, 189, 227, 0.3);
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--color-texto);
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-texto-claro);
            font-size: 18px;
            transition: var(--transicion);
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: var(--transicion);
            font-family: inherit;
            background: #fafafa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--maga-cyan);
            background: white;
            box-shadow: 0 0 0 4px rgba(10, 189, 227, 0.15);
        }
        
        .form-group input:focus + .icon,
        .form-group input:not(:placeholder-shown) + .icon {
            color: var(--maga-cyan);
        }
        
        .form-group input:hover {
            border-color: #cbd5e0;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-texto-claro);
            cursor: pointer;
            padding: 5px;
            font-size: 18px;
            transition: var(--transicion);
        }
        
        .password-toggle:hover {
            color: var(--maga-cyan);
        }
        
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-cyan) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transicion);
            margin-top: 10px;
            box-shadow: 0 6px 20px rgba(10, 189, 227, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:hover::before {
            width: 400px;
            height: 400px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(10, 189, 227, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            top: 50%;
            left: 50%;
            margin-left: -11px;
            margin-top: -11px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-out;
            font-size: 14px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
            color: #c53030;
            border: 2px solid #fc8181;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
            color: #047857;
            border: 2px solid #6ee7b7;
        }
        
        .alert-icon {
            font-size: 18px;
            margin-right: 12px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
            color: var(--color-texto-claro);
            font-size: 13px;
        }
        
        .footer-text a {
            color: var(--maga-cyan);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transicion);
        }
        
        .footer-text a:hover {
            color: var(--maga-cyan-oscuro);
            text-decoration: underline;
        }
        
        /* Decoración de fondo */
        .bg-decoration {
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            pointer-events: none;
        }
        
        .bg-decoration.one {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        
        .bg-decoration.two {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            animation: float 10s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, 20px); }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 25px;
            }
            
            .logo-container img {
                max-width: 100%;
            }
            
            .login-title h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Decoraciones de fondo -->
    <div class="bg-decoration one"></div>
    <div class="bg-decoration two"></div>
    
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="logo-container">
                <img src="MagaLogo.png" alt="MAGA - Ministerio de Agricultura, Ganadería y Alimentación">
            </div>
            
            <!-- Título -->
            <div class="login-title">
                <h1>Sistema de Vales de Caja Chica</h1>
                <p>VISAR - Viceministerio de Sanidad Agropecuaria</p>
                <div class="system-badge">🔐 Acceso Seguro</div>
            </div>
            
            <!-- Mensajes -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Formulario -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-wrapper">
                        <input type="text" 
                               id="usuario" 
                               name="usuario" 
                               placeholder="Ingrese su usuario"
                               autocomplete="username"
                               required
                               value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">
                        <span class="icon">👤</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Ingrese su contraseña"
                               autocomplete="current-password"
                               required>
                        <span class="icon">🔒</span>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <span id="toggleIcon">👁️</span>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="btnLogin">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="footer-text">
                <p>Ministerio de Agricultura, Ganadería y Alimentación</p>
                <p style="margin-top: 5px;">Guatemala, C.A. • <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
        
        // Loading state en submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnLogin');
            btn.classList.add('loading');
            btn.textContent = '';
        });
        
        // Focus en el primer campo
        document.getElementById('usuario').focus();
        
        // Animación de entrada para los campos
        document.querySelectorAll('.form-group').forEach((group, index) => {
            group.style.opacity = '0';
            group.style.transform = 'translateY(20px)';
            setTimeout(() => {
                group.style.transition = 'all 0.4s ease-out';
                group.style.opacity = '1';
                group.style.transform = 'translateY(0)';
            }, 300 + (index * 100));
        });
    </script>
</body>
</html>