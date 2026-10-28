<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20)->through(fn ($notification) => [
            'id' => $notification->id,
            'title' => $notification->data['title'] ?? 'Notification',
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at->toIso8601String(),
        ]);

        return Inertia::render('Notifications/Index', ['notifications' => $notifications]);
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
