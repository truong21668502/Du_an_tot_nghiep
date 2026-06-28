<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $bestSellerIds = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->where('orders.status', 'COMPLETED')
            ->select('order_details.product_id', DB::raw('SUM(order_details.quantity) as total_sold'))
            ->groupBy('order_details.product_id')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->pluck('product_id');

        if ($bestSellerIds->isEmpty()) {
            $bestSellerIds = Product::where('is_active', 'Đang bán')->limit(6)->pluck('id');
        }

        $products = Product::with(['category', 'variants', 'images'])
            ->whereIn('id', $bestSellerIds)
            ->where('is_active', 'Đang bán')
            ->limit(6)
            ->get()
            ->map(function ($product) {
                $minPrice = (float) ($product->variants->min('price') ?? 0);
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
                    'rating' => 4.5,
                    'createdAt' => $product->created_at,
                    'variants' => $product->variants->map(fn($v) => [
                        'id' => $v->id,
                        'size' => $v->size,
                        'price' => (float) $v->price,
                    ]),
                ];
            });

        $articles = Post::with('user')
            ->where('status', 'PUBLISHED')
            ->orderByDesc('created_at')
            ->limit(2)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => \Str::limit(strip_tags($post->content), 150),
                    'thumbnail' => $post->thumbnail_url,
                    'author' => $post->user->full_name ?? 'Nắng Coffee',
                    'created_at' => $post->created_at->format('d/m/Y'),
                ];
            });

        return inertia('Home', [
            'drinks' => $products->values(),
            'articles' => $articles,
        ]);
    }
}