<?php
namespace App\Repositories;

use App\Utils\Database;

class AdminRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ---------------------------------------------------------------- usuarios
    public function usuarios(): array
    {
        $filas = $this->db->all(
            'SELECT x.id, x.usuario, x.nombre, x.correo, x.activo, x.rol_id, r.nombre AS rol, x.departamento_id, x.municipio_id,
                    d.nombre AS departamento, m.nombre AS municipio, x.ultimo_acceso, x.bloqueado_hasta, x.debe_cambiar_clave
             FROM seg_usuario x JOIN seg_rol r ON r.id = x.rol_id
             LEFT JOIN ref_departamento d ON d.id = x.departamento_id LEFT JOIN ref_municipio m ON m.id = x.municipio_id
             ORDER BY x.usuario');
        foreach ($filas as &$f) {
            $f['activo'] = (bool) $f['activo'];
            $f['debe_cambiar_clave'] = (bool) $f['debe_cambiar_clave'];
        }
        return $filas;
    }

    public function usuarioExiste(string $usuario): bool
    {
        return $this->db->scalar('SELECT 1 FROM seg_usuario WHERE usuario = ?', [$usuario]) !== null;
    }

    public function crearUsuario(string $usuario, string $nombre, ?string $correo, string $hash, int $rolId, ?int $dep, ?int $mun): int
    {
        return $this->db->insert(
            'INSERT INTO seg_usuario(usuario, nombre, correo, hash, rol_id, departamento_id, municipio_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$usuario, $nombre, $correo, $hash, $rolId, $dep, $mun]
        );
    }

    public function actualizarUsuario(int $id, array $campos): void
    {
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($campos)));
        $this->db->execute("UPDATE seg_usuario SET $sets WHERE id = ?", array_merge(array_values($campos), [$id]));
    }

    public function revocarSesiones(int $usuarioId): void
    {
        $this->db->execute('UPDATE seg_sesion SET revocado_en = NOW() WHERE usuario_id = ? AND revocado_en IS NULL', [$usuarioId]);
    }

    public function restablecerClave(int $id, string $hash): bool
    {
        if ($this->db->scalar('SELECT 1 FROM seg_usuario WHERE id = ?', [$id]) === null) {
            return false;
        }
        $this->db->execute('UPDATE seg_usuario SET hash = ?, debe_cambiar_clave = 1, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?', [$hash, $id]);
        return true;
    }

    public function rolExiste(int $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM seg_rol WHERE id = ?', [$id]) !== null;
    }

    public function municipioExiste(int $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM ref_municipio WHERE id = ?', [$id]) !== null;
    }

    public function departamentoExiste(int $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM ref_departamento WHERE id = ?', [$id]) !== null;
    }

    // ---------------------------------------------------------------- roles
    public function roles(): array
    {
        $filas = $this->db->all(
            'SELECT r.id, r.codigo, r.nombre, r.descripcion, r.sistema,
                    (SELECT GROUP_CONCAT(rp.permiso_codigo ORDER BY rp.permiso_codigo) FROM seg_rol_permiso rp WHERE rp.rol_id = r.id) AS permisos,
                    (SELECT COUNT(*) FROM seg_usuario x WHERE x.rol_id = r.id) AS usuarios
             FROM seg_rol r ORDER BY r.id');
        foreach ($filas as &$f) {
            $f['sistema'] = (bool) $f['sistema'];
            $f['permisos'] = $f['permisos'] === null ? [] : explode(',', $f['permisos']);
            $f['usuarios'] = (int) $f['usuarios'];
        }
        return $filas;
    }

    public function permisos(): array
    {
        return $this->db->all('SELECT codigo, modulo, descripcion FROM seg_permiso ORDER BY orden');
    }

    /** @return int|null id del rol creado, null si el código ya existe */
    public function crearRol(string $codigo, string $nombre, ?string $descripcion): ?int
    {
        if ($this->db->scalar('SELECT 1 FROM seg_rol WHERE codigo = ?', [$codigo]) !== null) {
            return null;
        }
        return $this->db->insert('INSERT INTO seg_rol(codigo, nombre, descripcion) VALUES (?, ?, ?)', [$codigo, $nombre, $descripcion]);
    }

    public function codigoRol(int $id): ?string
    {
        return $this->db->scalar('SELECT codigo FROM seg_rol WHERE id = ?', [$id]);
    }

    public function permisosValidos(array $codigos): array
    {
        if (!$codigos) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($codigos), '?'));
        return array_column($this->db->all("SELECT codigo FROM seg_permiso WHERE codigo IN ($marcas)", array_values($codigos)), 'codigo');
    }

    public function reemplazarPermisos(int $rolId, array $validos): void
    {
        if ($validos) {
            $marcas = implode(',', array_fill(0, count($validos), '?'));
            $this->db->execute("DELETE FROM seg_rol_permiso WHERE rol_id = ? AND permiso_codigo NOT IN ($marcas)", array_merge([$rolId], $validos));
        } else {
            $this->db->execute('DELETE FROM seg_rol_permiso WHERE rol_id = ?', [$rolId]);
        }
        foreach ($validos as $codigo) {
            $this->db->execute('INSERT IGNORE INTO seg_rol_permiso(rol_id, permiso_codigo) VALUES (?, ?)', [$rolId, $codigo]);
        }
    }

    // ---------------------------------------------------------------- bitácora y parámetros
    public function bitacora(array $f, int $limite): array
    {
        $w = [];
        $p = [];
        foreach (['usuario' => 'b.usuario = ?', 'accion' => 'b.accion = ?', 'entidad' => 'b.entidad = ?', 'entidad_id' => 'b.entidad_id = ?',
            'desde' => 'b.ts >= ?', 'hasta' => 'b.ts < DATE_ADD(?, INTERVAL 1 DAY)', 'antes_de' => 'b.id < ?'] as $k => $cond) {
            if (isset($f[$k])) {
                $w[] = $cond;
                $p[] = $f[$k];
            }
        }
        $sql = 'SELECT b.id, b.ts, b.usuario, b.accion, b.modulo, b.entidad, b.entidad_id, b.antes, b.despues, b.ip FROM audit_bitacora b'
            . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY b.id DESC LIMIT ?';
        $filas = $this->db->all($sql, array_merge($p, [$limite]));
        foreach ($filas as &$r) {
            $r['antes'] = $r['antes'] === null ? null : json_decode($r['antes'], true);
            $r['despues'] = $r['despues'] === null ? null : json_decode($r['despues'], true);
        }
        return $filas;
    }

    public function parametros(): array
    {
        $filas = $this->db->all('SELECT clave, valor, descripcion FROM ref_parametro ORDER BY clave');
        foreach ($filas as &$r) {
            $r['valor'] = json_decode($r['valor'], true);
        }
        return $filas;
    }

    public function parametroExiste(string $clave): bool
    {
        return $this->db->scalar('SELECT 1 FROM ref_parametro WHERE clave = ?', [$clave]) !== null;
    }

    public function guardarParametro(string $clave, string $json): void
    {
        $this->db->execute('UPDATE ref_parametro SET valor = ? WHERE clave = ?', [$json, $clave]);
    }

    public function familiaExiste(int $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM ref_cat_familia_resolucion WHERE id = ?', [$id]) !== null;
    }

    public function guardarTipoResolucion(string $codigo, string $nombre, int $familiaId, bool $activo): void
    {
        $this->db->execute(
            'INSERT INTO ref_cat_tipo_resolucion(codigo, nombre, familia_id, activo) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), familia_id = VALUES(familia_id), activo = VALUES(activo)',
            [$codigo, $nombre, $familiaId, (int) $activo]
        );
        $this->db->execute("INSERT INTO mart_meta(clave, valor) VALUES ('pendiente_refresco', '1') ON DUPLICATE KEY UPDATE valor = '1'");
    }
}
