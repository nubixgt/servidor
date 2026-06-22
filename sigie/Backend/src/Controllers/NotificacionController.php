<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\NotificacionModel;
use App\Utils\JwtUtils;

class NotificacionController extends Controller
{
    private function getUserId()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            }
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $payload = JwtUtils::validate($matches[1]);
            if ($payload && isset($payload['id'])) {
                return (int)$payload['id'];
            }
        }
        return null;
    }

    /**
     * Obtener listado de notificaciones para el usuario autenticado
     */
    #[Authorize(['administrador', 'inspector'])]
    #[Route('/notificaciones', 'GET')]
    public function listar()
    {
        $userId = $this->getUserId();
        if (!$userId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $model = new NotificacionModel();
        $notificaciones = $model->getByUser($userId);
        $unreadCount = $model->getUnreadCount($userId);

        $this->json([
            'status' => 'success',
            'data' => [
                'list' => $notificaciones,
                'unread' => $unreadCount
            ]
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    #[Authorize(['administrador', 'inspector'])]
    #[Route('/notificaciones/leer-todas', 'PUT')]
    public function leerTodas()
    {
        $userId = $this->getUserId();
        if (!$userId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $model = new NotificacionModel();
        if ($model->markAllAsRead($userId)) {
            $this->json([
                'status' => 'success',
                'message' => 'Todas las notificaciones marcadas como leídas'
            ]);
        } else {
            $this->json(['error' => 'No se pudieron actualizar las notificaciones'], 500);
        }
    }
}
