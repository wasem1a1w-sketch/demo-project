<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = AdminNotification::with(['readByUsers' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])->latest()->paginate(20);

        return response()->json($notifications);
    }

    public function unread(Request $request)
    {
        $user = $request->user();

        $readIds = DB::table('admin_notification_user')
            ->where('user_id', $user->id)
            ->pluck('admin_notification_id');

        $notifications = AdminNotification::whereNotIn('id', $readIds)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'count' => AdminNotification::whereNotIn('id', $readIds)->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        DB::table('admin_notification_user')->updateOrInsert(
            [
                'admin_notification_id' => $id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['message' => 'ok']);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();

        $unreadIds = AdminNotification::whereNotIn('id', function ($q) use ($user) {
            $q->select('admin_notification_id')
                ->from('admin_notification_user')
                ->where('user_id', $user->id);
        })->pluck('id');

        $now = now();
        $inserts = $unreadIds->map(fn ($id) => [
            'admin_notification_id' => $id,
            'user_id' => $user->id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if (!empty($inserts)) {
            DB::table('admin_notification_user')->insert($inserts);
        }

        return response()->json(['message' => 'ok']);
    }
}
