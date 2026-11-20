<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\UsuarioDTO;
use App\Repositories\UsuarioRepository;

/** Administración de cuentas (solo rol admin). */
class UsuarioService
{
    private const CLAVE_MIN = 8;
    private const CLAVE_MAX = 72; // bcrypt ignora lo que pase de 72 bytes

    private UsuarioRepository $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    public function listar(): array
    {
        $items = array_map(static fn(array $r) => [
            'id' => (int) $r['id'],
            'usuario' => $r['usuario'],
            'nombre' => $r['nombre_completo'],
            'rol' => $r['rol'],
            'activo' => (bool) $r['activo'],
            'ultimo_acceso' => $r['ultimo_acceso'],
            'creado_en' => $r['creado_en'],
            'seguimientos' => (int) $r['seguimientos'],
        ], $this->usuarios->listar());

        return ['items' => $items, 'roles' => UsuarioDTO::ROLES];
    }

    public function crear(UsuarioDTO $dto): array
    {
        $this->validar($dto, true);
        if ($this->usuarios->nombreEnUso($dto->usuario)) {
            throw new HttpException('Ese nombre de usuario ya existe', 409);
        }
        $id = $this->usuarios->crear(
            $dto->rol,
            $dto->usuario,
            password_hash($dto->clave, PASSWORD_BCRYPT),
            $dto->nombre,
            $dto->activo
        );
        return ['id' => $id];
    }

    public function actualizar(int $id, int $actorId, UsuarioDTO $dto): void
    {
        $destino = $this->usuarios->findById($id);
        if ($destino === null) {
            throw new HttpException('Usuario no encontrado', 404);
        }
        $this->validar($dto, false);
        if ($this->usuarios->nombreEnUso($dto->usuario, $id)) {
            throw new HttpException('Ese nombre de usuario ya existe', 409);
        }

        $dejaDeSerAdminActivo = $destino->rol === 'admin' && $destino->activo
            && ($dto->rol !== 'admin' || !$dto->activo);
        if ($dejaDeSerAdminActivo) {
            if ($id === $actorId) {
                throw new HttpException('No puedes desactivar tu propia cuenta ni quitarte el rol de administrador', 422);
            }
            if ($this->usuarios->adminsActivos($id) === 0) {
                throw new HttpException('Debe quedar al menos un administrador activo', 422);
            }
        }
        if ($id === $actorId && !$dto->activo) {
            throw new HttpException('No puedes desactivar tu propia cuenta', 422);
        }

        $this->usuarios->actualizar($id, $dto->rol, $dto->usuario, $dto->nombre, $dto->activo);
        if ($dto->clave !== null) {
            $this->usuarios->cambiarClave($id, password_hash($dto->clave, PASSWORD_BCRYPT));
        }
    }

    public function eliminar(int $id, int $actorId): void
    {
        $destino = $this->usuarios->findById($id);
        if ($destino === null) {
            throw new HttpException('Usuario no encontrado', 404);
        }
        if ($id === $actorId) {
            throw new HttpException('No puedes eliminar tu propia cuenta', 422);
        }
        if ($destino->rol === 'admin' && $destino->activo && $this->usuarios->adminsActivos($id) === 0) {
            throw new HttpException('Debe quedar al menos un administrador activo', 422);
        }
        // Quien ya dejó comentarios o imágenes no se borra: se perdería quién los hizo
        if ($this->usuarios->tieneSeguimientos($id)) {
            throw new HttpException(
                'Este usuario ya registró seguimientos y no se puede eliminar. Desactívalo para impedir su acceso.',
                409
            );
        }
        $this->usuarios->eliminar($id);
    }

    private function validar(UsuarioDTO $dto, bool $claveObligatoria): void
    {
        $errores = [];
        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $dto->usuario)) {
            $errores['usuario'] = 'De 3 a 40 caracteres: letras, números, punto, guion o guion bajo';
        }
        if (mb_strlen($dto->nombre) < 2 || mb_strlen($dto->nombre) > 120) {
            $errores['nombre'] = 'El nombre debe tener entre 2 y 120 caracteres';
        }
        if (!in_array($dto->rol, UsuarioDTO::ROLES, true)) {
            $errores['rol'] = 'Rol inválido';
        }
        if ($dto->clave === null) {
            if ($claveObligatoria) {
                $errores['clave'] = 'La contraseña es obligatoria';
            }
        } elseif (strlen($dto->clave) < self::CLAVE_MIN || strlen($dto->clave) > self::CLAVE_MAX) {
            $errores['clave'] = 'La contraseña debe tener entre ' . self::CLAVE_MIN . ' y ' . self::CLAVE_MAX . ' caracteres';
        }
        if ($errores) {
            throw new HttpException(reset($errores), 422, $errores);
        }
    }
}
