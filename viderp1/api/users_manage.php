<?php
/**
 * API: Gestión de Usuarios
 * VIDER - MAGA Guatemala
 * Usa hora de Guatemala
 */
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../index.php?error=Sin permisos');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../usuarios.php?error=Método no permitido');
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Hora actual de Guatemala (desde PHP)
    $horaGuatemala = date('Y-m-d H:i:s');
    
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $username = trim($_POST['username'] ?? '');
            $nombre = trim($_POST['nombre_completo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? 'tecnico';
            $pass = $_POST['password'] ?? '';

            if (empty($username) || empty($nombre) || empty($pass)) {
                header('Location: ../usuarios.php?error=Complete todos los campos obligatorios');
                exit;
            }

            if (strlen($pass) < 6) {
                header('Location: ../usuarios.php?error=Contraseña mínimo 6 caracteres');
                exit;
            }

            if (!in_array($rol, ['admin', 'tecnico'])) {
                $rol = 'tecnico';
            }

            // Verificar duplicado
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                header('Location: ../usuarios.php?error=El usuario ya existe');
                exit;
            }

            // Insertar con hora de Guatemala
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre_completo, email, rol, activo, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([$username, $hash, $nombre, $email ?: null, $rol, $horaGuatemala]);

            header('Location: ../usuarios.php?success=Usuario creado correctamente');
            exit;

        case 'update':
            $id = intval($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $nombre = trim($_POST['nombre_completo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? 'tecnico';
            $pass = $_POST['password'] ?? '';

            if (!$id || empty($username) || empty($nombre)) {
                header('Location: ../usuarios.php?error=Datos incompletos');
                exit;
            }

            if (!in_array($rol, ['admin', 'tecnico'])) {
                $rol = 'tecnico';
            }

            // Verificar duplicado
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                header('Location: ../usuarios.php?error=Nombre de usuario en uso');
                exit;
            }

            // Actualizar
            if (!empty($pass)) {
                if (strlen($pass) < 6) {
                    header('Location: ../usuarios.php?error=Contraseña mínimo 6 caracteres');
                    exit;
                }
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET username=?, nombre_completo=?, email=?, rol=?, password=? WHERE id=?");
                $stmt->execute([$username, $nombre, $email ?: null, $rol, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET username=?, nombre_completo=?, email=?, rol=? WHERE id=?");
                $stmt->execute([$username, $nombre, $email ?: null, $rol, $id]);
            }

            header('Location: ../usuarios.php?success=Usuario actualizado');
            exit;

        case 'delete':
            $id = intval($_POST['user_id'] ?? 0);
            
            if (!$id) {
                header('Location: ../usuarios.php?error=ID inválido');
                exit;
            }

            if ($id == $_SESSION['user_id']) {
                header('Location: ../usuarios.php?error=No puede eliminarse a sí mismo');
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);

            header('Location: ../usuarios.php?success=Usuario eliminado');
            exit;

        case 'toggle':
            $id = intval($_POST['user_id'] ?? 0);
            $activar = $_POST['activate'] === 'true' ? 1 : 0;

            if (!$id) {
                header('Location: ../usuarios.php?error=ID inválido');
                exit;
            }

            if ($id == $_SESSION['user_id'] && !$activar) {
                header('Location: ../usuarios.php?error=No puede desactivarse a sí mismo');
                exit;
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
            $stmt->execute([$activar, $id]);

            $msg = $activar ? 'activado' : 'desactivado';
            header("Location: ../usuarios.php?success=Usuario $msg");
            exit;

        default:
            header('Location: ../usuarios.php?error=Acción no válida');
            exit;
    }

} catch (Exception $e) {
    header('Location: ../usuarios.php?error=' . urlencode($e->getMessage()));
    exit;
}