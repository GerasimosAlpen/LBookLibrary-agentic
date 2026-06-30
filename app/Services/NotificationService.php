<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    protected $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function getNotificationsForUser(int $userId)
    {
        return $this->notificationRepository->getByUserId($userId);
    }

    public function markAsRead(int $notifId, $currentUser)
    {
        $notification = $this->notificationRepository->findById($notifId);

        if ($notification->user_id !== $currentUser->id) {
            throw ValidationException::withMessages(['notification' => 'Unauthorized action']);
        }

        return $this->notificationRepository->markAsRead($notifId);
    }

    public function getUnreadCount(int $userId)
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }
}
