<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Exceptions\ApiException;
use App\Services\UserService;
use App\Utils\Auth;

// Solo administradores.
//   GET  /users                                           -> [user]
//   POST /users { username, fullName, password, role }    -> 201 user
//   PUT  /users/{id} { fullName?, role?, active?, password? } -> user
class UserController extends Controller
{
    private function body(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json(['error' => 'Solicitud inválida.'], 400);
        }
        return $data;
    }

    #[Route('/users', 'GET')]
    #[Authorize(['admin'])]
    public function index()
    {
        header('Cache-Control: no-store');
        try {
            $this->json((new UserService())->getAll());
        } catch (ApiException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    #[Route('/users', 'POST')]
    #[Authorize(['admin'])]
    public function create()
    {
        try {
            $this->json((new UserService())->create($this->body()), 201);
        } catch (ApiException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    #[Route('/users/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        try {
            $this->json((new UserService())->update((int) $id, $this->body(), Auth::user()['id']));
        } catch (ApiException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}
