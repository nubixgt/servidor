<?php
namespace App\DTOs\Notifications;

class NotificationDTO
{
    public $id;
    public $userId;
    public $title;
    public $message;
    public $type;
    public $isRead;
    public $createdAt;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->userId = $data['user_id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->message = $data['message'] ?? '';
        $this->type = $data['type'] ?? 'info';
        $this->isRead = (bool)($data['is_read'] ?? false);
        // Formatear el tiempo si es necesario
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'read' => $this->isRead,
            'time' => $this->formatTime($this->createdAt) // Format time relative
        ];
    }

    private function formatTime($datetime)
    {
        if (!$datetime) return 'Hace un momento';
        
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) return 'Hace un momento';
        if ($diff < 3600) return 'Hace ' . floor($diff / 60) . ' min';
        if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' horas';
        return 'Hace ' . floor($diff / 86400) . ' días';
    }
}
