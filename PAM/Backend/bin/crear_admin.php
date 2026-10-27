<?php
/**
 * Crea (o restablece) un usuario administrador.
 *
 *   php bin/crear_admin.php [--usuario=admin] [--nombre="Administrador del sistema"] [--correo=a@b.com] [--clave=...]
 *
 * Sin --clave se genera una temporal y se muestra una sola vez; se pedirá cambiarla al ingresar.
 */
require_once __DIR__ . '/../autoload.php';

use App\Utils\Database;
use App\Utils\Security;

$opt = getopt('', ['usuario::', 'nombre::', 'correo::', 'clave::']);
$usuario = $opt['usuario'] ?? 'admin';
$nombre = $opt['nombre'] ?? 'Administrador del sistema';
$correo = $opt['correo'] ?? null;
$clave = $opt['clave'] ?? Security::claveTemporal();

if ($problema = Security::validarClave($clave)) {
    fwrite(STDERR, "Clave rechazada: $problema\n");
    exit(2);
}

$db = Database::getInstance();
$rol = $db->scalar("SELECT id FROM seg_rol WHERE codigo = 'ADMINISTRADOR'");
if ($rol === null) {
    fwrite(STDERR, "No existe el rol ADMINISTRADOR: importe primero Database/seed.sql\n");
    exit(2);
}

$db->execute(
    'INSERT INTO seg_usuario(usuario, nombre, correo, hash, rol_id, debe_cambiar_clave) VALUES (?, ?, ?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE hash = VALUES(hash), activo = 1, intentos_fallidos = 0, bloqueado_hasta = NULL,
        debe_cambiar_clave = 1, rol_id = VALUES(rol_id)',
    [$usuario, $nombre, $correo, Security::hashClave($clave), $rol]
);

echo "Usuario '$usuario' listo." . (isset($opt['clave']) ? '' : " Clave temporal: $clave  (se pedirá cambiarla al ingresar)") . "\n";
