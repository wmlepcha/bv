<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'title' => (string) ($notification->data['title'] ?? 'Notification'),
                'body' => (string) ($notification->data['body'] ?? ''),
                'url' => isset($notification->data['url']) ? (string) $notification->data['url'] : null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('app/student/notifications', [
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
