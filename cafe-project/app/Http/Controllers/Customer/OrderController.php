<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\CartException;
use App\Exceptions\VoucherException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PlaceOrderRequest;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Table;
use App\Models\UserAddress;
use App\Events\OrderCreated;
use App\Events\TableStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Services\ShippingService;

class OrderController extends Controller
{
    private const COOKIE_NAME = 'cart_token';
    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30;
    protected $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    public function store(PlaceOrderRequest $request)
    {
        $cart = $this->getOrCreateCart($request);

        try {
            $order = $this->placeOrder($cart, $request->validated(), $request);
        } catch (CartException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return $request->validated('payment_method') === 'CASH'
            ? redirect()->route('customer.orders.pending', $order)->with('success', 'Đặt hàng thành công, vui lòng thanh toán tiền mặt tại quầy')
            : Inertia::location($this->buildVnpayUrl($order, $request->ip()));
    }

    public function pending(Request $request, Order $order)
    {
        // Bảo mật: Kiểm tra quyền xem đơn hàng
        if (!$order->user_id) {
            // Đơn của khách vãng lai → phải khớp cart_token
            $cookieToken = $request->cookie(self::COOKIE_NAME);
            abort_if(!$cookieToken || $order->cart_token !== $cookieToken, 403, 'Bạn không có quyền xem đơn hàng này.');
        } else {
            // Đơn của user đã đăng nhập → phải đúng user_id
            abort_if($order->user_id !== Auth::id(), 403);
        }

        return inertia('Orders/Pending', [
            'order' => $order->load('details.product', 'details.variant', 'payment', 'table'),
        ]);
    }

    // ─── Order Logic ───────────────────────────────────────────

private function placeOrder(Cart $cart, array $data, Request $request): Order
    {
        $cart->loadMissing('items.product.variants', 'items.variant');

        if ($cart->items->isEmpty()) {
            throw new CartException('Giỏ hàng đang trống, không thể đặt hàng');
        }

        return DB::transaction(function () use ($cart, $data, $request) {
            [$subtotal, $discountAmount, $couponId] = $this->calculateAmounts($cart);

            // Tính phí ship dựa trên địa chỉ đã chọn (nếu là đơn giao hàng)
            $shippingData = $this->resolveShippingFee($data);

            $order = $this->createOrder($cart, $data, $subtotal, $discountAmount, $couponId, $shippingData);

            $this->syncOrderDetails($order, $cart);
            $this->syncPayment($order, $data['payment_method']);

            // Tính thời gian dự kiến (AI)
            $newOrderItems = $order->details()->with('product')->get()->map(function ($detail) {
                return [
                    'item_name' => $detail->product->product_name ?? 'N/A',
                    'quantity' => $detail->quantity
                ];
            })->toArray();

            try {
                $geminiService = app(\App\Services\GeminiService::class);
                $aiPredictedTime = $geminiService->estimateOrderPrepTime($newOrderItems);
                
                if ($aiPredictedTime !== null) {
                    $order->estimated_prep_time = $aiPredictedTime;
                } else {
                    $totalNewDrinks = array_sum(array_column($newOrderItems, 'quantity'));
                    $order->estimated_prep_time = $totalNewDrinks * 5;
                }
                $order->save();
            } catch (\Exception $e) {
                $totalNewDrinks = array_sum(array_column($newOrderItems, 'quantity'));
                $order->estimated_prep_time = $totalNewDrinks * 5;
                $order->save();
            }

            if ($couponId && Auth::check()) {
                $couponUser = DB::table('coupon_user')
                    ->where('user_id', Auth::id())
                    ->where('coupon_id', $couponId)
                    ->first();

                if ($couponUser) {
                    DB::table('coupon_user')
                        ->where('id', $couponUser->id)
                        ->update([
                            'is_used' => true,
                            'used_at' => now(),
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('coupon_user')->insert([
                        'user_id' => Auth::id(),
                        'coupon_id' => $couponId,
                        'is_used' => true,
                        'used_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                Coupon::where('id', $couponId)->increment('used_count');
            }

            $cart->items()->delete();
            session()->forget('cart_voucher');
            session()->forget('table_id');

            $order->load('table', 'details.product', 'details.variant', 'payment');
            
            if ($order->table_id) {
                $table = \App\Models\Table::find($order->table_id);
                if ($table) {
                    $table->update(['status' => 'OCCUPIED']);
                    broadcast(new TableStatusUpdated($table));
                }
            }

            if ($data['order_type'] !== 'DELIVERY' || $data['payment_method'] !== 'VNPAY') {
                broadcast(new OrderCreated($order));
            }

            return $order->load('details', 'payment');
        });
    }

    /**
     * Xác định phí ship + địa chỉ giao hàng dựa trên address_id trong request.
     * Ném CartException nếu địa chỉ không hợp lệ hoặc vượt bán kính giao hàng.
     */
    private function resolveShippingFee(array $data): array
    {
        if (($data['order_type'] ?? null) !== 'DELIVERY') {
            return ['fee' => 0.0, 'address' => null];
        }

        if (empty($data['address_id'])) {
            throw new CartException('Vui lòng chọn địa chỉ giao hàng');
        }

        $address = UserAddress::find($data['address_id']);

        if (!$address || !$address->latitude || !$address->longitude) {
            throw new CartException('Địa chỉ giao hàng không hợp lệ, vui lòng chọn lại');
        }

        $distanceMeters = $this->shippingService->getDistanceMeters(
            (float) $address->latitude,
            (float) $address->longitude
        );

        if ($distanceMeters === null) {
            throw new CartException('Không thể tính khoảng cách giao hàng, vui lòng thử lại');
        }

        $fee = $this->shippingService->calculateFee($distanceMeters);

        if ($fee === null || $distanceMeters > ShippingService::MAX_DISTANCE_METERS) {
            throw new CartException('Địa chỉ giao hàng vượt quá bán kính hỗ trợ (5km)');
        }

        return ['fee' => $fee, 'address' => $address];
    }

    private function calculateAmounts(Cart $cart): array
    {
        $subtotal = $this->calculateSubtotal($cart);
        $voucher = session('cart_voucher');
        $discountAmount = 0.0;
        $couponId = null;

        if ($voucher) {
            try {
                $coupon = $this->validateCoupon($voucher['code'], $subtotal);
                $discountAmount = $this->calculateDiscount($coupon, $subtotal);
                $couponId = $coupon->id;
            } catch (VoucherException) {
                session()->forget('cart_voucher');
            }
        }

        return [$subtotal, $discountAmount, $couponId];
    }

    private function createOrder(
        Cart $cart,
        array $data,
        float $subtotal,
        float $discountAmount,
        ?int $couponId,
        array $shippingData
    ): Order {
        $tableId = null;

        if ($data['order_type'] === 'DINE_IN') {
            $tableId = $data['table_id'] ?? session('table_id');
        }

        $shippingFee = $shippingData['fee'] ?? 0.0;

        $orderData = [
            'user_id' => Auth::id(),
            'cart_token' => Auth::check() ? null : $cart->token,
            'table_id' => $tableId,
            'coupon_id' => $couponId,
            'total_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'shipping_fee' => $shippingFee,
            'final_amount' => $subtotal - $discountAmount + $shippingFee,
            'order_type' => $data['order_type'],
            'status' => 'PENDING',
            'source' => 'CUSTOMER',
            'note' => $data['note'] ?? null,
            'distance' => $data['distance'] ?? null,
            'duration' => $data['duration'] ?? null,
        ];

        $address = $shippingData['address'] ?? null;
        if ($data['order_type'] === 'DELIVERY' && $address) {
            $orderData['receiver_name'] = $address->receiver_name;
            $orderData['receiver_phone'] = $address->receiver_phone;
            $orderData['address_detail'] = $address->address_detail;
            $orderData['ward'] = $address->ward;
            $orderData['city'] = $address->city;
            $orderData['latitude'] = $address->latitude;
            $orderData['longitude'] = $address->longitude;
            $orderData['goong_place_id'] = $address->goong_place_id;
        }

        return Order::create($orderData);
    }
    private function syncOrderDetails(Order $order, Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $price = $item->variant
                ? (float) $item->variant->price
                : (float) ($item->product->variants->min('price') ?? 0);

            $order->details()->create([
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'unit_price' => $price,
                'note' => $item->note,
                'barista_status' => 'PENDING',
            ]);
        }
    }

    private function syncPayment(Order $order, string $paymentMethod): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'amount' => $order->final_amount,
            'payment_status' => 'PENDING',
        ]);
    }

    // ─── Cart Logic ────────────────────────────────────────────

    private function getOrCreateCart(Request $request): Cart
    {
        if (Auth::check()) {
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            $this->mergeGuestCartIntoUserCart($request, $cart);
            return $cart;
        }

        $token = $request->cookie(self::COOKIE_NAME);
        if ($token && $cart = Cart::whereNull('user_id')->where('token', $token)->first()) {
            return $cart;
        }

        $token = (string) Str::uuid();
        $cart = Cart::create(['token' => $token]);
        Cookie::queue(self::COOKIE_NAME, $token, self::COOKIE_LIFETIME_MINUTES);
        return $cart;
    }

    private function mergeGuestCartIntoUserCart(Request $request, Cart $userCart): void
    {
        $token = $request->cookie(self::COOKIE_NAME);
        if (!$token)
            return;

        $guestCart = Cart::whereNull('user_id')->where('token', $token)->first();
        if (!$guestCart || $guestCart->id === $userCart->id)
            return;

        foreach ($guestCart->items as $guestItem) {
            $userItem = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->first();

            if ($userItem) {
                $userItem->increment('quantity', $guestItem->quantity);
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->items()->delete();
        $guestCart->delete();
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    private function calculateSubtotal(Cart $cart): float
    {
        $cart->loadMissing('items.product.variants', 'items.variant');

        return (float) $cart->items->sum(function ($item) {
            $price = $item->variant
                ? (float) $item->variant->price
                : (float) ($item->product->variants->min('price') ?? 0);
            return $price * $item->quantity;
        });
    }

    // ─── Coupon Logic ──────────────────────────────────────────

    private function validateCoupon(string $code, float $subtotal): Coupon
    {
        $coupon = Coupon::where('code', strtoupper(trim($code)))
            ->where('status', 'ACTIVE')
            ->where(fn($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()))
            ->first();

        if (!$coupon) {
            throw new VoucherException('Mã giảm giá không tồn tại hoặc đã hết hạn');
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw new VoucherException('Mã giảm giá đã đạt giới hạn sử dụng');
        }

        if ($coupon->min_order_value && $subtotal < (float) $coupon->min_order_value) {
            throw new VoucherException(
                'Đơn hàng tối thiểu ' . number_format($coupon->min_order_value) . 'đ để áp dụng mã này'
            );
        }

        return $coupon;
    }

    private function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = match (strtoupper($coupon->discount_type)) {
            'PERCENTAGE' => $subtotal * ((float) $coupon->discount_value / 100),
            'FIXED' => (float) $coupon->discount_value,
            default => 0.0,
        };

        if (strtoupper($coupon->discount_type) === 'PERCENTAGE' && $coupon->max_discount_amount) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return round(min($discount, $subtotal), 2);
    }

    // ─── VNPay Logic ───────────────────────────────────────────

    private function buildVnpayUrl(Order $order, string $ip): string
    {
        $tmnCode = config('services.vnpay.tmn_code');
        $hashSecret = config('services.vnpay.hash_secret');
        $baseUrl = config('services.vnpay.url');

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) round($order->final_amount * 100),
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $ip,
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang #' . $order->id,
            'vnp_OrderType' => 'billpayment',
            'vnp_ReturnUrl' => route('vnpay.return'),
            'vnp_TxnRef' => $order->id . '_' . now()->timestamp,
        ];

        // Sắp xếp tham số theo alphabet
        ksort($params);

        // Tạo chuỗi query (Bắt buộc dùng PHP_QUERY_RFC3986 để khoảng trắng thành %20 thay vì dấu +)
        $hashData = "";
        $i = 0;
        foreach ($params as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        // Tạo chuỗi query cho URL hiển thị
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        // Băm HMAC-SHA512 bằng chuỗi dữ liệu gốc (KHÔNG dùng urldecode)
        $secureHash = hash_hmac('sha512', $hashData, $hashSecret);

        return $baseUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }
}