<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $notifications = AppNotification::where('user_id', $user->id)
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($n) => [
                'id'      => $n->id,
                'type'    => $n->type,
                'message' => $n->message,
                'data'    => $n->data,
                'read'    => $n->read_at !== null,
                'time'    => $n->created_at?->diffForHumans() ?? 'Just now',
            ]);

        $unread = AppNotification::where('user_id', $user->id)->whereNull('read_at')->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unread,
        ]);
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['count' => 0]);
        }

        $count = AppNotification::where('user_id', $user->id)->whereNull('read_at')->count();

        return response()->json(['count' => $count]);
    }

    // POST /api/notifications/{id}/read
    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $notif = AppNotification::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        if (! $notif->read_at) {
            $notif->read_at = now();
            $notif->save();
        }

        return response()->json(['message' => 'Marked as read']);
    }

    // POST /api/notifications/read-all
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        AppNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
