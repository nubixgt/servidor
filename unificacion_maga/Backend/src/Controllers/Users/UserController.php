<?php
namespace App\Controllers\Users;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Users\UserService;

class UserController extends Controller
{
    private $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    #[Route('/users', 'GET')]
    public function index()
    {
        try {
            $data = $this->userService->getUsers();
            // Process the data array to have nombres and apellidos, and parse permissions
            foreach ($data as &$user) {
                $parts = explode(' ', $user['nombre_completo'], 2);
                $user['nombres'] = $parts[0] ?? '';
                $user['apellidos'] = $parts[1] ?? '';
                $user['role'] = $user['rol'] ?? '';
                $user['status'] = $user['activo'] ?? 1;
                $user['permissions'] = isset($user['permisos']) ? json_decode($user['permisos'], true) : [];
            }
            $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/users/{id}', 'GET')]
    public function show($id)
    {
        try {
            $user = $this->userService->getUser($id);
            if ($user) {
                $this->json(['success' => true, 'data' => $user]);
            } else {
                $this->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/users', 'POST')]
    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = $this->userService->createUser($data);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nuevo Usuario", "Se ha registrado un nuevo usuario en el sistema MAGA.", "info"
            );
            $this->json(['success' => true, 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/users/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $this->userService->updateUser($id, $data);
            $this->json(['success' => true, 'message' => 'Usuario actualizado']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/users/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $this->userService->deleteUser($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Usuario Eliminado", "Se ha dado de baja a un usuario del sistema.", "warning"
            );
            $this->json(['success' => true, 'message' => 'Usuario eliminado']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/users/{id}/reset-password', 'POST')]
    public function resetPassword($id)
    {
        // Require ADMIN role
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $token = $matches[1];
        $payload = \App\Utils\JwtUtils::validate($token);
        $userRole = strtoupper($payload['role'] ?? $payload['rol'] ?? '');
        
        if (!$payload || $userRole !== 'ADMIN') {
            $this->json(['success' => false, 'message' => 'Permisos insuficientes. Solo administradores pueden restablecer contraseñas.'], 403);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['password']) || strlen($data['password']) < 6) {
                $this->json(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
                return;
            }

            // Using direct update instead of userService->updateUser to avoid triggering name splitting logic if missing
            $userRepo = new \App\Repositories\Auth\UserRepository();
            $success = $userRepo->update($id, ['password' => password_hash($data['password'], PASSWORD_BCRYPT)]);
            
            if ($success) {
                // Log action
                (new \App\Services\Audit\AuditService())->log($payload['id'], 'UPDATE', 'maga_usuarios', $id, null, ['password' => '***RESET***']);
                
                $this->json(['success' => true, 'message' => 'Contraseña restablecida correctamente']);
            } else {
                $this->json(['success' => false, 'message' => 'No se pudo restablecer la contraseña'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
