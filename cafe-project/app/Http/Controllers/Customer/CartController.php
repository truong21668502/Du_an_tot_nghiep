<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\VoucherException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddToCartRequest;
use App\Http\Requests\Customer\ApplyVoucherRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
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

        return back()->with('toast-success', 'Đã thêm sản phẩm vào giỏ hàng');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $cart = $this->getOrCreateCart($request);
        $this->authorizeCartItem($cartItem, $cart);

        $cartItem->update([
            'quantity' => $request->validated('quantity'),
            'note' => $request->validated('note') ?? $cartItem->note,
        ]);

        return back()->with('success', 'Đã cập nhật giỏ hàng');
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        $cart = $this->getOrCreateCart($request);
        $this->authorizeCartItem($cartItem, $cart);
        $cartItem->delete();

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
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

        try {
            $coupon = $this->validateCoupon($request->validated('code'), $subtotal);
        } catch (VoucherException $e) {
            return back()->withErrors(['voucher' => $e->getMessage()]);
        }

        session()->put('cart_voucher', [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount' => $this->calculateDiscount($coupon, $subtotal),
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
        ]);

        return redirect()->route('customer.cart.index')->with('success', 'Đã áp dụng mã giảm giá');
    }

    public function removeVoucher()
    {
        session()->forget('cart_voucher');

        return redirect()->route('customer.cart.index')->with('success', 'Đã xóa mã giảm giá');
    }

    // ─── Cart logic ────────────────────────────────────────────

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

        if ($guestCart->orders()->exists()) {
            $guestCart->items()->delete();
        } else {
            $guestCart->delete();
        }

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

    // ─── Helpers ───────────────────────────────────────────────

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
                ] : null,
                'quantity' => $item->quantity,
                'note' => $item->note,
                'subtotal' => $price * $item->quantity,
            ];
        });
    }

    private function authorizeCartItem(CartItem $cartItem, Cart $cart): void
    {
        abort_if($cartItem->cart_id !== $cart->id, 403);
    }
}