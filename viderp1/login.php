<?php
/**
 * VIDER - Sistema de Login
 * MAGA Guatemala - Responsive corregido
 */
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = $_GET['error'] ?? '';
$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Iniciar Sesión | VIDER - MAGA Guatemala</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a90d9;
            --accent: #4ade80;
            --accent-dark: #22c55e;
            --bg-dark: #0a1628;
            --bg-darker: #050d1a;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --error: #ef4444;
            --success: #22c55e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100%;
            background: var(--bg-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            /* CORREGIDO: Permitir scroll */
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem;
        }

        .bg-gradient {
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(74, 144, 217, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(74, 222, 128, 0.1) 0%, transparent 50%),
                linear-gradient(180deg, var(--bg-darker) 0%, var(--bg-dark) 50%, var(--bg-darker) 100%);
            z-index: 0;
            pointer-events: none;
        }

        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
            animation: float 20s infinite ease-in-out;
        }

        .particle:nth-child(1) {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(74, 144, 217, 0.2) 0%, transparent 70%);
            top: -150px;
            left: -150px;
        }

        .particle:nth-child(2) {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(74, 222, 128, 0.15) 0%, transparent 70%);
            bottom: -150px;
            right: -150px;
            animation-delay: -7s;
        }

        .particle:nth-child(3) {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(74, 144, 217, 0.15) 0%, transparent 70%);
            top: 40%;
            right: 5%;
            animation-delay: -12s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            25% { transform: translate(15px, -15px); }
            50% { transform: translate(-10px, 15px); }
            75% { transform: translate(-15px, -10px); }
        }

        .shapes {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 1;
        }

        .shape {
            position: absolute;
            opacity: 0.25;
            animation: shapeFloat 8s ease-in-out infinite;
        }

        .shape.triangle {
            width: 0;
            height: 0;
            border-left: 25px solid transparent;
            border-right: 25px solid transparent;
            border-bottom: 45px solid var(--primary);
        }

        .shape:nth-child(1) { left: 8%; top: 35%; }
        .shape:nth-child(2) { right: 12%; bottom: 25%; border-bottom-color: var(--accent); animation-delay: -3s; }

        .shape.circle {
            width: 18px;
            height: 18px;
            border: 3px solid var(--accent);
            border-radius: 50%;
        }

        .shape:nth-child(3) { right: 18%; top: 18%; animation-delay: -5s; }
        .shape:nth-child(4) { left: 12%; bottom: 18%; animation-delay: -2s; }

        @keyframes shapeFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(180deg); }
        }

        /* Contenedor principal */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px;
            margin: auto;
        }

        .login-card {
            background: rgba(15, 25, 45, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(74, 144, 217, 0.2);
            border-radius: 20px;
            padding: 2rem 1.75rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 80px rgba(74, 144, 217, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 75px;
            height: 75px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 18px;
            margin-bottom: 1rem;
            box-shadow: 0 10px 35px rgba(74, 144, 217, 0.4);
        }

        .logo-container i {
            font-size: 2rem;
            color: white;
        }

        .login-header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            letter-spacing: 2px;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .alert {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper > i:first-child {
            position: absolute;
            left: 1rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.65rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all 0.25s ease;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(74, 144, 217, 0.1);
            box-shadow: 0 0 0 3px rgba(74, 144, 217, 0.15);
        }

        .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }

        .toggle-password {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
        }

        .toggle-password:hover { color: var(--primary); }

        .form-options {
            display: flex;
            align-items: center;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .checkbox-wrapper input { display: none; }

        .checkbox-custom {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .checkbox-custom i {
            font-size: 0.65rem;
            color: white;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s ease;
        }

        .checkbox-wrapper input:checked + .checkbox-custom {
            background: var(--accent);
            border-color: var(--accent);
        }

        .checkbox-wrapper input:checked + .checkbox-custom i {
            opacity: 1;
            transform: scale(1);
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, var(--accent-dark), var(--accent));
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.25s ease;
            position: relative;
            margin-top: 0.25rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        .btn-login.loading { pointer-events: none; }
        .btn-login.loading .btn-text,
        .btn-login.loading > i { opacity: 0; }

        .btn-login .spinner {
            position: absolute;
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            opacity: 0;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner { opacity: 1; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .login-footer {
            margin-top: 1.35rem;
            padding-top: 1.15rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .login-footer p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.65rem;
        }

        .maga-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            background: rgba(74, 144, 217, 0.1);
            border: 1px solid rgba(74, 144, 217, 0.2);
            border-radius: 16px;
            font-size: 0.7rem;
            color: var(--primary);
        }

        .capslock-warning {
            display: none;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.4rem;
            padding: 0.4rem 0.65rem;
            background: rgba(251, 191, 36, 0.15);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 6px;
            font-size: 0.75rem;
            color: #fcd34d;
        }

        .capslock-warning.show { display: flex; }

        .input-wrapper input.invalid {
            border-color: var(--error);
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            body { padding: 0.75rem; }

            .login-card {
                padding: 1.5rem 1.25rem;
                border-radius: 16px;
            }

            .logo-container {
                width: 65px;
                height: 65px;
                border-radius: 14px;
            }

            .logo-container i { font-size: 1.65rem; }

            .login-header h1 { font-size: 1.6rem; }
            .login-header p { font-size: 0.8rem; }
            .login-header { margin-bottom: 1.25rem; }

            .login-form { gap: 1rem; }

            .input-wrapper input {
                padding: 0.7rem 0.9rem 0.7rem 2.4rem;
                font-size: 0.85rem;
            }

            .btn-login { padding: 0.8rem; }

            .login-footer {
                margin-top: 1.15rem;
                padding-top: 1rem;
            }

            .particles, .shapes { display: none; }
        }

        @media (max-height: 650px) {
            .login-header { margin-bottom: 1rem; }
            .logo-container {
                width: 60px;
                height: 60px;
                margin-bottom: 0.65rem;
            }
            .login-header h1 { font-size: 1.5rem; }
            .login-form { gap: 0.9rem; }
            .login-footer { margin-top: 1rem; padding-top: 0.85rem; }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="shapes">
        <div class="shape triangle"></div>
        <div class="shape triangle"></div>
        <div class="shape circle"></div>
        <div class="shape circle"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <i class="fas fa-seedling"></i>
                </div>
                <h1>VIDER</h1>
                <p>Sistema de Desarrollo Económico Rural</p>
            </div>

            <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($mensaje) ?></span>
            </div>
            <?php endif; ?>

            <form class="login-form" id="loginForm" action="api/auth_login.php" method="POST">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Ingrese su usuario" autocomplete="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <div class="capslock-warning" id="capslockWarning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Bloq Mayús activado</span>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember">
                        <div class="checkbox-custom"><i class="fas fa-check"></i></div>
                        <span>Recordarme</span>
                    </label>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="btn-text">Iniciar Sesión</span>
                    <i class="fas fa-arrow-right"></i>
                    <div class="spinner"></div>
                </button>
            </form>

            <div class="login-footer">
                <p>Ministerio de Agricultura, Ganadería y Alimentación</p>
                <div class="maga-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Acceso Seguro</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const p = document.getElementById('password');
            const i = document.getElementById('toggleIcon');
            if (p.type === 'password') {
                p.type = 'text';
                i.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                p.type = 'password';
                i.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('password').addEventListener('keyup', function(e) {
            document.getElementById('capslockWarning').classList.toggle('show', e.getModifierState('CapsLock'));
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnLogin');
            const u = document.getElementById('username').value.trim();
            const p = document.getElementById('password').value.trim();

            if (!u || !p) {
                e.preventDefault();
                if (!u) document.getElementById('username').classList.add('invalid');
                if (!p) document.getElementById('password').classList.add('invalid');
                return;
            }
            btn.classList.add('loading');
        });

        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('invalid');
            });
        });
    </script>
</body>
</html>