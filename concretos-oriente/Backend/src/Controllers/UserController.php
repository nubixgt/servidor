<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\UserService;
use Exception;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    // GET /users
    #[Route('/users', 'GET')]
    public function index()
    {
        try {
            $users = $this->userService->getAllUsers();

            $this->json([
                "status" => "success",
                "data" => $users
            ]);
        } catch (Exception $e) {
            $this->json([
                "status" => "error",
                "message" => "Error al obtener los usuarios: " . $e->getMessage()
            ], 500);
        }
    }

    // POST /users
    #[Route('/users', 'POST')]
    public function store()
    {
        try {
            $data = [
                'nombre'      => $_POST['nombre'] ?? '',
                'usuario'     => $_POST['usuario'] ?? '',
                'passwordRaw' => $_POST['password'] ?? '',
                'rol'         => $_POST['rol'] ?? 'admin',
                'estado'      => $_POST['estado'] ?? 'Activo',
                'permisos'    => $_POST['permisos'] ?? null,
            ];

            $fotoFile = $_FILES['foto'] ?? null;

            $this->userService->createUser($data, $fotoFile);

            $this->json([
                "status" => "success",
                "message" => "Usuario creado exitosamente"
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => "Error al guardar: " . $e->getMessage()
            ], $code);
        }
    }

    // POST /users/{id} (Update - Usamos POST por FormData con archivos)
    #[Route('/users/{id}', 'POST')]
    public function update($id)
    {
        try {
            $data = [
                'nombre'      => $_POST['nombre'] ?? null,
                'usuario'     => $_POST['usuario'] ?? null,
                'rol'         => $_POST['rol'] ?? null,
                'estado'      => $_POST['estado'] ?? null,
                'permisos'    => $_POST['permisos'] ?? null,
                'passwordRaw' => $_POST['password'] ?? '',
            ];

            $fotoFile = $_FILES['foto'] ?? null;

            $this->userService->updateUser((int)$id, $data, $fotoFile);

            $this->json([
                "status" => "success",
                "message" => "Usuario actualizado exitosamente"
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => "Error al actualizar: " . $e->getMessage()
            ], $code);
        }
    }

    // DELETE /users/{id}
    #[Route('/users/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->userService->deleteUser((int)$id);

            $this->json([
                "status" => "success",
                "message" => "Usuario eliminado exitosamente"
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => "Error al eliminar: " . $e->getMessage()
            ], $code);
        }
    }
}
