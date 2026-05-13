<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
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

        $recentOrders = $user->can('orders.read')
            ? Order::orderByDesc('id')->limit(10)->get()
            : [];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
