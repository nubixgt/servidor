<?php
namespace App\Controllers;

use App\Services\RoleService;
use App\Utils\Response;

class RoleController
{
    private RoleService $roleService;

    public function __construct()
    {
        $this->roleService = new RoleService();
    }

    public function getRoutes(): array
    {
        return [
            'GET' => [
                '/api/v1/roles' => 'getAllRoles',
                '/api/v1/roles/(?P<id>\d+)' => 'getRole'
            ],
            'POST' => [
                '/api/v1/roles' => 'createRole'
            ],
            'PUT' => [
                '/api/v1/roles/(?P<id>\d+)' => 'updateRole'
            ],
            'DELETE' => [
                '/api/v1/roles/(?P<id>\d+)' => 'deleteRole'
            ]
        ];
    }

    public function getAllRoles(): void
    {
        try {
            $roles = $this->roleService->getAllRoles();
            Response::json($roles);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getRole(int $id): void
    {
        try {
            $role = $this->roleService->getRoleById($id);
            if (!$role) {
                Response::error("Rol no encontrado", 404);
                return;
            }
            Response::json($role);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function createRole(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $role = $this->roleService->createRole($data);
            Response::json($role, 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateRole(int $id): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $this->roleService->updateRole($id, $data);
            Response::json(['message' => 'Rol actualizado correctamente']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteRole(int $id): void
    {
        try {
            $this->roleService->deleteRole($id);
            Response::json(['message' => 'Rol eliminado correctamente']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
