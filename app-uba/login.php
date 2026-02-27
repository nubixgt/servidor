<?php
// web/login.php
require_once 'web/config/database.php';

// Si ya tiene sesión activa, redirigir
if (isset($_SESSION['usuario_id'])) {
    $rol = $_SESSION['usuario_rol'];
    header("Location: web/modules/{$rol}/dashboard.php");  // ← CORREGIDO
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM usuarios_web WHERE usuario = :usuario AND estado = 'activo'";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();

            if (password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $user['id_usuario'];
                $_SESSION['usuario_nombre'] = $user['nombre_completo'];
                $_SESSION['usuario_usuario'] = $user['usuario'];
                $_SESSION['usuario_rol'] = $user['rol'];

                // Actualizar último login
                $sqlUpdate = "UPDATE usuarios_web SET ultimo_login = NOW() WHERE id_usuario = :id";
                $stmtUpdate = $db->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':id', $user['id_usuario']);
                $stmtUpdate->execute();

                // Redirigir según rol
                header("Location: web/modules/{$user['rol']}/dashboard.php");  // ← CORREGIDO
                exit;
            } else {
                $error = 'Contraseña incorrecta';
            }
        } else {
            $error = 'Usuario no encontrado o inactivo';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AppUBA</title>
    <link rel="stylesheet" href="web/css/login.css">
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-paw"></i>
                <h1>AppUBA - MAGA</h1>
                <p>Sistema de Gestión de Denuncias</p>
            </div>

            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="usuario">
                        <i class="fas fa-user"></i> Usuario
                    </label>
                    <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required
                        autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required
                        autocomplete="current-password">
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>© 2026 MAGA - Ministerio de Agricultura, Ganadería y Alimentación</p>
            </div>
        </div>
    </div>

    <script src="web/js/login.js"></script>

    <?php if (isset($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error de inicio de sesión',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#DC2626',
                confirmButtonText: 'Intentar de nuevo'
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Sesión cerrada!',
                text: 'Has cerrado sesión exitosamente',
                confirmButtonColor: '#3b82f6',
                timer: 2500,
                timerProgressBar: true,
                showConfirmButton: true,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Limpiar el parámetro de la URL para evitar que se muestre de nuevo
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        </script>
    <?php endif; ?>
</body>

</html>