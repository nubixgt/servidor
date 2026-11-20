<?php
namespace App\Controllers;

use App\Attributes\Authorize;
use App\Attributes\Route;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\DTOs\UsuarioDTO;
use App\Services\UsuarioService;
use App\Utils\Response;

/** Gestión de cuentas: todo el controlador es exclusivo del administrador. */
class UsuarioController extends Controller
{
    #[Route('/usuarios', 'GET')]
    #[Authorize(['admin'])]
    public function index()
    {
        Response::success((new UsuarioService())->listar());
    }

    #[Route('/usuarios', 'POST')]
    #[Authorize(['admin'])]
    public function crear()
    {
        $r = (new UsuarioService())->crear(UsuarioDTO::fromRequest($this->cuerpo()));
        Response::success($r, 'Usuario creado', 201);
    }

    #[Route('/usuarios/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function actualizar($id)
    {
        (new UsuarioService())->actualizar($this->id($id), Auth::userId(), UsuarioDTO::fromRequest($this->cuerpo()));
        Response::success([], 'Usuario actualizado');
    }

    #[Route('/usuarios/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function eliminar($id)
    {
        (new UsuarioService())->eliminar($this->id($id), Auth::userId());
        Response::success([], 'Usuario eliminado');
    }

    private function cuerpo(): array
    {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    private function id($valor): int
    {
        if (!ctype_digit((string) $valor) || (int) $valor < 1) {
            throw new HttpException('Identificador inválido', 400);
        }
        return (int) $valor;
    }
}
