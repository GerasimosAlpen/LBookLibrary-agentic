<?php

namespace App\Repositories;

use App\Models\Notification;

class NotificationRepository
{
    public function getByUserId(int $userId)
    {
        return Notification::where('user_id', $userId)->latest()->get();
    }

    public function findById(int $id)
    {
        return Notification::findOrFail($id);
    }

    public function markAsRead(int $id)
    {
        $notification = $this->findById($id);
        $notification->update(['is_read' => true]);
        return $notification;
    }

    public function getUnreadCount(int $userId)
    {
        return Notification::where('user_id', $userId)->where('is_read', false)->count();
    }
}
