<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Product $product)
    {
        $reviews = ProductReview::where('product_id', $product->id)
            ->where(function ($q) {
                $q->where('is_approved', true);
                if (auth()->check()) {
                    $q->orWhere('user_id', auth()->id());
                }
            })
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string',
        ]);

        $exists = ProductReview::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        $validated['product_id'] = $product->id;
        $validated['user_id'] = $request->user()->id;

        $review = ProductReview::create($validated);

        $review->load('user:id,name');

        UserActivityLog::record($request->user()->id, 'review_created', "Review submitted for product: {$product->name}");

        AdminNotification::notify('review_submitted', [
            'review_id' => $review->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'rating' => $review->rating,
            'title' => $review->title,
            'message' => "New review from {$request->user()->name} on {$product->name}",
        ]);

        return response()->json($review, 201);
    }

    public function update(Request $request, Product $product, ProductReview $review)
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string',
        ]);

        $validated['is_approved'] = false;
        $review->update($validated);
        $review->load('user:id,name');

        return response()->json($review);
    }

    public function destroy(Request $request, Product $product, ProductReview $review)
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review->delete();

        return response()->json(null, 204);
    }
}
