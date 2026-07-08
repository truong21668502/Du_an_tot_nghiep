<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MenuFilterRequest;
use App\Models\Category;
use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(MenuFilterRequest $request)
    {
        $filters = $request->validated();

        $categories = Category::whereHas('products', function ($q) {
            $q->where('is_active', 'Đang bán');
        })->get(['id', 'category_name', 'slug']);

        $productsQuery = Product::with(['category', 'brand', 'images', 'variants'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('is_active', 'Đang bán');

        if (!empty($filters['category'])) {
            $productsQuery->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $productsQuery->where(function ($q) use ($s) {
                $q->where('product_name', 'like', "%{$s}%")
                    ->orWhere('short_description', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $min = $filters['min_price'] ?? 0;
            $max = $filters['max_price'] ?? 999999999;

            $productsQuery->whereHas('variants', function ($q) use ($min, $max) {
                $q->where('price', '>=', $min)
                    ->where('price', '<=', $max);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'newest';

        if (in_array($sortBy, ['price_asc', 'price_desc'])) {
            $dir = $sortBy === 'price_asc' ? 'asc' : 'desc';
            $productsQuery->addSelect([
                'min_price' => \App\Models\ProductVariant::select('price')
                    ->whereColumn('product_id', 'products.id')
                    ->orderBy('price', 'asc')
                    ->limit(1)
            ])->orderBy('min_price', $dir);
        } else {
            $field = match ($sortBy) {
                'name_asc', 'name_desc' => 'product_name',
                'oldest' => 'created_at',
                default => 'created_at',
            };
            $dir = in_array($sortBy, ['name_asc', 'oldest']) ? 'asc' : 'desc';
            $productsQuery->orderBy($field, $dir);
        }

        $products = $productsQuery->paginate(12)->withQueryString();

        $favoritedProductIds = [];
        if (Auth::check()) {
            $favoritedProductIds = FavoriteProduct::where('user_id', Auth::id())
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->toArray();
        }

        $products->getCollection()->transform(function ($product) use ($favoritedProductIds) {
            $variants = $product->variants->map(function ($v) {
                $isDiscountValid = $v->discount_price &&
                    (!$v->sale_date_start || $v->sale_date_start <= now()) &&
                    (!$v->sale_date_end || $v->sale_date_end >= now());

                return [
                    'id'             => $v->id,
                    'size'           => $v->size ?? null,
                    'price'          => (float) $v->price,
                    'discount_price' => $isDiscountValid ? (float) $v->discount_price : null,
                    'current_price'  => $isDiscountValid ? (float) $v->discount_price : (float) $v->price,
                    'status'         => $v->status,
                ];
            });

            $hasDiscount = $variants->contains(fn($v) => $v['discount_price'] !== null);
            $cheapestVariant = $variants->sortBy('current_price')->first();

            return [
                'id'               => $product->id,
                'product_name'     => $product->product_name,
                'slug'             => $product->slug,
                'short_description' => $product->short_description,
                'image_url'        => $product->image_url ?? ($product->images->first()->image_url ?? null),
                'category'         => [
                    'id'   => $product->category->id ?? null,
                    'name' => $product->category->category_name ?? null,
                    'slug' => $product->category->slug ?? null,
                ],
                'brand' => [
                    'id'   => $product->brand->id ?? null,
                    'name' => $product->brand->brand_name ?? null,
                ],
                'variants'      => $variants->values(),
                'min_price'     => $cheapestVariant['current_price'] ?? 0,
                'max_price'     => $variants->max('current_price') ?? 0,
                'has_discount'  => $hasDiscount,
                'is_favorited'  => in_array($product->id, $favoritedProductIds),
                'avg_rating'    => round($product->reviews_avg_rating ?? 0, 1),
                'total_reviews' => $product->reviews_count ?? 0,
                'created_at'    => $product->created_at,
            ];
        });

        return inertia('Menu', [
            'categories' => collect([['id' => 'all', 'label' => 'Tất cả']])
                ->merge($categories->map(fn($c) => ['id' => $c->slug, 'label' => $c->category_name]))
                ->values(),
            'products' => $products,
            'filters'  => [
                'category'  => $filters['category'] ?? 'all',
                'search'    => $filters['search'] ?? '',
                'min_price' => $filters['min_price'] ?? null,
                'max_price' => $filters['max_price'] ?? null,
                'rating'    => $filters['rating'] ?? null,
                'sort_by'   => $filters['sort_by'] ?? 'newest',
            ],
        ]);
    }

    public function show($slug)
    {
        $product = Product::with([
            'category',
            'brand',
            'images',
            'variants' => function ($query) {
                $query->where('status', 'AVAILABLE');
            },
            'variants.recipes.material',
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('slug', $slug)
            ->where('is_active', 'Đang bán')
            ->firstOrFail();

        $reviews = Review::with('user')
            ->where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        $isFavorited = false;
        if (Auth::check()) {
            $isFavorited = FavoriteProduct::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->exists();
        }

        $productData = [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'slug' => $product->slug,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'image_url' => $product->image_url,
            'images' => $product->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->image_url,
            ]),
            'category' => [
                'id' => $product->category->id ?? null,
                'name' => $product->category->category_name ?? null,
                'slug' => $product->category->slug ?? null,
            ],
            'brand' => [
                'id' => $product->brand->id ?? null,
                'name' => $product->brand->brand_name ?? null,
                'logo' => $product->brand->logo_url ?? null,
            ],
            'variants' => $product->variants->map(function ($variant) {
                $stockQuantity = PHP_INT_MAX;

                if ($variant->recipes->isNotEmpty()) {
                    foreach ($variant->recipes as $recipe) {
                        if ($recipe->material && $recipe->quantity_needed > 0) {
                            $possible = floor($recipe->material->quantity_in_stock / $recipe->quantity_needed);
                            $stockQuantity = min($stockQuantity, $possible);
                        }
                    }
                } else {
                    $stockQuantity = $variant->status === 'AVAILABLE' ? PHP_INT_MAX : 0;
                }

                if ($stockQuantity === PHP_INT_MAX) {
                    $stockQuantity = $variant->status === 'AVAILABLE' ? 99 : 0;
                }

                return [
                    'id' => $variant->id,
                    'size' => $variant->size,
                    'price' => (float) $variant->price,
                    'discount_price' => $variant->discount_price ? (float) $variant->discount_price : null,
                    'quantity' => max(0, (int) $stockQuantity),
                    'status' => $variant->status,
                ];
            }),
            'avg_rating' => round($product->reviews_avg_rating ?? 0, 1),
            'total_reviews' => $product->reviews_count ?? 0,
            'is_favorited' => $isFavorited,
        ];

        $relatedProducts = Product::with(['category', 'images', 'variants'])
            ->withAvg('reviews', 'rating')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', 'Đang bán')
            ->limit(4)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->product_name,
                    'slug' => $p->slug,
                    'price' => (float) ($p->variants->min('price') ?? 0),
                    'category' => $p->category->slug ?? 'all',
                    'badge' => $p->category->category_name ?? null,
                    'badgeVariant' => 'tertiary',
                    'rating' => round($p->reviews_avg_rating ?? 0, 1),
                    'description' => $p->short_description ?? '',
                    'image' => $p->image_url ?? ($p->images->first()->image_url ?? null),
                    'createdAt' => $p->created_at,
                ];
            });

        return inertia('Menu/Show', [
            'product' => $productData,
            'reviews' => $reviews->through(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => [
                        'name' => $review->user->full_name ?? 'Ẩn danh',
                        'avatar' => $review->user->avatar ?? null,
                    ],
                    'created_at' => $review->created_at,
                ];
            }),
            'relatedProducts' => $relatedProducts,
            'isFavorited' => $isFavorited,
        ]);
    }
}