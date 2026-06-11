<?php

namespace App\Http\Controllers;

use App\Services\AbsenceMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user (JSON API).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->paginate(10);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'type' => $n->data['type'] ?? 'general',
                'severity' => $n->data['severity'] ?? 'info',
                'time' => $n->created_at->diffForHumans(),
                'action_url' => $n->data['action_url'] ?? null,
                'action_text' => $n->data['action_text'] ?? null,
            ]),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'has_more' => $notifications->hasMorePages(),
            ]
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark ALL notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    /**
     * Get notification count only (for bell badge polling).
     */
    public function count()
    {
        return response()->json([
            'unread_count' => Auth::user()->unreadNotifications()->count()
        ]);
    }
}