<?php

namespace App\Http\Controllers\Customer;

use App\Events\OrderCreated;
use App\Events\OrderPaymentConfirmed;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Cart::where('user_id', Auth::id())->with(['items.product', 'items.variant'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('toast-error', 'Giỏ hàng trống');
        }

        $addresses = UserAddress::where('user_id', Auth::id())->get();

        $voucherSession = session('cart_voucher');
        $voucherDiscount = $voucherSession['discount'] ?? 0;

        $subtotal = $cart->items->sum(fn($item) => ($item->variant->price ?? 0) * $item->quantity);
        $total = max(0, $subtotal - $voucherDiscount);

        return inertia('Checkout/Index', [
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product->product_name,
                    'image' => $item->product->image_url,
                    'size' => $item->variant->size ?? null,
                    'price' => (float) ($item->variant->price ?? 0),
                    'quantity' => $item->quantity,
                    'subtotal' => (float) ($item->variant->price ?? 0) * $item->quantity,
                ]),
            ],
            'addresses' => $addresses->map(fn($a) => [
                'id' => $a->id,
                'receiver_name' => $a->receiver_name,
                'receiver_phone' => $a->receiver_phone,
                'address_detail' => $a->address_detail,
                'ward' => $a->ward,
                'city' => $a->city,
                'is_default' => $a->is_default,
                'full_address' => "{$a->address_detail}, {$a->ward}, {$a->city}",
            ]),
            'subtotal' => $subtotal,
            'voucherDiscount' => $voucherDiscount,
            'total' => $total,
            'paymentMethods' => [
                ['id' => 'CASH', 'label' => 'Thanh toán khi nhận hàng (COD)', 'icon' => 'payments'],
                ['id' => 'BANK_TRANSFER', 'label' => 'Chuyển khoản ngân hàng', 'icon' => 'account_balance'],
                ['id' => 'MOMO', 'label' => 'Ví MoMo', 'icon' => 'wallet'],
                ['id' => 'VNPAY', 'label' => 'VNPay', 'icon' => 'credit_card'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer', 'exists:user_addresses,id'],
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER,MOMO,VNPAY'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = Cart::where('user_id', Auth::id())->with(['items.product', 'items.variant'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return back()->with('toast-error', 'Giỏ hàng trống');
        }

        $voucherSession = session('cart_voucher');
        $voucherDiscount = $voucherSession['discount'] ?? 0;
        $couponId = $voucherSession['coupon_id'] ?? null;

        $subtotal = $cart->items->sum(fn($item) => ($item->variant->price ?? 0) * $item->quantity);
        $total = max(0, $subtotal - $voucherDiscount);

        $paymentMethod = $request->payment_method;
        $isOnline = in_array($paymentMethod, ['BANK_TRANSFER', 'MOMO', 'VNPAY']);

        $address = UserAddress::findOrFail($request->address_id);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => Auth::id(),
                'user_address_id' => $address->id,
                'coupon_id' => $couponId,
                'total_amount' => $subtotal,
                'discount_amount' => $voucherDiscount,
                'final_amount' => $total,
                'payment_method' => $paymentMethod,
                'payment_status' => 'PENDING',
                'order_type' => 'DELIVERY',
                'status' => 'PENDING',
            ]);

            foreach ($cart->items as $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->variant->price ?? 0,
                    'note' => $item->note,
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'amount' => $total,
                'payment_status' => 'PENDING',
            ]);

            $cart->items()->delete();
            session()->forget('cart_voucher');

            DB::commit();

            $order->load('payment');
            broadcast(new OrderCreated($order))->toOthers();

            if ($isOnline) {
                return redirect()->route('checkout.payment', $order->id)->with('toast-success', 'Đơn hàng đã được tạo');
            }

            return redirect()->route('checkout.success', $order->id)->with('toast-success', 'Đặt hàng thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('toast-error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function success(Order $order)
    {
        if ($order->user_id !== Auth::id()) abort(403);

        return inertia('Checkout/Success', [
            'order' => [
                'id' => $order->id,
                'order_code' => 'DH' . str_pad($order->id, 8, '0', STR_PAD_LEFT),
                'final_amount' => $order->final_amount,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ],
        ]);
    }

    public function payment(Order $order)
    {
        if ($order->user_id !== Auth::id()) abort(403);
        if ($order->payment_method === 'CASH') abort(404);

        $qrUrl = $this->generateQrUrl($order);

        return inertia('Checkout/Payment', [
            'order' => [
                'id' => $order->id,
                'order_code' => 'DH' . str_pad($order->id, 8, '0', STR_PAD_LEFT),
                'final_amount' => $order->final_amount,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
            ],
            'qrUrl' => $qrUrl,
            'bankInfo' => [
                'account_number' => config('services.bank.account_number'),
                'short_name' => config('services.bank.short_name'),
                'account_name' => config('services.bank.account_name'),
            ],
        ]);
    }

    public function confirmPayment(Order $order)
    {
        if ($order->user_id !== Auth::id()) abort(403);

        broadcast(new OrderPaymentConfirmed($order))->toOthers();

        return redirect()->route('checkout.confirming', $order->id)->with('toast-success', 'Xác nhận thanh toán thành công');
    }

    public function confirming(Order $order)
    {
        if ($order->user_id !== Auth::id()) abort(403);

        return inertia('Checkout/Confirming', [
            'order' => [
                'id' => $order->id,
                'order_code' => 'DH' . str_pad($order->id, 8, '0', STR_PAD_LEFT),
                'final_amount' => $order->final_amount,
                'status' => $order->status,
            ],
        ]);
    }

    private function generateQrUrl(Order $order): string
    {
        $accountNumber = config('services.bank.account_number');
        $bankName = config('services.bank.short_name');
        $amount = $order->final_amount;
        $orderCode = 'DH' . str_pad($order->id, 8, '0', STR_PAD_LEFT);

        return "https://img.vietqr.io/image/{$bankName}-{$accountNumber}-compact2.png?amount={$amount}&addInfo={$orderCode}";
    }
}