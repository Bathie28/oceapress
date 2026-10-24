<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->client->notifications()
            ->orderByDesc('dateEnvoi')
            ->orderByDesc('idNotification')
            ->get();

        return view('client.notifications.index', compact('notifications'));
    }

    public function show(Request $request, int $notification): View
    {
        $notification = $request->user()->client->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if (! $notification->lu) {
            $notification->lu = true;
            $notification->save();
        }

        return view('client.notifications.show', compact('notification'));
    }
}
