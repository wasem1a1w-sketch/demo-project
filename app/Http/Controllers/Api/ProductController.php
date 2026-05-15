<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $sub = DB::table('product_reviews')
            ->selectRaw('COALESCE(AVG(rating), 0)')
            ->whereColumn('product_id', 'products.id')
            ->where('is_approved', 1);

        $query = Product::with(['images', 'category'])
            ->active()
            ->withCount('images')
            ->addSelect(['reviews_avg_rating' => $sub])
            ->withCount(['reviews' => fn ($q) => $q->where('is_approved', true)]);

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

        $sort = $request->sort ?? 'newest';

        if ($sort === 'price_low') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_high') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'name') {
            $query->orderBy('name', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate($request->per_page ?? 12);

        return response()->json($products);
    }

    public function show($slug)
    {
        $sub = DB::table('product_reviews')
            ->selectRaw('COALESCE(AVG(rating), 0)')
            ->whereColumn('product_id', 'products.id')
            ->where('is_approved', 1);

        $product = Product::with(['images', 'category'])
            ->where('slug', $slug)
            ->active()
            ->addSelect(['reviews_avg_rating' => $sub])
            ->withCount(['reviews' => fn ($q) => $q->where('is_approved', true)])
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
