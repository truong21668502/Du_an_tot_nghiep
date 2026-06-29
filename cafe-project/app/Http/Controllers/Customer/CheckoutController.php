<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    private const COOKIE_NAME = 'cart_token';
    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30;

    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.product.variants', 'items.variant');

        // Lấy table từ session (nếu có - tức khách vào từ QR)
        $tableId = session('table_id');
        $tableName = session('table_name');
        $orderType = $tableId ? 'DINE_IN' : null; // Mặc định DINE_IN nếu có bàn

        return inertia('Checkout/Index', [
            'cart' => ['id' => $cart->id],
            'subtotal' => $this->calculateSubtotal($cart),
            'voucher' => session('cart_voucher'),
            'tables' => $tableId ? [] : Table::where('status', 'EMPTY')->get(['id', 'table_name', 'area', 'capacity']),
            'sessionTable' => $tableId ? [
                'id' => $tableId,
                'table_name' => $tableName,
            ] : null,
            'sessionOrderType' => $orderType,
        ]);
    }

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
}