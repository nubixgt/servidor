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

    #[Route('/usuarios', 'POST')]
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate basic fields
        if (empty($data['nombre_completo']) || empty($data['usuario']) || empty($data['password_hash']) || empty($data['rol'])) {
            $this->json(['error' => 'Faltan campos obligatorios'], 400);
            return;
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password_hash'], PASSWORD_BCRYPT);

        // Sanitize categoria if rol is admin
        if ($data['rol'] === 'administrador') {
            $data['categoria_asignada'] = null;
        }

        $model = new UsuarioModel();

        try {
            $model->create($data);
            $this->json(['status' => 'success', 'message' => 'Usuario creado exitosamente']);
        } catch (\PDOException $e) {
            // Error 23000 is integrity constraint violation (e.g. duplicate unique key)
            if ($e->getCode() == 23000) {
                $this->json(['error' => 'El nombre de usuario ya está registrado'], 409);
            } else {
                $this->json(['error' => 'Error en la base de datos'], 500);
            }
        }
    }

    #[Route('/usuarios/{id}/toggle', 'PUT')]
    public function toggle($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!isset($data['estado'])) {
            $this->json(['error' => 'Falta el parámetro estado'], 400);
            return;
        }

        $model = new UsuarioModel();
        $success = $model->toggleStatus((int)$id, (int)$data['estado']);

        if ($success) {
            $this->json(['status' => 'success', 'message' => 'Estado actualizado correctament']);
        } else {
            $this->json(['error' => 'No se pudo actualizar el estado'], 500);
        }
    }
}
