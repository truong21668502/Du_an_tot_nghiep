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
use App\Events\OrderCreated;
use App\Events\TableStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderController extends Controller
{
    private const COOKIE_NAME = 'cart_token';
    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30;

    public function store(PlaceOrderRequest $request)
    {
        $cart = $this->getOrCreateCart($request);

        try {
            $order = $this->placeOrder($cart, $request->validated());
        } catch (CartException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return $request->validated('payment_method') === 'CASH'
            ? redirect()->route('customer.orders.pending', $order)->with('success', 'Đặt hàng thành công, vui lòng thanh toán tiền mặt tại quầy')
            : Inertia::location($this->buildVnpayUrl($order, $request->ip()));
    }

    public function pending(Order $order)
    {
        return inertia('Orders/Pending', [
            'order' => $order->load('details.product', 'details.variant', 'payment', 'table'),
        ]);
    }

    // ─── Order logic ───────────────────────────────────────────

    private function placeOrder(Cart $cart, array $data): Order
    {
        $cart->loadMissing('items.product.variants', 'items.variant');
        $this->assertCartNotEmpty($cart);

        return DB::transaction(function () use ($cart, $data) {
            $activeOrder = Order::with('payment')
                ->where('cart_id', $cart->id)
                ->whereIn('status', ['PENDING', 'PROCESSING'])
                ->lockForUpdate()
                ->first();

            $this->assertCartIsFreeForNewOrder($activeOrder);

            [$subtotal, $discountAmount, $couponId] = $this->calculateAmounts($cart);

            $order = $activeOrder
                ? $this->updateOrder($activeOrder, $data, $subtotal, $discountAmount, $couponId)
                : $this->createOrder($cart, $data, $subtotal, $discountAmount, $couponId);

            $this->syncOrderDetails($order, $cart);
            $this->syncPayment($order, $data['payment_method']);

            if ($data['payment_method'] === 'CASH') {
                $cart->items()->delete();
                session()->forget('cart_voucher');
            }

            if ($order->table_id) {
                $table = Table::find($order->table_id);

                if ($table) {
                    $table->update([
                        'status' => 'OCCUPIED'
                    ]);

                    broadcast(new TableStatusUpdated($table));
                }
            }
            // Bắn event real-time cho staff
            $order->load('table', 'details.product', 'details.variant', 'payment');
            broadcast(new OrderCreated($order));
            return $order;
        });
    }

    private function assertCartNotEmpty(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw new CartException('Giỏ hàng đang trống, không thể đặt hàng');
        }
    }

    private function assertCartIsFreeForNewOrder(?Order $activeOrder): void
    {
        if (!$activeOrder) return;

        $isResumableVnpay = $activeOrder->status === 'PENDING'
            && $activeOrder->payment?->payment_method === 'BANK_TRANSFER';

        if (!$isResumableVnpay) {
            throw new CartException('Giỏ hàng này đang có một đơn hàng chưa hoàn thành, vui lòng xử lý xong đơn đó trước.');
        }
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

    private function createOrder(Cart $cart, array $data, float $subtotal, float $discountAmount, ?int $couponId): Order
    {
        return Order::create([
            'cart_id' => $cart->id,
            'user_id' => $cart->user_id,
            'table_id' => $data['table_id'] ?? null,
            'coupon_id' => $couponId,
            'total_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'final_amount' => $subtotal - $discountAmount,
            'order_type' => $data['order_type'],
            'status' => 'PENDING',
        ]);
    }

    private function updateOrder(Order $order, array $data, float $subtotal, float $discountAmount, ?int $couponId): Order
    {
        $order->update([
            'table_id' => $data['table_id'] ?? null,
            'coupon_id' => $couponId,
            'total_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'final_amount' => $subtotal - $discountAmount,
            'order_type' => $data['order_type'],
        ]);

        return $order;
    }

    private function syncOrderDetails(Order $order, Cart $cart): void
    {
        $order->details()->delete();

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
        return Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method' => $paymentMethod,
                'amount' => $order->final_amount,
                'payment_status' => 'PENDING',
                'transaction_id' => null,
                'payment_time' => null,
            ]
        );
    }

    // ─── Cart logic ────────────────────────────────────────────

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
        if (!$token) return;

        $guestCart = Cart::whereNull('user_id')->where('token', $token)->first();
        if (!$guestCart || $guestCart->id === $userCart->id) return;

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

        if ($guestCart->orders()->exists()) {
            $guestCart->items()->delete();
        } else {
            $guestCart->delete();
        }

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

    // ─── Coupon logic ──────────────────────────────────────────

    private function validateCoupon(string $code, float $subtotal): Coupon
    {
        $coupon = Coupon::where('code', strtoupper(trim($code)))
            ->where('status', 'ACTIVE')
            ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()))
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

    // ─── VNPay logic ───────────────────────────────────────────

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