<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateReviewRequest;
use App\Http\Requests\Customer\UpdateReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\Order;

class ReviewController extends Controller
{
    // GET /products/{product}/reviews
    public function index(Product $product)
    {
        return $product->reviews()
            ->with('user:id,full_name')
            ->latest()
            ->paginate(10);
    }

    // POST /reviews
    public function store(CreateReviewRequest $request)
    {
        $order = Order::where('user_id', $request->user()->id)
            ->where('status', 'COMPLETED')
            ->whereHas('details', fn($q) => $q->where('product_id', $request->product_id))
            ->whereDoesntHave('reviews', fn($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->first();

        $review = Review::create([
            'product_id' => $request->product_id,
            'user_id'    => $request->user()->id,
            'order_id'   => $order->id,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
        ]);

        return $review->load('user:id,full_name');
    }

    // PATCH /reviews/{review}
    public function update(UpdateReviewRequest $request, Review $review)
    {
        $review->update($request->validated());

        return $review;
    }

    // DELETE /reviews/{review}
    public function destroy(Review $review)
    {
        abort_if($review->user_id !== auth()->id(), 403);

        $review->delete();

        return response()->noContent(); // 204
    }
}