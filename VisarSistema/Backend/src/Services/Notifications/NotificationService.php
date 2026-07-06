<?php
namespace App\Services\Notifications;

use App\Repositories\Notifications\NotificationRepository;
use App\DTOs\Notifications\NotificationDTO;

class NotificationService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new NotificationRepository();
    }

    public function getUnreadNotifications(int $userId)
    {
        $records = $this->repository->getUnreadByUserId($userId);
        $notifications = [];

        foreach ($records as $record) {
            $dto = new NotificationDTO($record);
            $notifications[] = $dto->toArray();
        }

        return $notifications;
    }

    public function markAllAsRead(int $userId)
    {
        return $this->repository->markAllAsRead($userId);
    }

    public function createGlobalNotification(string $title, string $message, string $type = 'info')
    {
        return $this->repository->createGlobal($title, $message, $type);
    }
}
