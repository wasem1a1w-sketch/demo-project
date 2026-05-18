<?php

namespace App\Http\Controllers\Admin;

use App\Events\ClientNotificationBroadcast;
use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\UserActivityLog;
use App\Notifications\ReviewApproved;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = ProductReview::with(['user:id,name', 'product:id,name,slug'])
            ->latest()
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($reviews);
        }

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function approve(ProductReview $review)
    {
        $review->update(['is_approved' => true]);
        $review->load('product:id,name', 'user:id,name');

        UserActivityLog::record(auth()->id(), 'review_approved', "Review #{$review->id} approved");

        $review->user->notify(new ReviewApproved($review, 'approved'));
        broadcast(new ClientNotificationBroadcast('review_status_changed', [
            'message' => 'Your review has been approved!',
            'product_name' => $review->product->name,
            'status' => 'approved',
        ], $review->user_id));

        return back();
    }

    public function reject(ProductReview $review)
    {
        $review->update(['is_approved' => false]);
        $review->load('product:id,name', 'user:id,name');

        UserActivityLog::record(auth()->id(), 'review_rejected', "Review #{$review->id} rejected");

        $review->user->notify(new ReviewApproved($review, 'rejected'));
        broadcast(new ClientNotificationBroadcast('review_status_changed', [
            'message' => 'Your review has been rejected.',
            'product_name' => $review->product->name,
            'status' => 'rejected',
        ], $review->user_id));

        return back();
    }

    public function destroy(ProductReview $review)
    {
        $review->load('product:id,name', 'user:id,name');
        $userId = $review->user_id;
        $productName = $review->product->name;

        $review->user->notify(new ReviewApproved($review, 'deleted'));
        broadcast(new ClientNotificationBroadcast('review_status_changed', [
            'message' => 'Your review has been deleted.',
            'product_name' => $productName,
            'status' => 'deleted',
        ], $userId));

        UserActivityLog::record(auth()->id(), 'review_deleted', "Review #{$review->id} deleted");

        $review->delete();

        return back();
    }
}
