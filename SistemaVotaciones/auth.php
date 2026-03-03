<?php
/**
 * auth.php - Archivo de autenticación
 * Incluir al inicio de cada página que requiera login
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar timeout de sesión (opcional - 2 horas)
$timeout_duration = 7200; // 2 horas en segundos
if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];
    if ($elapsed_time > $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Actualizar tiempo de actividad
$_SESSION['login_time'] = time();

// Función para verificar si el usuario es admin
function esAdmin() {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin';
}

// Función para requerir permisos de admin
function requiereAdmin() {
    if (!esAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        die('
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acceso Denegado</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: "Inter", sans-serif;
                }
                .error-card {
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    padding: 3rem;
                    text-align: center;
                    max-width: 500px;
                }
                .error-icon {
                    font-size: 5rem;
                    color: #ef4444;
                    margin-bottom: 1.5rem;
                }
                h1 {
                    color: #1e293b;
                    font-weight: 800;
                    margin-bottom: 1rem;
                }
                p {
                    color: #64748b;
                    margin-bottom: 2rem;
                }
                .btn-back {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 0.75rem 2rem;
                    border-radius: 10px;
                    font-weight: 600;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                }
                .btn-back:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="error-card">
                <i class="bi bi-shield-exclamation error-icon"></i>
                <h1>Acceso Denegado</h1>
                <p>No tienes permisos para acceder a esta sección. Esta área está restringida solo para administradores.</p>
                <a href="index.php" class="btn-back">
                    <i class="bi bi-arrow-left me-2"></i>Volver al Dashboard
                </a>
            </div>
        </body>
        </html>
        ');
    }
}

// Función para obtener información del usuario actual
function getUsuarioActual() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
        'tipo_usuario' => $_SESSION['tipo_usuario'] ?? 'usuario'
    ];
}

// Actualizar última actividad en la base de datos (opcional)
try {
    require_once 'config.php';
    $db = getDB();
    $stmt = $db->prepare("UPDATE sesiones_activas SET fecha_actividad = NOW() WHERE session_id = ?");
    $stmt->execute([session_id()]);
} catch (Exception $e) {
    // Silenciar errores de actualización de sesión
}