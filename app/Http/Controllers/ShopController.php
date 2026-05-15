<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::with(['images', 'category'])
            ->active()
            ->featured()
            ->limit(8)
            ->get();

        $categories = Category::active()
            ->withCount('products')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->limit(8)
            ->get();

        return Inertia::render('Welcome', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }

    public function shop()
    {
        return Inertia::render('Shop/Index');
    }

    public function product($slug)
    {
        return Inertia::render('Shop/Product', [
            'slug' => $slug,
        ]);
    }

    public function cart()
    {
        return Inertia::render('Shop/Cart');
    }

    public function checkout()
    {
        return Inertia::render('Checkout/Index');
    }

    public function categories()
    {
        $categories = Category::active()->orderBy('order')->with('children')->get();

        return Inertia::render('Shop/Categories', [
            'categories' => $categories,
        ]);
    }

    public function wishlist()
    {
        return Inertia::render('Shop/Wishlist');
    }
}
