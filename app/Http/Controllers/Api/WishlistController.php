<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WishlistRequest;
use App\Models\Product;
use App\Models\UserActivityLog;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        return response()->json([
            'items' => $this->getWishlist(),
        ]);
    }

    public function add(WishlistRequest $request)
    {
        $exists = WishlistItem::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->exists();

        if (!$exists) {
            WishlistItem::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
            ]);
        }

        $product = Product::find($request->product_id);
        UserActivityLog::record(auth()->id(), 'wishlist_item_added', "Item added to wishlist: {$product?->name}");

        return response()->json([
            'items' => $this->getWishlist(),
        ]);
    }

    public function remove($id)
    {
        $item = WishlistItem::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        $productName = $item?->product?->name;

        if ($item) {
            $item->delete();
        }

        UserActivityLog::record(auth()->id(), 'wishlist_item_removed', "Item removed from wishlist: {$productName}");

        return response()->json([
            'items' => $this->getWishlist(),
        ]);
    }

    public function clear()
    {
        WishlistItem::where('user_id', auth()->id())->delete();

        UserActivityLog::record(auth()->id(), 'wishlist_cleared', 'Wishlist cleared');

        return response()->json([
            'items' => [],
        ]);
    }

    private function getWishlist()
    {
        return WishlistItem::where('user_id', auth()->id())
            ->with('product.primaryImage')
            ->withAvg(['product.reviews' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->withCount(['product.reviews' => fn ($q) => $q->where('is_approved', true)])
            ->latest()
            ->get()
            ->map(function ($item) {
                $product = $item->product;
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => $product->price,
                        'compare_price' => $product->compare_price,
                        'image' => $product->primaryImage?->thumb_path,
                        'in_stock' => $product->stock > 0,
                        'reviews_avg_rating' => (float) ($item->product_reviews_avg_rating ?? 0),
                        'reviews_count' => (int) ($item->product_reviews_count ?? 0),
                    ] : null,
                ];
            });
    }
}
