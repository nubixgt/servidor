<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\UserRepository;
use PDOException;

class UserService
{
    public const ROLES = ['admin', 'vendedor'];

    private UserRepository $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function getAll(): array
    {
        try {
            return array_map(fn($u) => $u->toArray(), $this->repository->findAll());
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new ApiException('No se pudieron consultar los usuarios.', 503);
        }
    }

    public function create(array $data): array
    {
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        $fullName = trim((string) ($data['fullName'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = (string) ($data['role'] ?? '');

        if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)) {
            throw new ApiException('El usuario debe tener de 3 a 30 caracteres (letras, números, punto, guion).', 400);
        }
        self::validateFullName($fullName);
        self::validatePassword($password);
        if (!in_array($role, self::ROLES, true)) {
            throw new ApiException('Rol inválido.', 400);
        }

        try {
            $id = $this->repository->create($username, password_hash($password, PASSWORD_DEFAULT), $fullName, $role);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ApiException('Ese nombre de usuario ya existe.', 409);
            }
            error_log($e->getMessage());
            throw new ApiException('No se pudo crear el usuario.', 503);
        }

        return $this->repository->findById($id)->toArray();
    }

    public function update(int $id, array $data, int $actorId): array
    {
        $user = $this->repository->findById($id);
        if (!$user) {
            throw new ApiException('El usuario no existe.', 404);
        }

        $fields = [];

        if (array_key_exists('fullName', $data)) {
            $fullName = trim((string) $data['fullName']);
            self::validateFullName($fullName);
            $fields['full_name'] = $fullName;
        }
        if (array_key_exists('role', $data)) {
            if (!in_array($data['role'], self::ROLES, true)) {
                throw new ApiException('Rol inválido.', 400);
            }
            $fields['role'] = $data['role'];
        }
        if (array_key_exists('active', $data)) {
            $fields['active'] = $data['active'] ? 1 : 0;
        }
        if (array_key_exists('password', $data)) {
            self::validatePassword((string) $data['password']);
            $fields['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        // Nadie puede quitarse el acceso a sí mismo ni dejar el sistema sin administradores.
        $loosesAdmin = ($fields['role'] ?? $user->role) !== 'admin' || (($fields['active'] ?? 1) === 0);
        if ($user->role === 'admin' && $user->active && $loosesAdmin) {
            if ($id === $actorId) {
                throw new ApiException('No puedes quitarte el rol de administrador ni desactivarte a ti mismo.', 400);
            }
            if ($this->repository->countActiveAdmins() <= 1) {
                throw new ApiException('Debe quedar al menos un administrador activo.', 400);
            }
        }

        try {
            $this->repository->update($id, $fields);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new ApiException('No se pudo actualizar el usuario.', 503);
        }

        return $this->repository->findById($id)->toArray();
    }

    private static function validateFullName(string $name): void
    {
        $len = mb_strlen($name);
        if ($len < 2 || $len > 100) {
            throw new ApiException('Ingresa el nombre completo (2 a 100 caracteres).', 400);
        }
    }

    private static function validatePassword(string $password): void
    {
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new ApiException('La contraseña debe tener entre 8 y 72 caracteres.', 400);
        }
    }
}
