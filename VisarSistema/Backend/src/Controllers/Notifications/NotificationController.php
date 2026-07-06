<?php
namespace App\Controllers\Notifications;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Utils\Response;
use App\Utils\JwtUtils;
use App\Services\Notifications\NotificationService;

class NotificationController extends Controller
{
    private $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    #[Route('/notifications', 'GET')]
    // #[Authorize(['admin', 'user', 'tecnico'])] // Descomenta si usas roles
    public function getNotifications()
    {
        // 1. Obtener ID del usuario del token
        // Esto depende de cómo manejes JwtUtils. Asumimos que podemos obtenerlo así:
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        // Si no hay auth estricta aún, usamos el ID 1 por defecto para la demostración
        $userId = 1; 

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = JwtUtils::validate($token);
            if ($payload && isset($payload['id'])) {
                $userId = $payload['id'];
            }
        }

        // 2. Obtener notificaciones
        $notifications = $this->notificationService->getUnreadNotifications($userId);
        
        Response::json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    #[Route('/notifications/read-all', 'PUT')]
    // #[Authorize(['admin', 'user', 'tecnico'])]
    public function markAllAsRead()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $userId = 1; 

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = JwtUtils::validate($token);
            if ($payload && isset($payload['id'])) {
                $userId = $payload['id'];
            }
        }

        $success = $this->notificationService->markAllAsRead($userId);

        if ($success) {
            Response::json(['status' => 'success', 'message' => 'Notificaciones marcadas como leídas']);
        } else {
            Response::json(['status' => 'error', 'message' => 'No se pudieron actualizar las notificaciones'], 500);
        }
    }

    #[Route('/notifications/global', 'POST')]
    // #[Authorize(['admin'])] // Requiere permisos de administrador
    public function createGlobal()
    {
        // Leer el body (JSON)
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['title']) || !isset($input['message'])) {
            Response::json(['status' => 'error', 'message' => 'Faltan parámetros requeridos (title, message)'], 400);
        }

        $title = $input['title'];
        $message = $input['message'];
        $type = $input['type'] ?? 'info';

        $success = $this->notificationService->createGlobalNotification($title, $message, $type);

        if ($success) {
            Response::json(['status' => 'success', 'message' => 'Notificación global enviada correctamente']);
        } else {
            Response::json(['status' => 'error', 'message' => 'Error al enviar la notificación global'], 500);
        }
    }
}
