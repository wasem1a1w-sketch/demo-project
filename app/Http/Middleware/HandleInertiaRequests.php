<?php

namespace App\Http\Middleware;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        $user = Auth::user();
        $userData = null;

        if ($user) {
            $isAdmin = $user->can('admin.access');
            $unreadAdminNotifications = 0;

            if ($isAdmin) {
                $readIds = DB::table('admin_notification_user')
                    ->where('user_id', $user->id)
                    ->pluck('admin_notification_id');
                $unreadAdminNotifications = AdminNotification::whereNotIn('id', $readIds)->count();
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'unread_notifications' => $user->unreadNotifications()->count(),
                'unread_admin_notifications' => $unreadAdminNotifications,
            ];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $userData,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
