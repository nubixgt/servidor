<?php
/**
 * Autenticación: login, logout, sesión actual y cambio de contraseña.
 *   GET  auth.php?a=me
 *   POST auth.php?a=login     {usuario, password}
 *   POST auth.php?a=logout
 *   POST auth.php?a=password  {actual, nueva}
 */
require_once __DIR__ . '/bootstrap.php';

$accion = $_GET['a'] ?? 'me';

switch ($accion) {
    case 'me':
        $u = usuarioActual();
        if (!$u) fallar('Sesión no iniciada.', 401);
        responder(['ok' => true, 'usuario' => $u]);

    case 'login':
        exigirPost();
        $in = leerJSON();
        $usuario = trim((string)($in['usuario'] ?? ''));
        $password = (string)($in['password'] ?? '');
        if ($usuario === '' || $password === '') fallar('Ingresa usuario y contraseña.');

        $st = db()->prepare('SELECT * FROM usuarios WHERE usuario = ?');
        $st->execute([$usuario]);
        $u = $st->fetch();

        if ($u && $u['bloqueado_hasta'] > time()) {
            $min = (int)ceil(($u['bloqueado_hasta'] - time()) / 60);
            fallar("Usuario bloqueado temporalmente por intentos fallidos. Intenta en {$min} min.", 429);
        }
        if (!$u || !$u['activo'] || !password_verify($password, $u['password_hash'])) {
            if ($u) {
                $intentos = $u['intentos_fallidos'] + 1;
                $bloqueo = $intentos >= MAX_INTENTOS_LOGIN ? time() + BLOQUEO_SEGUNDOS : 0;
                db()->prepare('UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?')
                    ->execute([$bloqueo ? 0 : $intentos, $bloqueo, $u['id']]);
            }
            registrar($usuario, 'login_fallido');
            fallar('Usuario o contraseña incorrectos.', 401);
        }

        iniciarSesion();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        db()->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = 0, ultimo_acceso = ? WHERE id = ?')
            ->execute([date('Y-m-d H:i:s'), $u['id']]);
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }
        registrar($u['usuario'], 'login');
        responder(['ok' => true, 'usuario' => usuarioActual()]);

    case 'logout':
        exigirPost();
        iniciarSesion();
        if (!empty($_SESSION['uid'])) {
            $u = usuarioActual();
            if ($u) registrar($u['usuario'], 'logout');
        }
        $_SESSION = [];
        session_destroy();
        responder(['ok' => true]);

    case 'password':
        exigirPost();
        $u = exigirUsuario();
        $in = leerJSON();
        $nueva = (string)($in['nueva'] ?? '');
        if (mb_strlen($nueva) < 8) fallar('La nueva contraseña debe tener al menos 8 caracteres.');
        $st = db()->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
        $st->execute([$u['id']]);
        if (!password_verify((string)($in['actual'] ?? ''), $st->fetchColumn())) fallar('La contraseña actual no es correcta.');
        db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);
        registrar($u['usuario'], 'cambio_password');
        responder(['ok' => true]);

    default:
        fallar('Acción no válida.', 404);
}
