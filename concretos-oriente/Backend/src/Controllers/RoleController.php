<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\RoleService;
use Exception;

class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct()
    {
        $this->roleService = new RoleService();
    }

    #[Route('/roles', 'GET')]
    public function index()
    {
        try {
            $roles = $this->roleService->getAllRoles();
            $this->json($roles);
        } catch (Exception $e) {
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/roles/{id}', 'GET')]
    public function show($id)
    {
        try {
            $role = $this->roleService->getRoleById((int)$id);
            if (!$role) {
                $this->json(["status" => "error", "message" => "Rol no encontrado"], 404);
                return;
            }
            $this->json($role);
        } catch (Exception $e) {
            $this->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    #[Route('/roles', 'POST')]
    public function store()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $role = $this->roleService->createRole($data);
            $this->json($role, 201);
        } catch (Exception $e) {
            $this->json(["status" => "error", "message" => $e->getMessage()], 400);
        }
    }

    #[Route('/roles/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $this->roleService->updateRole((int)$id, $data);
            $this->json(['status' => 'success', 'message' => 'Rol actualizado correctamente']);
        } catch (Exception $e) {
            $this->json(["status" => "error", "message" => $e->getMessage()], 400);
        }
    }

    #[Route('/roles/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->roleService->deleteRole((int)$id);
            $this->json(['status' => 'success', 'message' => 'Rol eliminado correctamente']);
        } catch (Exception $e) {
            $this->json(["status" => "error", "message" => $e->getMessage()], 400);
        }
    }
}
