<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\VoucherException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Cart\AddToCartRequest;
use App\Http\Requests\Customer\Cart\ApplyVoucherRequest;
use App\Http\Requests\Customer\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private const COOKIE_NAME = 'cart_token';
    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30;

    private ?Cart $resolvedCart = null;

    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        if ($request->has('code')) {
            $code = strtoupper(trim($request->input('code')));
            $subtotal = $this->calculateSubtotal($cart);

            try {
                $coupon = $this->validateCoupon($code, $subtotal);
                $discountAmount = $this->calculateDiscount($coupon, $subtotal);

                session([
                    'cart_voucher' => [
                        'coupon_id' => $coupon->id,
                        'code' => $coupon->code,
                        'discount' => $discountAmount,
                        'discount_type' => $coupon->discount_type,
                        'discount_value' => (float) $coupon->discount_value,
                    ]
                ]);
            } catch (VoucherException $e) {
                session()->flash('error', $e->getMessage());
            }
        } else {
            // Cập nhật lại giá trị voucher trong session theo tổng tiền hiện tại
            $this->recalculateVoucher($cart);
        }

        $cart->load([
            'items.product.category',
            'items.product.images',
            'items.product.variants',
            'items.variant',
        ]);

        $voucherSession = session('cart_voucher');

        return inertia('Cart', [
            'cart' => ['id' => $cart->id],
            'cartItems' => $this->transformCartItems($cart),
            'voucherDiscount' => $voucherSession['discount'] ?? 0,
            'appliedVoucher' => $voucherSession ? [
                'code' => $voucherSession['code'],
                'discount' => $voucherSession['discount'],
            ] : null,
        ]);
    }

    public function add(AddToCartRequest $request)
    {
        $cart = $this->getOrCreateCart($request);
        $this->addItem($cart, $request->validated());

        // Tính toán lại voucher dựa trên subtotal mới
        $voucherSession = $this->recalculateVoucher($cart);

        $cart->load([
            'items.product.category',
            'items.product.images',
            'items.product.variants',
            'items.variant',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cartItems' => $this->transformCartItems($cart),
            'voucherDiscount' => $voucherSession['discount'] ?? 0,
            'appliedVoucher' => $voucherSession ? [
                'code' => $voucherSession['code'],
                'discount' => $voucherSession['discount'],
            ] : null,
        ]);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $cart = $this->getOrCreateCart($request);
        abort_if($cartItem->cart_id !== $cart->id, 403);

        $cartItem->update($request->validated());

        // Tính toán lại voucher dựa trên subtotal mới
        $voucherSession = $this->recalculateVoucher($cart);

        $cart->load([
            'items.product.category',
            'items.product.images',
            'items.product.variants',
            'items.variant',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật giỏ hàng',
            'cartItems' => $this->transformCartItems($cart),
            'voucherDiscount' => $voucherSession['discount'] ?? 0,
            'appliedVoucher' => $voucherSession ? [
                'code' => $voucherSession['code'],
                'discount' => $voucherSession['discount'],
            ] : null,
        ]);
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        $cart = $this->getOrCreateCart($request);
        abort_if($cartItem->cart_id !== $cart->id, 403);
        $cartItem->delete();

        // Tính toán lại voucher dựa trên subtotal mới
        $voucherSession = $this->recalculateVoucher($cart);

        $cart->load([
            'items.product.category',
            'items.product.images',
            'items.product.variants',
            'items.variant',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
            'cartItems' => $this->transformCartItems($cart),
            'voucherDiscount' => $voucherSession['discount'] ?? 0,
            'appliedVoucher' => $voucherSession ? [
                'code' => $voucherSession['code'],
                'discount' => $voucherSession['discount'],
            ] : null,
        ]);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();
        session()->forget('cart_voucher');

        return back()->with('success', 'Đã xóa toàn bộ giỏ hàng');
    }

    public function applyVoucher(ApplyVoucherRequest $request)
    {
        $cart = $this->getOrCreateCart($request);
        $subtotal = $this->calculateSubtotal($cart);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để sử dụng mã giảm giá'
            ], 401);
        }   

        try {
            $coupon = $this->validateCoupon($request->validated()['code'], $subtotal);
        } catch (VoucherException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        $discount = $this->calculateDiscount($coupon, $subtotal);

        session()->put('cart_voucher', [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount' => $discount,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã áp dụng mã giảm giá',
            'appliedVoucher' => [
                'code' => $coupon->code,
                'discount' => $discount,
            ],
            'voucherDiscount' => $discount
        ]);
    }

    public function removeVoucher()
    {
        session()->forget('cart_voucher');

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa mã giảm giá',
            'appliedVoucher' => null,
            'voucherDiscount' => 0
        ]);
    }

    /**
     * Tự động tính lại hoặc hủy voucher trong session khi tổng tiền giỏ hàng thay đổi
     */
    private function recalculateVoucher(Cart $cart): ?array
    {
        $voucherSession = session('cart_voucher');

        if (!$voucherSession) {
            return null;
        }

        $subtotal = $this->calculateSubtotal($cart);

        // Nếu giỏ hàng trống, xóa voucher
        if ($subtotal <= 0) {
            session()->forget('cart_voucher');
            return null;
        }

        try {
            // Kiểm tra lại tính hợp lệ của coupon đối với subtotal mới
            $coupon = $this->validateCoupon($voucherSession['code'], $subtotal);
            $newDiscount = $this->calculateDiscount($coupon, $subtotal);

            $updatedVoucher = [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'discount' => $newDiscount,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
            ];

            session(['cart_voucher' => $updatedVoucher]);

            return $updatedVoucher;
        } catch (VoucherException $e) {
            // Nếu subtotal mới không đạt điều kiện (ví dụ: nhỏ hơn min_order_value), tự động xóa voucher
            session()->forget('cart_voucher');
            return null;
        }
    }

    private function getOrCreateCart(Request $request): Cart
    {
        if ($this->resolvedCart) {
            return $this->resolvedCart;
        }

        if (Auth::check()) {
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            $this->mergeGuestCartIntoUserCart($request, $cart);
            return $this->resolvedCart = $cart;
        }

        $token = $request->cookie(self::COOKIE_NAME);
        if ($token && $cart = Cart::whereNull('user_id')->where('token', $token)->first()) {
            return $this->resolvedCart = $cart;
        }

        $token = (string) Str::uuid();
        $cart = Cart::create(['token' => $token]);
        Cookie::queue(self::COOKIE_NAME, $token, self::COOKIE_LIFETIME_MINUTES);
        return $this->resolvedCart = $cart;
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

        $guestCart->items()->delete();
        $guestCart->delete();
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    private function addItem(Cart $cart, array $data): CartItem
    {
        $item = $cart->items()
            ->where('product_id', $data['product_id'])
            ->where('variant_id', $data['variant_id'])
            ->first();

        if ($item) {
            $item->update([
                'quantity' => $item->quantity + ($data['quantity'] ?? 1),
                'note' => $data['note'] ?? $item->note,
            ]);
            return $item;
        }

        return $cart->items()->create([
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'],
            'quantity' => $data['quantity'] ?? 1,
            'note' => $data['note'] ?? null,
        ]);
    }

    private function calculateSubtotal(Cart $cart): float
    {
        $cart->loadMissing('items.product.variants', 'items.variant');

        return (float) $cart->items->sum(
            fn (CartItem $item) => $this->resolveItemPrice($item) * $item->quantity
        );
    }

    private function resolveItemPrice(CartItem $item): float
    {
        return $item->variant
            ? (float) $item->variant->price
            : (float) ($item->product->variants->min('price') ?? 0);
    }

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

        if (Auth::check()) {
            $alreadyUsed = \App\Models\CouponUser::where('user_id', Auth::id())
                ->where('coupon_id', $coupon->id)
                ->where('is_used', true)
                ->exists();

            if ($alreadyUsed) {
                throw new VoucherException('Bạn đã sử dụng mã giảm giá này rồi');
            }
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

    private function transformCartItems(Cart $cart)
    {
        return $cart->items->map(function (CartItem $item) {
            $product = $item->product;
            $price = $this->resolveItemPrice($item);

            return [
                'id' => $item->id,
                'product_id' => $product->id,
                'variant_id' => $item->variant_id,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'slug' => $product->slug,
                    'image' => $product->image_url ?? ($product->images->first()->image_url ?? null),
                    'price' => $price,
                ],
                'variant' => $item->variant ? [
                    'id' => $item->variant->id,
                    'size' => $item->variant->size ?? null,
                    'price' => (float) $item->variant->price,
                    'available_quantity' => min(10 , $item->variant->getAvailableQuantity()),
                ] : null,
                'quantity' => $item->quantity,
                'note' => $item->note,
                'subtotal' => $price * $item->quantity,
            ];
        });
    }
}