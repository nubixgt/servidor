<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\CambioUsuarioDTO;
use App\DTOs\NuevoRolDTO;
use App\DTOs\NuevoUsuarioDTO;
use App\DTOs\TipoResolucionDTO;
use App\Entities\Usuario;
use App\Repositories\AdminRepository;
use App\Utils\Database;
use App\Utils\Security;
use App\Utils\Validator;

/**
 * Administración: usuarios, roles/permisos configurables, parámetros, catálogo de resoluciones y bitácora.
 */
class AdminService
{
    private $repository;
    private $auditoria;
    private $db;

    public function __construct()
    {
        $this->repository = new AdminRepository();
        $this->auditoria = new AuditoriaService();
        $this->db = Database::getInstance();
    }

    // ---------------------------------------------------------------- usuarios
    public function usuarios(): array
    {
        return $this->repository->usuarios();
    }

    public function crearUsuario(NuevoUsuarioDTO $dto): array
    {
        if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $dto->usuario)) {
            throw new HttpException('El usuario debe tener de 3 a 40 caracteres (letras, números, punto, guion)', 400);
        }
        $nombre = Validator::texto($dto->nombre, 'El nombre', 3, 120, true);
        $correo = Validator::texto($dto->correo, 'El correo', 0, 160);
        if ($correo !== null && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException('El correo no es válido', 400);
        }
        $rolId = Validator::entero($dto->rolId, 'El rol');
        $dep = Validator::entero($dto->departamentoId, 'El departamento', false);
        $mun = Validator::entero($dto->municipioId, 'El municipio', false);
        $this->validarAmbito($rolId, $dep, $mun);
        $dep = $dep ?: ($mun ? intdiv($mun, 100) : null);

        if ($this->repository->usuarioExiste($dto->usuario)) {
            throw new HttpException('Ese nombre de usuario ya existe', 409);
        }
        $clave = Security::claveTemporal();
        $id = $this->repository->crearUsuario($dto->usuario, $nombre, $correo, Security::hashClave($clave), $rolId, $dep, $mun);
        return ['id' => $id, 'clave_temporal' => $clave];
    }

    public function editarUsuario(Usuario $u, int $uid, CambioUsuarioDTO $dto): array
    {
        $in = $dto->campos;
        if ($uid === $u->id && ((array_key_exists('activo', $in) && !$in['activo']) || array_key_exists('rol_id', $in))) {
            throw new HttpException('No puede desactivarse ni cambiar su propio rol', 400);
        }

        $campos = [];
        if (array_key_exists('nombre', $in)) {
            $campos['nombre'] = Validator::texto($in['nombre'], 'El nombre', 3, 120, true);
        }
        if (array_key_exists('correo', $in)) {
            $campos['correo'] = Validator::texto($in['correo'], 'El correo', 0, 160);
            if ($campos['correo'] !== null && !filter_var($campos['correo'], FILTER_VALIDATE_EMAIL)) {
                throw new HttpException('El correo no es válido', 400);
            }
        }
        if (array_key_exists('rol_id', $in)) {
            $campos['rol_id'] = Validator::entero($in['rol_id'], 'El rol');
        }
        if (array_key_exists('activo', $in)) {
            $campos['activo'] = (int) (bool) $in['activo'];
        }
        if (array_key_exists('departamento_id', $in)) {
            $campos['departamento_id'] = Validator::entero($in['departamento_id'], 'El departamento', false);
        }
        if (array_key_exists('municipio_id', $in)) {
            $campos['municipio_id'] = Validator::entero($in['municipio_id'], 'El municipio', false);
        }
        if (!empty($in['ambito_nacional'])) {
            $campos['departamento_id'] = null;
            $campos['municipio_id'] = null;
        }
        if (!empty($campos['municipio_id'])) {
            $campos['departamento_id'] = intdiv($campos['municipio_id'], 100);
        }
        if (!$campos) {
            return ['ok' => true];
        }
        $this->validarAmbito($campos['rol_id'] ?? null, $campos['departamento_id'] ?? null, $campos['municipio_id'] ?? null);

        $this->db->transaction(function () use ($uid, $campos) {
            $this->repository->actualizarUsuario($uid, $campos);
            if (array_key_exists('activo', $campos) && !$campos['activo']) {
                $this->repository->revocarSesiones($uid);
            }
        });
        return ['ok' => true];
    }

    public function restablecerClave(Usuario $u, int $uid): array
    {
        $clave = Security::claveTemporal();
        $ok = $this->db->transaction(function () use ($uid, $clave) {
            if (!$this->repository->restablecerClave($uid, Security::hashClave($clave))) {
                return false;
            }
            $this->repository->revocarSesiones($uid);
            return true;
        });
        if (!$ok) {
            throw new HttpException('Usuario no encontrado', 404);
        }
        $this->auditoria->registrar($u, 'RESTABLECER_CLAVE', 'admin', 'seg.usuario', (string) $uid);
        return ['clave_temporal' => $clave];
    }

    // ---------------------------------------------------------------- roles
    public function roles(): array
    {
        return ['roles' => $this->repository->roles(), 'permisos' => $this->repository->permisos()];
    }

    public function crearRol(NuevoRolDTO $dto): array
    {
        if (!preg_match('/^[A-Z_]{3,30}$/', $dto->codigo)) {
            throw new HttpException('El código del rol debe tener de 3 a 30 letras mayúsculas o guion bajo', 400);
        }
        $nombre = Validator::texto($dto->nombre, 'El nombre', 3, 60, true);
        $id = $this->repository->crearRol($dto->codigo, $nombre, Validator::texto($dto->descripcion, 'La descripción', 0, 255));
        if ($id === null) {
            throw new HttpException('Ya existe un rol con ese código', 409);
        }
        return ['id' => $id];
    }

    public function asignarPermisos(int $rid, array $permisos): array
    {
        $codigo = $this->repository->codigoRol($rid);
        if ($codigo === null) {
            throw new HttpException('Rol no encontrado', 404);
        }
        $permisos = array_values(array_unique(array_filter($permisos, 'is_string')));
        if ($codigo === 'ADMINISTRADOR' && array_diff(['roles.gestionar', 'usuarios.gestionar'], $permisos)) {
            throw new HttpException('El rol Administrador debe conservar la gestión de usuarios y roles', 400);
        }
        $validos = $this->repository->permisosValidos($permisos);
        $this->db->transaction(fn() => $this->repository->reemplazarPermisos($rid, $validos));
        sort($validos);
        return ['ok' => true, 'permisos' => $validos];
    }

    // ---------------------------------------------------------------- bitácora y parámetros
    public function bitacora(array $q): array
    {
        $f = [];
        foreach (['usuario', 'accion', 'entidad', 'entidad_id'] as $k) {
            if (!empty($q[$k])) {
                $f[$k] = (string) $q[$k];
            }
        }
        foreach (['desde', 'hasta'] as $k) {
            if (!empty($q[$k])) {
                $f[$k] = Validator::fecha($q[$k], "El campo $k");
            }
        }
        if (!empty($q['antes_de'])) {
            $f['antes_de'] = Validator::entero($q['antes_de'], 'antes_de');
        }
        $limite = max(1, min(200, (int) ($q['limite'] ?? 50)));
        return $this->repository->bitacora($f, $limite);
    }

    public function parametros(): array
    {
        return $this->repository->parametros();
    }

    public function cambiarParametro(string $clave, $valor): array
    {
        if (!$this->repository->parametroExiste($clave)) {
            throw new HttpException('Parámetro no encontrado', 404);
        }
        $this->repository->guardarParametro($clave, json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return ['ok' => true];
    }

    public function guardarTipoResolucion(TipoResolucionDTO $dto): array
    {
        $codigo = mb_strtoupper(Validator::texto($dto->codigo, 'El código', 1, 6, true), 'UTF-8');
        $nombre = Validator::texto($dto->nombre, 'El nombre', 3, 200, true);
        $familia = Validator::entero($dto->familiaId, 'La familia');
        if (!$this->repository->familiaExiste($familia)) {
            throw new HttpException('La familia de resolución no existe', 400);
        }
        $this->repository->guardarTipoResolucion($codigo, $nombre, $familia, $dto->activo);
        return ['ok' => true];
    }

    // ---------------------------------------------------------------- internos
    private function validarAmbito(?int $rolId, ?int $dep, ?int $mun): void
    {
        if ($rolId !== null && !$this->repository->rolExiste($rolId)) {
            throw new HttpException('El rol no existe', 400);
        }
        if ($dep && !$this->repository->departamentoExiste($dep)) {
            throw new HttpException('El departamento no existe', 400);
        }
        if ($mun && !$this->repository->municipioExiste($mun)) {
            throw new HttpException('El municipio no existe', 400);
        }
    }
}
