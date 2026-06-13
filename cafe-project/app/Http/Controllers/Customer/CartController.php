<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddToCartRequest;
use App\Http\Requests\Customer\ApplyVoucherRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cart = $this->getOrCreateCart();
        $cart->load([
            'items.product.category',
            'items.product.images',
            'items.product.variants',
            'items.variant'
        ]);

        $cartItems = $cart->items->map(function ($item) {
            $product = $item->product;
            $variant = $item->variant;
            $price = $variant ? (float) $variant->price : (float) ($product->variants->min('price') ?? 0);

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
                'variant' => $variant ? [
                    'id' => $variant->id,
                    'size' => $variant->size ?? null,
                    'price' => (float) $variant->price,
                ] : null,
                'quantity' => $item->quantity,
                'note' => $item->note,
                'subtotal' => $price * $item->quantity,
            ];
        });

        $voucherSession = session('cart_voucher');

        return inertia('Cart', [
            'cart' => [
                'id' => $cart->id,
            ],
            'cartItems' => $cartItems,
            'voucherDiscount' => $voucherSession['discount'] ?? 0,
            'appliedVoucher' => $voucherSession ? [
                'code' => $voucherSession['code'],
                'discount' => $voucherSession['discount'],
            ] : null,
        ]);
    }

    public function add(AddToCartRequest $request)
    {
        $validated = $request->validated();
        $cart = $this->getOrCreateCart();

        $existingItem = $cart->items()
            ->where('product_id', $validated['product_id'])
            ->where('variant_id', $validated['variant_id'] ?? null)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + ($validated['quantity'] ?? 1),
                'note' => $validated['note'] ?? $existingItem->note,
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $validated['product_id'],
                'variant_id' => $validated['variant_id'] ?? null,
                'quantity' => $validated['quantity'] ?? 1,
                'note' => $validated['note'] ?? null,
            ]);
        }

        return back()->with('toast-success', 'Đã thêm sản phẩm vào giỏ hàng');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($cartItem);
        $validated = $request->validated();

        $cartItem->update([
            'quantity' => $validated['quantity'],
            'note' => $validated['note'] ?? $cartItem->note,
        ]);

        return back()->with('success', 'Đã cập nhật giỏ hàng');
    }

    public function remove(CartItem $cartItem)
    {
        $this->authorizeCartItem($cartItem);
        $cartItem->delete();

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
    }

    public function clear()
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete();
        session()->forget('cart_voucher');

        return back()->with('success', 'Đã xóa toàn bộ giỏ hàng');
    }

    public function applyVoucher(ApplyVoucherRequest $request)
    {
        $validated = $request->validated();
        $cart = $this->getOrCreateCart();
        $cart->load('items.product.variants', 'items.variant');

        $subtotal = $cart->items->sum(function ($item) {
            $price = $item->variant
                ? (float) $item->variant->price
                : (float) ($item->product->variants->min('price') ?? 0);
            return $price * $item->quantity;
        });

        $voucherCode = strtoupper(trim($validated['code']));

        $coupon = Coupon::where('code', $voucherCode)
            ->where('status', 'ACTIVE')
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now()->toDateString());
            })
            ->first();

        if (!$coupon) {
            return back()->withErrors(['voucher' => 'Mã giảm giá không tồn tại hoặc đã hết hạn']);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return back()->withErrors(['voucher' => 'Mã giảm giá đã đạt giới hạn sử dụng']);
        }

        if ($coupon->min_order_value && $subtotal < (float) $coupon->min_order_value) {
            return back()->withErrors([
                'voucher' => 'Đơn hàng tối thiểu ' . number_format($coupon->min_order_value) . 'đ để áp dụng mã này',
            ]);
        }

        $discount = 0;

        if (strtoupper($coupon->discount_type) === 'PERCENTAGE') {
            $discount = $subtotal * ((float) $coupon->discount_value / 100);
            if ($coupon->max_discount_amount) {
                $discount = min($discount, (float) $coupon->max_discount_amount);
            }
        } elseif (strtoupper($coupon->discount_type) === 'FIXED') {
            $discount = (float) $coupon->discount_value;
        }

        $discount = min($discount, $subtotal);
        $discount = round($discount, 2);

        session()->put('cart_voucher', [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount' => $discount,
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

    private function getOrCreateCart(): Cart
    {
        return Cart::firstOrCreate([
            'user_id' => Auth::id(),
        ]);
    }

    private function authorizeCartItem(CartItem $cartItem): void
    {
        $cart = $this->getOrCreateCart();
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }
    }
}