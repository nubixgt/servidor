<?php
/**
 * Usuarios, roles y permisos (solo administrador) + bitácora.
 *   GET  users.php?a=listar
 *   POST users.php?a=guardar   {id?, usuario, nombre, rol, activo, permisos, password?}
 *   GET  users.php?a=bitacora
 */
require_once __DIR__ . '/bootstrap.php';

$admin = exigirAdmin();
$accion = $_GET['a'] ?? 'listar';

switch ($accion) {
    case 'listar':
        $usuarios = db()->query('SELECT id, usuario, nombre, rol, permisos, activo, ultimo_acceso, creado FROM usuarios ORDER BY rol, nombre')->fetchAll();
        foreach ($usuarios as &$x) {
            $x['permisos'] = normalizarPermisos(json_decode($x['permisos'], true) ?: [], $x['rol']);
            $x['activo'] = (bool)$x['activo'];
        }
        unset($x);
        $deptos = db()->query('SELECT DISTINCT departamento FROM matriz ORDER BY departamento')->fetchAll(PDO::FETCH_COLUMN);
        responder(['ok' => true, 'usuarios' => $usuarios, 'departamentos' => $deptos]);

    case 'guardar':
        exigirPost();
        $in = leerJSON();
        $id = (int)($in['id'] ?? 0);
        $usuario = strtolower(trim((string)($in['usuario'] ?? '')));
        $nombre = trim((string)($in['nombre'] ?? ''));
        $rol = (string)($in['rol'] ?? '');
        $activo = !empty($in['activo']);
        $password = (string)($in['password'] ?? '');

        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $usuario)) fallar('El usuario debe tener de 3 a 40 caracteres: letras minúsculas, números, punto, guion.');
        if ($nombre === '' || mb_strlen($nombre) > 80) fallar('Indica el nombre completo (máximo 80 caracteres).');
        if (!in_array($rol, ['admin', 'editor', 'visualizador'], true)) fallar('Rol no válido.');
        if ((!$id || $password !== '') && mb_strlen($password) < 8) fallar('La contraseña debe tener al menos 8 caracteres.');

        $permisos = normalizarPermisos(is_array($in['permisos'] ?? null) ? $in['permisos'] : [], 'editor');
        if ($rol === 'editor' && !$permisos['modulos'] && !$permisos['importar']) {
            fallar('Un editor debe tener al menos un módulo habilitado o permiso para importar.');
        }
        if ($rol !== 'editor') $permisos = new stdClass();

        if ($id === (int)$admin['id'] && ($rol !== 'admin' || !$activo)) {
            fallar('No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.');
        }

        $dup = db()->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id <> ?');
        $dup->execute([$usuario, $id]);
        if ($dup->fetchColumn()) fallar("El usuario «{$usuario}» ya existe.");

        $permJson = json_encode($permisos, JSON_UNESCAPED_UNICODE);
        if ($id) {
            $sql = 'UPDATE usuarios SET usuario = ?, nombre = ?, rol = ?, activo = ?, permisos = ?' . ($password !== '' ? ', password_hash = ?, intentos_fallidos = 0, bloqueado_hasta = 0' : '') . ' WHERE id = ?';
            $params = [$usuario, $nombre, $rol, (int)$activo, $permJson];
            if ($password !== '') $params[] = password_hash($password, PASSWORD_DEFAULT);
            $params[] = $id;
            $st = db()->prepare($sql);
            $st->execute($params);
            if (!$st->rowCount()) fallar('Usuario no encontrado.', 404);
            registrar($admin['usuario'], 'usuario_editado', "{$usuario} · rol {$rol}" . ($activo ? '' : ' · INACTIVO') . ($password !== '' ? ' · contraseña restablecida' : '') . " · permisos {$permJson}");
        } else {
            db()->prepare('INSERT INTO usuarios (usuario, nombre, password_hash, rol, permisos, activo, creado) VALUES (?,?,?,?,?,?,?)')
                ->execute([$usuario, $nombre, password_hash($password, PASSWORD_DEFAULT), $rol, $permJson, (int)$activo, date('Y-m-d H:i:s')]);
            $id = (int)db()->lastInsertId();
            registrar($admin['usuario'], 'usuario_creado', "{$usuario} · rol {$rol} · permisos {$permJson}");
        }
        responder(['ok' => true, 'id' => $id]);

    case 'bitacora':
        $st = db()->query('SELECT fecha, usuario, accion, detalle FROM bitacora ORDER BY id DESC LIMIT 300');
        responder(['ok' => true, 'registros' => $st->fetchAll()]);

    default:
        fallar('Acción no válida.', 404);
}
