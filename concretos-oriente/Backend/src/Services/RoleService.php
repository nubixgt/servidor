<?php
namespace App\Services;

use App\Repositories\RoleRepository;
use App\Entities\Role;
use Exception;

class RoleService
{
    private RoleRepository $roleRepository;

    public function __construct()
    {
        $this->roleRepository = new RoleRepository();
    }

    public function getAllRoles(): array
    {
        $roles = $this->roleRepository->findAll();
        return array_map(fn($r) => $r->toArray(), $roles);
    }

    public function getRoleById(int $id): ?array
    {
        $role = $this->roleRepository->findById($id);
        return $role ? $role->toArray() : null;
    }

    public function createRole(array $data): array
    {
        if (empty($data['name'])) {
            throw new Exception("El nombre del rol es requerido");
        }

        $role = new Role($data);
        $created = $this->roleRepository->create($role);
        return $created->toArray();
    }

    public function updateRole(int $id, array $data): void
    {
        $role = $this->roleRepository->findById($id);
        if (!$role) {
            throw new Exception("Rol no encontrado");
        }

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }
        if (isset($data['description'])) {
            $role->description = $data['description'];
        }
        if (isset($data['permissions'])) {
            $role->permissions = is_string($data['permissions']) ? json_decode($data['permissions'], true) : $data['permissions'];
        }

        $this->roleRepository->update($role);
    }

    public function deleteRole(int $id): void
    {
        $this->roleRepository->delete($id);
    }
}
