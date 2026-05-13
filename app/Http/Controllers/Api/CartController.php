<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartController extends Controller
{
    protected function getSessionId()
    {
        if (! Session::has('cart_session')) {
            Session::put('cart_session', Str::uuid()->toString());
        }

        return Session::get('cart_session');
    }

    protected function getCart()
    {
        return \DB::table('cart_items')
            ->where('session_id', $this->getSessionId())
            ->get()
            ->map(function ($item) {
                $product = Product::with('images')->find($item->product_id);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => $product->price,
                        'stock' => $product->stock,
                        'images' => $product->images->map(fn ($img) => ['image_path' => $img->image_path])->toArray(),
                    ] : null,
                ];
            })
            ->filter(fn ($item) => $item['product'] !== null)
            ->values();
    }

    protected function getCoupon()
    {
        $couponId = Session::get('cart_coupon_id');
        if (! $couponId) {
            return null;
        }
        $coupon = Coupon::find($couponId);

        return $coupon && $coupon->isValid() ? $coupon : null;
    }

    public function index()
    {
        return response()->json([
            'items' => $this->getCart(),
            'coupon' => $this->getCoupon(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:99',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < ($request->quantity ?? 1)) {
            return response()->json(['message' => 'Insufficient stock'], 422);
        }

        $sessionId = $this->getSessionId();
        $quantity = $request->quantity ?? 1;

        $existingItem = \DB::table('cart_items')
            ->where('session_id', $sessionId)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;
            if ($product->stock < $newQuantity) {
                return response()->json(['message' => 'Insufficient stock'], 422);
            }

            \DB::table('cart_items')
                ->where('id', $existingItem->id)
                ->update(['quantity' => $newQuantity]);
        } else {
            \DB::table('cart_items')->insert([
                'session_id' => $sessionId,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'items' => $this->getCart(),
            'coupon' => $this->getCoupon(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $item = \DB::table('cart_items')
            ->where('id', $id)
            ->where('session_id', $this->getSessionId())
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        if ($request->quantity === 0) {
            \DB::table('cart_items')->where('id', $id)->delete();
        } else {
            $product = Product::find($item->product_id);
            if ($product && $product->stock < $request->quantity) {
                return response()->json(['message' => 'Insufficient stock'], 422);
            }

            \DB::table('cart_items')
                ->where('id', $id)
                ->update(['quantity' => $request->quantity]);
        }

        return response()->json([
            'items' => $this->getCart(),
            'coupon' => $this->getCoupon(),
        ]);
    }

    public function remove($id)
    {
        \DB::table('cart_items')
            ->where('id', $id)
            ->where('session_id', $this->getSessionId())
            ->delete();

        return response()->json([
            'items' => $this->getCart(),
            'coupon' => $this->getCoupon(),
        ]);
    }

    public function clear()
    {
        \DB::table('cart_items')
            ->where('session_id', $this->getSessionId())
            ->delete();

        Session::forget('cart_coupon_id');

        return response()->json([
            'items' => [],
            'coupon' => null,
        ]);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $coupon = Coupon::where('code', strtoupper($request->code))->first();

        if (! $coupon || ! $coupon->isValid()) {
            return response()->json(['message' => 'Invalid or expired coupon'], 422);
        }

        Session::put('cart_coupon_id', $coupon->id);

        return response()->json([
            'coupon' => $coupon,
            'items' => $this->getCart(),
        ]);
    }

    public function removeCoupon()
    {
        Session::forget('cart_coupon_id');

        return response()->json([
            'items' => $this->getCart(),
            'coupon' => null,
        ]);
    }
}
