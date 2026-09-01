<?php

namespace App\Services\Chat;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ChatToolService
{
    // ─── Tool definitions (OpenAI format) ──────────────────────────────────

    public function getDefinitions(bool $isAuthenticated): array
    {
        $tools = [
            $this->def('search_products',
                'Tìm kiếm sản phẩm theo tên hoặc danh mục. Dùng khi khách hỏi về sản phẩm.',
                [
                    'query'         => ['type' => 'string', 'description' => 'Từ khóa tìm kiếm (tên sản phẩm)'],
                    'category_slug' => ['type' => 'string', 'description' => 'Slug danh mục để lọc'],
                    'limit'         => ['type' => 'integer', 'description' => 'Số kết quả tối đa (mặc định 5)'],
                ]
            ),
            $this->def('get_product_detail',
                'Lấy chi tiết đầy đủ một sản phẩm theo slug.',
                ['slug' => ['type' => 'string', 'description' => 'Slug sản phẩm']],
                ['slug']
            ),
            $this->def('get_menu_categories',
                'Lấy danh sách tất cả danh mục sản phẩm trong menu.',
                []
            ),
            // $this->def('get_active_coupons',
            //     'Lấy danh sách mã giảm giá đang hoạt động.',
            //     []
            // ),
        ];

        // Các tool nhạy cảm — chỉ expose khi đã đăng nhập
        if ($isAuthenticated) {
            $tools[] = $this->def('get_my_orders',
                'Xem lịch sử đơn hàng của tôi.',
                [
                    'limit'  => ['type' => 'integer', 'description' => 'Số đơn gần nhất (mặc định 5)'],
                    'status' => ['type' => 'string', 'description' => 'Lọc: PENDING | PROCESSING | COMPLETED | CANCELLED'],
                ]
            );
            $tools[] = $this->def('get_order_detail',
                'Xem chi tiết một đơn hàng cụ thể.',
                ['order_id' => ['type' => 'integer', 'description' => 'Mã đơn hàng']],
                ['order_id']
            );
            $tools[] = $this->def('get_my_profile',
                'Xem thông tin tài khoản cá nhân.',
                []
            );
        }

        return $tools;
    }

    private function def(string $name, string $description, array $props = [], array $required = []): array
    {
        $properties = [];

        foreach ($props as $key => $prop) {
            $properties[$key] = [
                'type' => $prop['type'],
                'description' => $prop['description'],
            ];
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => (object) $properties,
                    'required' => $required,
                ],
            ],
        ];
    }

    // ─── Tool execution ─────────────────────────────────────────────────────

    public function execute(string $toolName, array $args, ?int $userId): array
    {
        try {
            return match ($toolName) {
                'search_products'   => $this->searchProducts($args),
                'get_product_detail' => $this->getProductDetail($args),
                'get_menu_categories' => $this->getMenuCategories(),
                'get_active_coupons' => $this->getActiveCoupons(),

                // Auth-required — trả về unauthorized nếu gọi mà không có userId
                // (double-check, AI chỉ được nhận tool này khi isAuthenticated = true)
                'get_my_orders'    => $userId ? $this->getMyOrders($args, $userId)    : $this->unauthorized(),
                'get_order_detail' => $userId ? $this->getOrderDetail($args, $userId)  : $this->unauthorized(),
                'get_my_profile'   => $userId ? $this->getMyProfile($userId)           : $this->unauthorized(),

                default => ['error' => "Tool không tồn tại: {$toolName}"],
            };
        } catch (\Throwable $e) {
            Log::error("ChatTool [{$toolName}] failed", [
                'args'  => $args,
                'error' => $e->getMessage(),
            ]);

            return ['error' => 'Lỗi khi lấy dữ liệu, vui lòng thử lại.'];
        }
    }

    // ─── Tool implementations ────────────────────────────────────────────────

    private function searchProducts(array $args): array
    {
        $now = now(); // Lấy thời gian hiện tại để so sánh sale

        // Hàm tạo câu lệnh SQL CASE WHEN để tính giá thực tế của Variant sau khi trừ giảm giá
        $getActualPriceSql = "CASE 
            WHEN discount_price IS NOT NULL 
                AND discount_price > 0 
                AND (sale_date_start IS NULL OR sale_date_start <= '{$now}') 
                AND (sale_date_end IS NULL OR sale_date_end >= '{$now}') 
            THEN discount_price 
            ELSE price 
        END";

        $query = Product::query()
            ->where('is_active', true)
            ->with(['category', 'variants' => function($q) use ($getActualPriceSql) {
                // Eager load các trường cần thiết và tính luôn giá thực tế của từng variant lẻ
                $q->select('*')->selectRaw("({$getActualPriceSql}) as actual_price");
            }]);


        if (!empty($args['query'])) {
            $query->where('product_name', 'LIKE', '%' . trim($args['query']) . '%');
        }

        if (!empty($args['category_slug'])) {
            $query->whereRelation('category', 'slug', $args['category_slug']);
        }

        $limit    = min((int) ($args['limit'] ?? 5), 10);
        $products = $query->limit($limit)->get();

        return [
            'found'    => $products->count(),
            'products' => $products->map(fn ($p) => [
                'id'                => $p->id,
                'name'              => $p->product_name,
                'slug'              => $p->slug,
                'category'          => $p->category?->category_name,
                'image_url'         => $p->image_url,
                'short_description' => $p->short_description,
                // Trả về chi tiết từng size kèm giá gốc + giá giảm để AI biết đường tư vấn
                'variants'          => $p->variants->map(fn ($v) => [
                    'size'           => $v->size,
                    'origin_price'   => (float) $v->price,
                    'current_price'  => (float) $v->actual_price, // Giá thực tế tại thời điểm chat
                    'is_on_sale'     => (float) $v->actual_price < (float) $v->price,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }



    private function getProductDetail(array $args): array
    {
        $product = Product::where('slug', $args['slug'])
            ->where('is_active', true)
            ->with(['variants', 'category', 'brand', 'images'])
            ->first();

        if (!$product) {
            return ['error' => 'Không tìm thấy sản phẩm'];
        }

        return [
            'id'          => $product->id,
            'name'        => $product->product_name,
            'slug'        => $product->slug,
            'category'    => $product->category?->category_name,
            'brand'       => $product->brand?->brand_name,
            'description' => $product->short_description,
            'variants'    => $product->variants->map(fn ($v) => [
                'id'             => $v->id,
                'size'           => $v->size,
                'price'          => (float) $v->price,
                'discount_price' => $v->discount_price ? (float) $v->discount_price : null,
                'on_sale'        => $v->discount_price &&
                    $v->sale_date_start <= now() &&
                    $v->sale_date_end >= now(),
                'status'         => $v->status,
            ])->values()->all(),
        ];
    }

    private function getMenuCategories(): array
    {
        $categories = Category::withCount([
            'products as active_count' => fn ($q) => $q->where('is_active', true),
        ])->get();

        return [
            'categories' => $categories->map(fn ($c) => [
                'id'            => $c->id,
                'name'          => $c->category_name,
                'slug'          => $c->slug,
                'description'   => $c->description,
                'product_count' => $c->active_count,
            ])->values()->all(),
        ];
    }

    private function getActiveCoupons(): array
    {
        $coupons = Coupon::where('status', 'ACTIVE')
            ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->get();

        return [
            'coupons' => $coupons->map(fn ($c) => [
                'code'         => $c->code,
                'type'         => $c->discount_type,  // FIXED | PERCENTAGE
                'value'        => (float) $c->discount_value,
                'min_order'    => (float) $c->min_order_value,
                'max_discount' => $c->max_discount_amount ? (float) $c->max_discount_amount : null,
                'expires'      => $c->expiration_date?->format('d/m/Y H:i'),
            ])->values()->all(),
        ];
    }

    private function getMyOrders(array $args, int $userId): array
    {
        $query = Order::where('user_id', $userId)->with(['payment', 'details']);

        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        $limit  = min((int) ($args['limit'] ?? 5), 10);
        $orders = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        return [
            'total'  => $orders->count(),
            'orders' => $orders->map(fn ($o) => [
                'id'             => $o->id,
                'status'         => $o->status,
                'order_type'     => $o->order_type,
                'final_amount'   => (float) $o->final_amount,
                'payment_status' => $o->payment?->payment_status,
                'payment_method' => $o->payment?->payment_method,
                'items_count'    => $o->details->count(),
                'created_at'     => $o->created_at->format('d/m/Y H:i'),
            ])->values()->all(),
        ];
    }

    private function getOrderDetail(array $args, int $userId): array
    {
        // Quan trọng: where user_id để đảm bảo khách chỉ xem đơn của chính mình
        $order = Order::where('id', $args['order_id'])
            ->where('user_id', $userId)
            ->with(['details.product', 'details.variant', 'payment', 'table'])
            ->first();

        if (!$order) {
            return ['error' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem.'];
        }

        return [
            'id'              => $order->id,
            'status'          => $order->status,
            'order_type'      => $order->order_type,
            'table'           => $order->table?->table_name,
            'total_amount'    => (float) $order->total_amount,
            'discount_amount' => (float) $order->discount_amount,
            'final_amount'    => (float) $order->final_amount,
            'payment'         => $order->payment ? [
                'method'  => $order->payment->payment_method,
                'status'  => $order->payment->payment_status,
                'time'    => $order->payment->payment_time?->format('d/m/Y H:i'),
            ] : null,
            'items'           => $order->details->map(fn ($d) => [
                'product'        => $d->product->product_name,
                'size'           => $d->variant?->size,
                'quantity'       => $d->quantity,
                'unit_price'     => (float) $d->unit_price,
                'subtotal'       => (float) ($d->unit_price * $d->quantity),
                'note'           => $d->note,
                'barista_status' => $d->barista_status,
            ])->values()->all(),
            'created_at' => $order->created_at->format('d/m/Y H:i'),
        ];
    }

    private function getMyProfile(int $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            return ['error' => 'Không tìm thấy người dùng'];
        }

        return [
            'name'          => $user->full_name,
            'email'         => $user->email,
            'phone'         => $user->phone_number,
            'gender'        => $user->gender,
            'date_of_birth' => $user->date_of_birth?->format('d/m/Y'),
            'reward_points' => $user->reward_points,
            'member_since'  => $user->created_at->format('d/m/Y'),
        ];
    }

    private function unauthorized(): array
    {
        return [
            'error'         => 'Bạn cần đăng nhập để xem thông tin này.',
            'require_login' => true,
        ];
    }
}