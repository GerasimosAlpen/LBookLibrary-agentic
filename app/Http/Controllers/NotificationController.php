<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $notifications = $this->notificationService->getNotificationsForUser(Auth::id());
        return view('users.notifications', compact('notifications'));
    }

    public function markAsRead(int $notifId)
    {
        $this->notificationService->markAsRead($notifId, Auth::user());
        return redirect()->back()->with('success', 'Notification marked as read.');
    }
}
