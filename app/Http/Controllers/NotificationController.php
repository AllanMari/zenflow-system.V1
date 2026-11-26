<?php

namespace App\Http\Controllers;

use App\Notifications\SystemNotification;
use App\Services\AttendanceReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    /**
     * Fire a database notification from anywhere.
     */
    public static function sendTo(
        $recipients,
        string $title,
        string $message,
        string $type = 'general',
        string $severity = 'info',
        ?string $url = null,
        ?string $actionText = 'View',
        array $extra = []
    ): void {
        $payload = [
            'title'       => $title,
            'message'     => $message,
            'type'        => $type,
            'severity'    => $severity,
            'action_url'  => $url,
            'action_text' => $actionText,
        ];

        $payload = array_merge($payload, $extra);

        $notification = new SystemNotification($payload);

        if ($recipients instanceof \Illuminate\Database\Eloquent\Collection) {
            Notification::send($recipients, $notification);
        } elseif ($recipients) {
            $recipients->notify($notification);
        }
    }

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
            'unread_count' => $notifications->total(),
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
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

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

    /**
     * Lightweight 10-second feed for the receptionist floating toasts and bell.
     */
    public function attendanceFeed()
    {
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->limit(20)
            ->get()
            ->filter(fn ($notification) => ($notification->data['type'] ?? '') === 'attendance')
            ->values()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Attendance reminder',
                'message' => $notification->data['message'] ?? '',
                'stage' => $notification->data['stage'] ?? null,
                'appointment_id' => $notification->data['appointment_id'] ?? null,
                'action_url' => $notification->data['action_url'] ?? null,
                'action_text' => $notification->data['action_text'] ?? 'Open Appointment',
                'time' => $notification->created_at->diffForHumans(),
            ])
            ->all();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
            'followups' => app(AttendanceReminderService::class)->followUps(),
        ]);
    }

    /**
     * Customer-side feed for floating appointment reminders. Reuses the same
     * notifications table and SystemNotification payload as the receptionist.
     */
    public function customerAttendanceFeed()
    {
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->limit(20)
            ->get()
            ->filter(fn ($notification) => ($notification->data['type'] ?? '') === 'attendance')
            ->values()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Appointment reminder',
                'message' => $notification->data['message'] ?? '',
                'stage' => $notification->data['stage'] ?? null,
                'appointment_id' => $notification->data['appointment_id'] ?? null,
                'action_url' => $notification->data['action_url'] ?? null,
                'action_text' => $notification->data['action_text'] ?? 'Open Appointment',
                'time' => $notification->created_at->diffForHumans(),
            ])
            ->all();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }
}