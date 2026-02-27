<?php
// login.php
session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: formulario.php');
    exit();
}

require_once 'config/database.php';

$error = '';
$mensaje_exito = '';

// Verificar si viene de logout exitoso
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $mensaje_exito = 'Sesión cerrada correctamente';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $query = "SELECT id, usuario, password, nombre_completo, activo FROM usuarios WHERE usuario = :usuario";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user['activo'] == 1) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['usuario_id'] = $user['id'];
                        $_SESSION['usuario'] = $user['usuario'];
                        $_SESSION['nombre_completo'] = $user['nombre_completo'];
                        
                        actualizarUltimoAcceso($user['id']);
                        
                        header('Location: formulario.php?login=success');
                        exit();
                    } else {
                        $error = 'Credenciales incorrectas';
                    }
                } else {
                    $error = 'Usuario inactivo. Contacta al administrador';
                }
            } else {
                $error = 'Credenciales incorrectas';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema. Intenta más tarde';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Lotificación</title>
    <link rel="stylesheet" href="css/login.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Fondo con imagen -->
    <div class="background-image"></div>
    <div class="background-overlay"></div>

    <div class="login-container">
        <div class="login-box">
            <!-- Logo Ceiba -->
            <div class="logo-wrapper">
                <img src="assets/images/Logo Ceiba-2.png" alt="Logo Ceiba" class="logo-image">
            </div>
            
            <div class="login-header">
                <h1>Sistema de Lotificación</h1>
                <p>Inicia sesión para continuar</p>
            </div>

            <form id="loginForm" method="POST" action="login.php">
                <div class="form-group">
                    <label for="usuario">
                        <span class="label-icon"></span>
                        Usuario
                    </label>
                    <input 
                        type="text" 
                        id="usuario" 
                        name="usuario" 
                        placeholder="Ingrese su usuario"
                        required
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <span class="label-icon"></span>
                        Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Ingrese su contraseña"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn-login">
                    <span class="btn-text">Iniciar Sesión</span>
                    <span class="btn-icon">→</span>
                </button>
            </form>

            <div class="login-footer">
                <p>Sistema de Gestión v1.5</p>
            </div>
        </div>
    </div>

    <script>
        // Mostrar error si existe
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error de autenticación',
                text: '<?php echo addslashes($error); ?>',
                confirmButtonColor: '#38bdf8',
                confirmButtonText: 'Entendido'
            });
        <?php endif; ?>

        // Mostrar mensaje de logout exitoso
        <?php if ($mensaje_exito): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Hasta pronto!',
                text: '<?php echo addslashes($mensaje_exito); ?>',
                timer: 2000,
                showConfirmButton: false
            });
        <?php endif; ?>
    </script>
    <script src="js/login.js"></script>
</body>
</html>