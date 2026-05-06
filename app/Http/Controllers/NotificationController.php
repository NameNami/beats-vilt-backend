<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Notification $notification)
    {
        // Security check to ensure a user only reads their own notifications
        if (auth()->id() !== $notification->user_id) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        // return back() tells Inertia to refresh the page data without reloading the browser
        return back();
    }

    public function markAllAsRead()
    {
        auth()->user()->appNotifications()->where('is_read', false)->update(['is_read' => true]);
        return back();
    }
}

