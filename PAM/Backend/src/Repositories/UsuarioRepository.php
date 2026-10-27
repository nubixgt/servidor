<?php
namespace App\Repositories;

use App\Entities\Usuario;
use App\Utils\Database;

class UsuarioRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function buscarPorUsuario(string $usuario): ?array
    {
        return $this->db->one(
            'SELECT id, hash, activo, intentos_fallidos,
                    (bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()) AS bloqueado,
                    GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) / 60)) AS minutos
             FROM seg_usuario WHERE usuario = ?',
            [$usuario]
        );
    }

    public function registrarFallo(int $id, int $maxIntentos, int $bloqueoMin): void
    {
        // bloqueado_hasta va primero: MySQL evalúa los SET de izquierda a derecha y usa el valor ya incrementado
        $this->db->execute(
            'UPDATE seg_usuario
             SET bloqueado_hasta = CASE WHEN intentos_fallidos + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? MINUTE) END,
                 intentos_fallidos = intentos_fallidos + 1
             WHERE id = ?',
            [$maxIntentos, $bloqueoMin, $id]
        );
    }

    public function registrarAcceso(int $id): void
    {
        $this->db->execute('UPDATE seg_usuario SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?', [$id]);
    }

    /** Usuario con rol y permisos; null si está inactivo o (con $sesionId) la sesión ya no es válida. */
    public function cargar(int $id, ?string $sesionId = null): ?Usuario
    {
        $r = $this->db->one(
            'SELECT u.id, u.usuario, u.nombre, u.activo, u.debe_cambiar_clave, u.departamento_id, u.municipio_id,
                    r.codigo AS rol, r.nombre AS rol_nombre,
                    (SELECT GROUP_CONCAT(rp.permiso_codigo) FROM seg_rol_permiso rp WHERE rp.rol_id = r.id) AS permisos,
                    (SELECT s.revocado_en IS NULL AND s.expira_en > NOW() FROM seg_sesion s WHERE s.id = ?) AS sesion_ok
             FROM seg_usuario u JOIN seg_rol r ON r.id = u.rol_id
             WHERE u.id = ?',
            [$sesionId, $id]
        );
        if (!$r || !$r['activo'] || ($sesionId !== null && !$r['sesion_ok'])) {
            return null;
        }
        return new Usuario(
            (int) $r['id'], $r['usuario'], $r['nombre'], $r['rol'], $r['rol_nombre'],
            $r['permisos'] === null ? [] : explode(',', $r['permisos']),
            $r['departamento_id'] === null ? null : (int) $r['departamento_id'],
            $r['municipio_id'] === null ? null : (int) $r['municipio_id'],
            (bool) $r['debe_cambiar_clave'], $sesionId
        );
    }

    public function hashDe(int $id): ?string
    {
        return $this->db->scalar('SELECT hash FROM seg_usuario WHERE id = ?', [$id]);
    }

    public function cambiarClave(int $id, string $hash): void
    {
        $this->db->execute('UPDATE seg_usuario SET hash = ?, debe_cambiar_clave = 0 WHERE id = ?', [$hash, $id]);
    }
}
