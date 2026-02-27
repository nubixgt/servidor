<?php
require_once '../config/db.php';
require_once '../includes/funciones.php';

// Si ya está autenticado, redirigir al dashboard
if (estaAutenticado()) {
    redirigir('dashboard.php');
}

$error = '';
$mensaje = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = limpiarDato($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    
    if (empty($usuario) || empty($contrasena)) {
        $error = 'Por favor, complete todos los campos';
    } else {
        try {
            $pdo = obtenerConexion();
            
            $sql = "SELECT * FROM usuarios WHERE Usuario = :usuario LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario' => $usuario]);
            $usuarioDB = $stmt->fetch();
            
            // Comparación directa de contraseña (SOLO PARA PRUEBAS)
            if ($usuarioDB && $contrasena === $usuarioDB['Contrasena']) {
                // Verificar si el usuario está activo
                if ($usuarioDB['Estado'] !== 'Activo') {
                    $error = 'Su cuenta está ' . strtolower($usuarioDB['Estado']) . '. Contacte al administrador.';
                } else {
                    // Iniciar sesión
                    $_SESSION['usuario_id'] = $usuarioDB['id'];
                    $_SESSION['usuario'] = $usuarioDB['Usuario'];
                    $_SESSION['nombre_completo'] = $usuarioDB['NombreCompleto'];
                    $_SESSION['rol'] = $usuarioDB['Rol'];
                    $_SESSION['departamento'] = $usuarioDB['Departamento'];
                    $_SESSION['municipio'] = $usuarioDB['Municipio'];
                    $_SESSION['estado'] = $usuarioDB['Estado'];
                    $_SESSION['ultima_actividad'] = time();
                    
                    // Actualizar último acceso
                    $sqlUpdate = "UPDATE usuarios SET UltimoAcceso = NOW() WHERE id = :id";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->execute([':id' => $usuarioDB['id']]);
                    
                    redirigir('dashboard.php');
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error al procesar la solicitud. Intente nuevamente.';
        }
    }
}

// Verificar si hay mensajes en la URL
if (isset($_GET['mensaje'])) {
    switch ($_GET['mensaje']) {
        case 'sesion_cerrada':
            $mensaje = 'Sesión cerrada correctamente';
            break;
        case 'sesion_expirada':
            $mensaje = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'cuenta_inactiva':
            $error = 'Su cuenta está inactiva. Contacte al administrador.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema SICO GT</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Estilos del Login Premium -->
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="grid-background"></div>
    
    <div class="background-circles">
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <h2>SICO GT</h2>
            <p class="subtitle">Sistema de Control de Guatemala</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group-custom">
                <label for="usuario">Usuario</label>
                <div class="input-wrapper">
                    <input type="text" class="form-control" id="usuario" name="usuario" 
                           placeholder="Ingrese su usuario" required autofocus 
                           value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                    <span class="input-icon">
                        <i class="bi bi-person-fill"></i>
                    </span>
                </div>
            </div>

            <div class="input-group-custom">
                <label for="contrasena">Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" class="form-control" id="contrasena" name="contrasena" 
                           placeholder="Ingrese su contraseña" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Iniciar Sesión
            </button>
        </form>

        <div class="login-footer">
            <small>© 2025 Sistema SICO GT - Guatemala</small>
        </div>
    </div>

    <script>
        // Toggle para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('contrasena');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.animation = 'slideOutAlert 0.5s ease-out forwards';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Animación para ocultar alertas
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOutAlert {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-10px);
                }
            }
        `;
        document.head.appendChild(style);

        // Prevenir múltiples envíos del formulario
        const loginForm = document.querySelector('form');
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.login-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Iniciando sesión...';
            
            // Re-habilitar después de 3 segundos por si hay error
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión';
            }, 3000);
        });

        // Focus automático en el campo de usuario al cargar
        window.addEventListener('load', function() {
            document.getElementById('usuario').focus();
        });
    </script>
</body>
</html>
