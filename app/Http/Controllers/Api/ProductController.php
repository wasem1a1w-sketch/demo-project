<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['images', 'category'])
            ->active()
            ->withCount('images');

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->featured) {
            $query->featured();
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 12);

        return response()->json($products);
    }

    public function show($slug)
    {
        $product = Product::with(['images', 'category'])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        return response()->json($product);
    }

    public function categories()
    {
        $categories = Category::active()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return response()->json($categories);
    }
}
