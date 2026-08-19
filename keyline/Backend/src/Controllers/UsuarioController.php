<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\UsuarioDTO;
use App\Services\UsuarioService;
use App\Services\AuthService;

class UsuarioController extends Controller
{
    #[Route('/usuarios', 'GET')]
    #[Authorize(['administrador', 'supervisor'])]
    public function index()
    {
        $currentUser = AuthService::currentPayload();
        $service = new UsuarioService();
        $usuarios = $service->listar($currentUser);
        $this->json(['users' => array_map(fn($u) => $u->toPublicArray(), $usuarios)]);
    }

    #[Route('/usuarios', 'POST')]
    #[Authorize(['administrador'])]
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = UsuarioDTO::fromRequest($data);
        $service = new UsuarioService();
        try {
            $usuario = $service->crear($dto);
            $this->json(['user' => $usuario->toPublicArray()], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/usuarios/{id}', 'PUT')]
    #[Authorize(['administrador'])]
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = UsuarioDTO::fromRequest($data);
        $service = new UsuarioService();
        try {
            $usuario = $service->actualizar((int)$id, $dto);
            $this->json(['user' => $usuario->toPublicArray()]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/usuarios/{id}', 'DELETE')]
    #[Authorize(['administrador'])]
    public function delete($id)
    {
        $currentUser = AuthService::currentPayload();
        $service = new UsuarioService();
        try {
            $service->eliminar((int)$id, $currentUser);
            $this->json(['ok' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    private function statusFor(\Exception $e): int
    {
        $code = $e->getCode();
        return in_array($code, [400, 401, 403, 404, 409], true) ? $code : 500;
    }
}
