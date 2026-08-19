<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\DTOs\UsuarioDTO;
use App\Entities\Usuario;

class UsuarioService
{
    private UsuarioRepository $repository;

    private const ROLES = ['tecnico', 'supervisor', 'administrador'];

    private const DEPARTAMENTOS_VALIDOS = [
        'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula', 'El Progreso',
        'Escuintla', 'Guatemala', 'Huehuetenango', 'Izabal', 'Jalapa', 'Jutiapa',
        'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu', 'Sacatepéquez',
        'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez', 'Totonicapán', 'Zacapa',
    ];

    public function __construct()
    {
        $this->repository = new UsuarioRepository();
    }

    /** @return Usuario[] */
    public function listar(array $currentUser): array
    {
        if ($currentUser['role'] === 'supervisor') {
            $criteria = ['role' => 'tecnico'];
            if (!empty($currentUser['regionAsignada'])) {
                $criteria['regionAsignada'] = $currentUser['regionAsignada'];
            }
            return $this->repository->findAll($criteria);
        }
        return $this->repository->findAll();
    }

    public function crear(UsuarioDTO $dto): Usuario
    {
        if (!$dto->nombre || !$dto->usuario || !$dto->password || !$dto->role) {
            throw new \Exception('Nombre, usuario, contraseña y rol son requeridos.', 400);
        }
        if (!in_array($dto->role, self::ROLES, true)) {
            throw new \Exception('Rol inválido.', 400);
        }
        if ($this->repository->existeUsuario($dto->usuario)) {
            throw new \Exception('Ya existe un usuario con ese nombre de usuario.', 409);
        }

        $usuario = new Usuario(
            nombre: $dto->nombre,
            usuario: $dto->usuario,
            email: $dto->email ?: null,
            passwordHash: password_hash($dto->password, PASSWORD_BCRYPT),
            role: $dto->role,
            regionAsignada: $this->normalizeRegion($dto->regionAsignada),
            telefono: $dto->telefono,
            activo: true,
        );

        return $this->repository->create($usuario);
    }

    public function actualizar(int $id, UsuarioDTO $dto): Usuario
    {
        $usuario = $this->repository->findById($id);
        if (!$usuario) {
            throw new \Exception('Usuario no encontrado.', 404);
        }

        $fields = [];
        if ($dto->nombre) {
            $fields['nombre'] = $dto->nombre;
        }
        if ($dto->role && in_array($dto->role, self::ROLES, true)) {
            $fields['role'] = $dto->role;
        }
        $fields['regionAsignada'] = $this->normalizeRegion($dto->regionAsignada);
        $fields['telefono'] = $dto->telefono;
        $fields['email'] = $dto->email;
        if ($dto->activo !== null) {
            $fields['activo'] = $dto->activo;
        }
        if ($dto->password) {
            $fields['passwordHash'] = password_hash($dto->password, PASSWORD_BCRYPT);
        }

        $this->repository->update($id, $fields);
        return $this->repository->findById($id);
    }

    public function eliminar(int $id, array $currentUser): void
    {
        if ($id === (int)$currentUser['id']) {
            throw new \Exception('No puedes eliminar tu propio usuario.', 400);
        }
        $usuario = $this->repository->findById($id);
        if (!$usuario) {
            throw new \Exception('Usuario no encontrado.', 404);
        }
        try {
            $this->repository->delete($id);
        } catch (\PDOException $e) {
            throw new \Exception('No se puede eliminar: este usuario tiene parcelas registradas a su nombre.', 409);
        }
    }

    private function normalizeRegion(string $region): string
    {
        return in_array($region, self::DEPARTAMENTOS_VALIDOS, true) ? $region : '';
    }
}
