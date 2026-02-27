<?php
// login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

// Si ya está logueado, redirigir según su rol
if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$error_type = ''; // Para identificar el tipo de error

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = cleanInput($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($usuario) || empty($contrasena)) {
        $error = 'Por favor, ingrese usuario y contraseña.';
        $error_type = 'warning';
    } else {
        try {
            $db = Database::getInstance()->getConnection();

            // Buscar usuario
            $stmt = $db->prepare("
                SELECT id, usuario, contrasena, rol, nivel_acceso, email, telefono, estado 
                FROM usuarios 
                WHERE usuario = :usuario
            ");
            $stmt->execute(['usuario' => $usuario]);
            $user = $stmt->fetch();

            if ($user) {
                // Verificar contraseña con password_verify (soporta BCRYPT)
                if (password_verify($contrasena, $user['contrasena'])) {
                    // Verificar estado
                    if ($user['estado'] !== STATUS_ACTIVE) {
                        $error = 'Su cuenta no está activa. Contacte al administrador.';
                        $error_type = 'warning';
                    } else {
                        // Login exitoso
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['usuario'] = $user['usuario'];
                        $_SESSION['rol'] = $user['rol'];
                        $_SESSION['nivel_acceso'] = $user['nivel_acceso'] ?? 'basico'; // ⭐ NUEVO
                        $_SESSION['email'] = $user['email'];

                        // Actualizar último acceso
                        $stmt = $db->prepare("UPDATE usuarios SET ultimoAcceso = NOW() WHERE id = :id");
                        $stmt->execute(['id' => $user['id']]);

                        // Guardar flag de éxito para mostrar SweetAlert antes de redirigir
                        $_SESSION['login_success'] = true;

                        // Redirigir según rol
                        redirectByRole();
                    }
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                    $error_type = 'error';
                }
            } else {
                $error = 'Usuario o contraseña incorrectos.';
                $error_type = 'error';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema. Por favor, intente más tarde.';
            $error_type = 'error';
            error_log($e->getMessage());
        }
    }
}

$pageTitle = 'Iniciar Sesión';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">

    <!-- CSS Login con Glassmorphism -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/pages/login.css">
    <?php
    // Verificar si viene de logout
    $logout_success = isset($_GET['logout']) && $_GET['logout'] === 'success';

    // ✨ NUEVO: Verificar si la sesión expiró
    $sesion_expirada = isset($_GET['sesion']) && $_GET['sesion'] === 'expirada';
    ?>
</head>

<body>
    <!-- Grid background -->
    <div class="grid-background"></div>

    <!-- Wave animations -->
    <div class="wave-container">
        <div class="wave wave1"></div>
        <div class="wave wave2"></div>
        <div class="wave wave3"></div>
    </div>

    <!-- Light beams -->
    <div class="light-beam"></div>
    <div class="light-beam"></div>
    <div class="light-beam"></div>

    <!-- Floating particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Floating shapes -->
    <div class="floating-elements">
        <div class="floating-shape shape1"></div>
        <div class="floating-shape shape2"></div>
        <div class="floating-shape shape3"></div>
    </div>

    <div class="login-container">
        <div class="logo-container">
            <div class="logo">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <h2>Bienvenido</h2>
        <p class="subtitle"><?php echo SITE_NAME; ?></p>

        <form method="POST" action="" id="loginForm">
            <div class="input-group">
                <label for="usuario">Usuario</label>
                <div class="input-wrapper">
                    <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autofocus
                        value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                    <span class="input-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="input-group">
                <label for="contrasena">Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" id="contrasena" name="contrasena" placeholder="••••••••" required>
                    <span class="input-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                </div>
            </div>

            <button type="submit" class="login-button">
                Iniciar Sesión
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>

    <!-- JavaScript Login -->
    <script src="<?php echo SITE_URL; ?>/assets/js/pages/login.js"></script>

    <?php if (!empty($error)): ?>
        <script>
            // Mostrar error con SweetAlert2
            document.addEventListener('DOMContentLoaded', function () {
                showLoginAlert('<?php echo $error_type; ?>', '<?php echo addslashes($error); ?>');
            });
        </script>
    <?php endif; ?>

    <?php if ($logout_success): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: '¡Sesión Cerrada!',
                    text: 'Has cerrado sesión correctamente. ¡Hasta pronto!',
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#10b981',
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(16, 185, 129, 0.1)',
                    customClass: {
                        popup: 'swal-glassmorphism',
                        confirmButton: 'swal-button-glass'
                    }
                }).then(() => {
                    // Limpiar parámetro de URL para evitar que se muestre nuevamente
                    const url = new URL(window.location);
                    url.searchParams.delete('logout');
                    window.history.replaceState({}, document.title, url.pathname);
                });
            });
        </script>
    <?php endif; ?>

    <?php if ($sesion_expirada): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: 'Sesión Expirada',
                    text: 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f59e0b',
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(245, 158, 11, 0.1)',
                    customClass: {
                        popup: 'swal-glassmorphism',
                        confirmButton: 'swal-button-glass'
                    }
                }).then(() => {
                    // Limpiar parámetro de URL para evitar que se muestre nuevamente
                    const url = new URL(window.location);
                    url.searchParams.delete('sesion');
                    window.history.replaceState({}, document.title, url.pathname);
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>