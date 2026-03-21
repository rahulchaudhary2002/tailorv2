<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        /** @var DatabaseNotification|null $notificationRecord */
        $notificationRecord = $user->notifications()->whereKey($notification)->first();
        abort_unless($notificationRecord, 404);

        if ($notificationRecord->read_at === null) {
            $notificationRecord->markAsRead();
        }

        $redirectTo = (string) data_get($notificationRecord->data, 'url', route('dashboard'));

        return redirect()->to($redirectTo);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Notifications marked as read.');
    }
}
