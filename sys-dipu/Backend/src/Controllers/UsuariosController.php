<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Models\UsuarioModel;

class UsuariosController extends Controller
{
    #[Route('/usuarios', 'GET')]
    public function index()
    {
        $model = new UsuarioModel();
        $usuarios = $model->getAll();

        $this->json([
            'status' => 'success',
            'data' => $usuarios
        ]);
    }

    #[Route('/usuarios/{id}', 'DELETE')]
    public function destroy($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['error' => 'ID de usuario inválido'], 400);
            return;
        }

        $model = new UsuarioModel();
        $success = $model->delete((int)$id);

        if ($success) {
            $this->json(['status' => 'success', 'message' => 'Usuario eliminado correctamente']);
        } else {
            $this->json(['error' => 'No se pudo eliminar el usuario'], 500);
        }
    }
}
