<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MenuFilterRequest;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(MenuFilterRequest $request)
    {
        $filters = $request->validated();

        $categories = Category::whereHas('products', function ($q) {
            $q->where('is_active', true);
        })->get(['id', 'category_name', 'slug']);

        $productsQuery = Product::with(['category', 'brand', 'images', 'variants'])
            ->where('is_active', true);

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

        $products->getCollection()->transform(function ($product) {
            return [
                'id'               => $product->id,
                'product_name'     => $product->product_name,
                'slug'             => $product->slug,
                'short_description'=> $product->short_description,
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
                'variants' => $product->variants->map(fn($v) => [
                    'id'    => $v->id,
                    'size'  => $v->size ?? null,
                    'price' => $v->price ?? 0,
                ]),
                'min_price' => $product->variants->min('price') ?? 0,
                'max_price' => $product->variants->max('price') ?? 0,
                'created_at' => $product->created_at,
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
}