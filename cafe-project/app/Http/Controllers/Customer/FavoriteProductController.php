<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FavoriteProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteProductController extends Controller
{
    public function index()
    {
        $favorites = FavoriteProduct::with([
            'product.category',
            'product.variants',
            'product.images',
            'product.reviews',
        ])
        ->where('user_id', Auth::id())
        ->orderByDesc('created_at')
        ->paginate(12)
        ->through(function ($fav) {
            $product = $fav->product;
            $minPrice = (float) ($product->variants->min('price') ?? 0);
            $avgRating = round($product->reviews->avg('rating') ?? 0, 1);

            return [
                'id' => $product->id,
                'name' => $product->product_name,
                'slug' => $product->slug,
                'description' => $product->short_description ?? '',
                'image' => $product->image_url ?? ($product->images->first()->image_url ?? 'https://placehold.co/400x400'),
                'price' => $minPrice,
                'category' => $product->category->slug ?? 'all',
                'badge' => $product->category->category_name ?? null,
                'badgeVariant' => 'tertiary',
                'rating' => $avgRating,
                'total_reviews' => $product->reviews->count(),
                'createdAt' => $product->created_at,
                'variants' => $product->variants->map(fn($v) => [
                    'id' => $v->id,
                    'size' => $v->size,
                    'price' => (float) $v->price,
                ]),
            ];
        });

        return inertia('Favorites/Index', [
            'favorites' => $favorites,
        ]);
    }

    public function toggle(Product $product)
    {
        $user = Auth::user();

        $exists = FavoriteProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($exists) {
            $exists->delete();

            return response()->json([
                'success' => true,
                'is_favorited' => false,
                'message' => 'Đã xóa khỏi danh sách yêu thích',
            ]);
        }

        FavoriteProduct::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return response()->json([
            'success' => true,
            'is_favorited' => true,
            'message' => 'Đã thêm vào danh sách yêu thích',
        ]);
    }

    public function remove(Product $product)
    {
        FavoriteProduct::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa khỏi danh sách yêu thích',
        ]);
    }
}