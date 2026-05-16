<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index()
    {
        $types = UserActivityLog::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return Inertia::render('Admin/ActivityLogs/Index', [
            'types' => $types,
        ]);
    }

    public function getLogs(Request $request)
    {
        $query = UserActivityLog::with('user');

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        return response()->json($logs);
    }
}
