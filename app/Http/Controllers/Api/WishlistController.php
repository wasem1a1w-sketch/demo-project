<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $exists = WishlistItem::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->exists();

        if (!$exists) {
            WishlistItem::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
            ]);
        }

        return response()->json([
            'items' => $this->getWishlist(),
        ]);
    }

    public function remove($id)
    {
        WishlistItem::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'items' => $this->getWishlist(),
        ]);
    }

    public function clear()
    {
        WishlistItem::where('user_id', auth()->id())->delete();

        return response()->json([
            'items' => [],
        ]);
    }

    private function getWishlist()
    {
        return WishlistItem::where('user_id', auth()->id())
            ->with('product.primaryImage')
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'price' => $item->product->price,
                        'image' => $item->product->primaryImage?->image_path,
                        'in_stock' => $item->product->stock > 0,
                    ] : null,
                ];
            });
    }
}
