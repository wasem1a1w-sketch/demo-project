<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $user = request()->user();

        $stats = [];

        if ($user->can('products.read')) {
            $stats['products'] = Product::count();
        }

        if ($user->can('orders.read')) {
            $stats['orders'] = Order::count();
            $stats['pendingOrders'] = Order::where('status', 'pending')->count();
            $stats['revenue'] = Order::where('payment_status', 'paid')->sum('total');
        }

        $startDate = Carbon::today()->subDays(6)->startOfDay();
        $endDate = Carbon::today()->endOfDay();
        $period = collect(range(6, 0))->map(fn ($days) => Carbon::today()->subDays($days)->format('Y-m-d'));

        $ordersByDay = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $revenueByDay = Order::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $chartLabels = $period->map(fn ($date) => Carbon::parse($date)->format('M j'))->all();
        $orderChartSeries = $period->map(fn ($date) => $ordersByDay[$date] ?? 0)->all();
        $revenueChartSeries = $period->map(fn ($date) => round($revenueByDay[$date] ?? 0, 2))->all();

        $recentOrders = $user->can('orders.read')
            ? Order::with('user:id,name')->orderByDesc('id')->limit(10)->get()
            : [];

        $this->runSlowDemoQueries();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'orderChartLabels' => $chartLabels,
            'orderChartSeries' => $orderChartSeries,
            'revenueChartLabels' => $chartLabels,
            'revenueChartSeries' => $revenueChartSeries,
        ]);
    }

    private function runSlowDemoQueries(): void
    {
        if (! config('app.slow_demo')) {
            return;
        }

        DB::table('user_activity_logs')
            ->orderByRaw('RAND()')
            ->limit(100)
            ->get();

        DB::table('user_activity_logs')
            ->selectRaw('SUBSTRING(description, 1, 25) AS phrase, COUNT(*) AS total')
            ->groupByRaw('SUBSTRING(description, 1, 25)')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(500)
            ->get();

        DB::table('user_activity_logs')
            ->whereRaw('description REGEXP "[0-9]{4}" AND user_agent LIKE "%bot%"')
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(200)
            ->get();
    }
}
