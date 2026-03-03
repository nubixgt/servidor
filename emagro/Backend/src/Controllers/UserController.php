<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Services\UserService;
use App\Utils\Response;
use Exception;

class UserController extends Controller
{
    #[Route('/users', 'GET')]
    #[Authorize(['admin', 'vendedor'])]
    #[HasPrivilege('manage_users')]
    public function index()
    {
        $service = new UserService();
        $users = $service->getAllUsers();
        Response::success($users);
    }

    #[Route('/users', 'POST')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $service = new UserService();

        try {
            $user = $service->createUser($data);
            Response::success($user, "Usuario creado exitosamente", 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    #[Route('/users/{id}', 'PUT')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $service = new UserService();

        try {
            $user = $service->updateUser($id, $data);
            Response::success($user, "Usuario actualizado exitosamente");
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    #[Route('/users/{id}/status', 'PATCH')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function toggleStatus($id)
    {
        $service = new UserService();

        try {
            $newStatus = $service->toggleUserStatus($id);
            Response::success(['status' => $newStatus], "Estado actualizado exitosamente");
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
