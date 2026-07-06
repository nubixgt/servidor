<?php
namespace App\Controllers\Settings;

use App\Core\Controller;
use App\Attributes\Route;

class SettingsController extends Controller
{
    private function getUserIdFromToken()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = \App\Utils\JwtUtils::validate($token);
            if ($payload && isset($payload['id'])) {
                return $payload['id'];
            }
        }
        return null;
    }

    #[Route('/settings/profile', 'GET')]
    public function getProfile()
    {
        try {
            $userId = $this->getUserIdFromToken();
            if (!$userId) {
                $this->json(['success' => false, 'message' => 'No autorizado'], 401);
                return;
            }

            $userService = new \App\Services\Users\UserService();
            $user = $userService->getUser($userId);

            if (!$user) {
                $this->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
                return;
            }

            $this->json([
                'success' => true,
                'data' => [
                    'nombres' => $user['nombres'],
                    'apellidos' => $user['apellidos'],
                    'email' => $user['email'],
                    'puesto_funcional' => $user['puesto_funcional'] ?? '',
                    'ubicacion_laboral' => $user['ubicacion_laboral'] ?? '',
                    'preferences' => [
                        'email_alerts' => true,
                        'dark_mode' => false
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/settings/profile', 'POST')]
    public function updateProfile()
    {
        $userId = $this->getUserIdFromToken();
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userService = new \App\Services\Users\UserService();
        
        try {
            $userService->updateUser($userId, $data);
            $this->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/settings/password', 'POST')]
    public function updatePassword()
    {
        $userId = $this->getUserIdFromToken();
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        $userRepo = new \App\Repositories\Auth\UserRepository();
        $db = \App\Utils\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT password FROM maga_usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$userRow || !password_verify($currentPassword, $userRow['password'])) {
            $this->json(['success' => false, 'error' => 'La contraseña actual es incorrecta'], 400);
            return;
        }

        $userService = new \App\Services\Users\UserService();
        try {
            $userService->updateUser($userId, ['password' => $newPassword]);
            $this->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/settings/preferences', 'POST')]
    public function updatePreferences()
    {
        $this->json([
            'success' => true,
            'message' => 'Preferencias actualizadas'
        ]);
    }

    #[Route('/settings/system', 'GET')]
    public function getSystemSettings()
    {
        $userId = $this->getUserIdFromToken();
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        // Verify ADMIN (accept both 'role' and 'rol' for backward compat)
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
        $payload = \App\Utils\JwtUtils::validate($matches[1] ?? '');
        $userRole = $payload['role'] ?? $payload['rol'] ?? '';
        if (!$payload || strtoupper($userRole) !== 'ADMIN') {
            $this->json(['success' => false, 'message' => 'Permisos insuficientes'], 403);
            return;
        }

        $repo = new \App\Repositories\Settings\SystemSettingsRepository();
        $settingsRaw = $repo->getAll();
        
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['key']] = [
                'value' => $row['value'],
                'description' => $row['description']
            ];
        }

        $this->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    #[Route('/settings/system', 'POST')]
    public function updateSystemSettings()
    {
        $userId = $this->getUserIdFromToken();
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        // Verify ADMIN (accept both 'role' and 'rol' for backward compat)
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
        $payload = \App\Utils\JwtUtils::validate($matches[1] ?? '');
        $userRole = $payload['role'] ?? $payload['rol'] ?? '';
        if (!$payload || strtoupper($userRole) !== 'ADMIN') {
            $this->json(['success' => false, 'message' => 'Permisos insuficientes'], 403);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
            return;
        }

        $repo = new \App\Repositories\Settings\SystemSettingsRepository();
        $success = $repo->updateMultiple($data['settings']);

        if ($success) {
            // Log this action
            (new \App\Services\Audit\AuditService())->log($userId, 'UPDATE', 'maga_settings', 'multiple_keys', null, $data['settings']);
            
            $this->json(['success' => true, 'message' => 'Configuraciones del sistema actualizadas']);
        } else {
            $this->json(['success' => false, 'message' => 'Error al actualizar configuraciones'], 500);
        }
    }
}
