<?php
namespace App\Repositories\Notifications;

use App\Utils\Database;
use PDO;

class NotificationRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUnreadByUserId(int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM maga_notificaciones WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC LIMIT 50");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAllAsRead(int $userId)
    {
        $stmt = $this->db->prepare("UPDATE maga_notificaciones SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function create(int $userId, string $title, string $message, string $type = 'info')
    {
        $stmt = $this->db->prepare("INSERT INTO maga_notificaciones (user_id, title, message, type) VALUES (:user_id, :title, :message, :type)");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function createGlobal(string $title, string $message, string $type = 'info')
    {
        // Inserta la notificación para todos los usuarios activos
        $stmt = $this->db->prepare("
            INSERT INTO maga_notificaciones (user_id, title, message, type)
            SELECT id, :title, :message, :type FROM maga_usuarios WHERE activo = 1
        ");
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        return $stmt->execute();
    }
}
