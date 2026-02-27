<?php
/**
 * Setup Inicial - Sistema de Ejecución Presupuestaria
 * Crea el usuario administrador inicial
 * ELIMINAR ESTE ARCHIVO DESPUÉS DE LA CONFIGURACIÓN INICIAL
 */

require_once 'config/database.php';

$mensaje = '';
$tipo = '';

// Procesar creación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (empty($nombre) || empty($email) || empty($password)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo = 'danger';
    } elseif ($password !== $confirmar) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo = 'danger';
    } elseif (strlen($password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo = 'danger';
    } else {
        try {
            $db = getDB();

            // Verificar si ya existe el email
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // Actualizar contraseña del usuario existente
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET password = ?, nombre = ?, activo = 1 WHERE email = ?");
                $stmt->execute([$hash, $nombre, $email]);
                $mensaje = "¡Contraseña actualizada! Ahora puede iniciar sesión con: $email";
                $tipo = 'success';
            } else {
                // Crear nuevo usuario administrador
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, 'admin', 1)");
                $stmt->execute([$nombre, $email, $hash]);
                $mensaje = "¡Usuario administrador creado! Ahora puede iniciar sesión con: $email";
                $tipo = 'success';
            }
        } catch (Exception $e) {
            $mensaje = 'Error: ' . $e->getMessage();
            $tipo = 'danger';
        }
    }
}

// Verificar si hay usuarios
$hayUsuarios = false;
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $result = $stmt->fetch();
    $hayUsuarios = $result['total'] > 0;
} catch (Exception $e) {
    // Tabla no existe o error de conexión
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Inicial -
        <?= APP_NAME ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a365d, #2c5282);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .setup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }

        .setup-header {
            background: linear-gradient(135deg, #1a365d, #3182ce);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .setup-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .setup-header p {
            opacity: 0.85;
            font-size: 0.9rem;
        }

        .setup-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #1a365d, #3182ce);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 54, 93, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-danger {
            background: #fed7d7;
            color: #c53030;
        }

        .alert-success {
            background: #c6f6d5;
            color: #276749;
        }

        .alert-warning {
            background: #fefcbf;
            color: #975a16;
        }

        .setup-footer {
            padding: 1.5rem 2rem;
            background: #f7fafc;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .setup-footer a {
            color: #3182ce;
            text-decoration: none;
            font-weight: 500;
        }

        .setup-footer a:hover {
            text-decoration: underline;
        }

        .icon-large {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="setup-card">
        <div class="setup-header">
            <i class="fas fa-cog icon-large"></i>
            <h1>Configuración Inicial</h1>
            <p>Crear o actualizar usuario administrador</p>
        </div>

        <div class="setup-body">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?>">
                    <i class="fas fa-<?= $tipo === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($tipo !== 'success'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Importante:</strong> Elimine este archivo después de configurar el sistema.
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control"
                            value="<?= htmlspecialchars($_POST['nombre'] ?? 'Administrador') ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Correo Electrónico</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($_POST['email'] ?? 'admin@maga.gob.gt') ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Nueva Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres"
                            required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                        <input type="password" name="confirmar" class="form-control" required>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="setup-footer">
            <?php if ($tipo === 'success'): ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Ir al Login</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-arrow-left"></i> Volver al Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>