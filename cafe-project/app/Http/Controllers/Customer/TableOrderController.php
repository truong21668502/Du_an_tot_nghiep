<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;

class TableOrderController extends Controller
{
    public function index(Request $request, $qrCode)
    {
        $table = Table::where('qr_code', $qrCode)->firstOrFail();

        session([
            'table_id' => $table->id,
            'table_name' => $table->table_name,
            'table_qr_code' => $qrCode,
        ]);

        $categories = Category::whereHas('products', function ($q) {
            $q->where('is_active', 'Đang bán');
        })->get(['id', 'category_name', 'slug']);

        $productsQuery = Product::with(['category', 'brand', 'images', 'variants'])
            ->where('is_active', 'Đang bán');

        // Lọc theo category
        if ($request->filled('category') && $request->category !== 'all') {
            $productsQuery->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Tìm kiếm
        if ($request->filled('search')) {
            $s = $request->search;
            $productsQuery->where(function ($q) use ($s) {
                $q->where('product_name', 'like', "%{$s}%")
                  ->orWhere('short_description', 'like', "%{$s}%");
            });
        }

        // Lọc giá
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $min = $request->min_price ?? 0;
            $max = $request->max_price ?? 999999999;

            $productsQuery->whereHas('variants', function ($q) use ($min, $max) {
                $q->where('price', '>=', $min)
                  ->where('price', '<=', $max);
            });
        }

        // Sắp xếp
        $sortBy = $request->sort_by ?? 'newest';
        $sortMap = [
            'newest'     => ['created_at', 'desc'],
            'oldest'     => ['created_at', 'asc'],
            'name_asc'   => ['product_name', 'asc'],
            'name_desc'  => ['product_name', 'desc'],
            'price_asc'  => ['min_price', 'asc'],
            'price_desc' => ['min_price', 'desc'],
        ];

        if (in_array($sortBy, ['price_asc', 'price_desc'])) {
            $dir = $sortBy === 'price_asc' ? 'asc' : 'desc';
            $productsQuery->addSelect([
                'min_price' => \App\Models\ProductVariant::select('price')
                    ->whereColumn('product_id', 'products.id')
                    ->orderBy('price', 'asc')
                    ->limit(1)
            ])->orderBy('min_price', $dir);
        } else {
            [$field, $dir] = $sortMap[$sortBy] ?? ['created_at', 'desc'];
            $productsQuery->orderBy($field, $dir);
        }

        $products = $productsQuery->paginate(12)->withQueryString();

        // Transform products
        // Transform products - Format cho MenuItemCard
        $products->getCollection()->transform(function ($product) {
            $minPrice = $product->variants->min('price') ?? 0;
            $hasDiscount = $product->variants->contains(fn($v) => $v->discount_price);
            
            return [
                'id'                => $product->id,
                'name'              => $product->product_name,        // MenuItemCard dùng 'name'
                'product_name'      => $product->product_name,
                'slug'              => $product->slug,
                'description'       => $product->short_description,   // MenuItemCard dùng 'description'
                'short_description' => $product->short_description,
                'image'             => $product->image_url ?? ($product->images->first()->image_url ?? null), // MenuItemCard dùng 'image'
                'image_url'         => $product->image_url ?? ($product->images->first()->image_url ?? null),
                'price'             => (float) $minPrice,             // MenuItemCard dùng 'price'
                'category'          => $product->category->category_name ?? null, // MenuItemCard dùng 'category' string
                'badge'             => $hasDiscount ? 'Giảm giá' : ($product->category->category_name ?? null),
                'badgeVariant'      => $hasDiscount ? 'error' : 'tertiary',
                'rating'            => 4.5,
                'createdAt'         => $product->created_at,
                'category_obj'      => [                               // Giữ lại object nếu cần
                    'id'   => $product->category->id ?? null,
                    'name' => $product->category->category_name ?? null,
                    'slug' => $product->category->slug ?? null,
                ],
                'variants' => $product->variants->map(fn($v) => [
                    'id'             => $v->id,
                    'size'           => $v->size ?? null,
                    'price'          => (float) $v->price,
                    'discount_price' => $v->discount_price ? (float) $v->discount_price : null,
                ]),
                'min_price'  => (float) $minPrice,
                'max_price'  => (float) ($product->variants->max('price') ?? 0),
                'created_at' => $product->created_at,
            ];
        });

        // Format categories cho filter
        $categoryFilters = collect([['id' => 'all', 'label' => 'Tất cả']])
            ->merge($categories->map(fn($c) => [
                'id'    => $c->slug,
                'label' => $c->category_name,
            ]))
            ->values();

        return inertia('TableOrder/Index', [
            'table'      => [
                'id'         => $table->id,
                'table_name' => $table->table_name,
                'area'       => $table->area,
                'capacity'   => $table->capacity,
                'qr_code'    => $table->qr_code,
            ],
            'categories'  => $categoryFilters,
            'products'    => $products,
            'filters'     => [
                'category'  => $request->category ?? 'all',
                'search'    => $request->search ?? '',
                'min_price' => $request->min_price ?? null,
                'max_price' => $request->max_price ?? null,
                'rating'    => $request->rating ?? null,
                'sort_by'   => $request->sort_by ?? 'newest',
            ],
            'cartItems'        => [],
            'voucherDiscount'  => 0,
            'appliedVoucher'   => null,
        ]);
    }
}