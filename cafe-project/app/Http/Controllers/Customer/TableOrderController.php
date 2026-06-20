<?php

namespace App\Http\Controllers\Customer;

use App\Events\OrderCreated;
use App\Events\OrderPaymentConfirmed;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TableOrderController extends Controller
{
    public function show($qr_code)
    {
        $table = Table::where('qr_code', $qr_code)->firstOrFail();

        $products = Product::with(['category', 'variants' => function ($q) {
            $q->where('status', 'AVAILABLE');
        }])
        ->where('is_active', 'Đang bán')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'image_url' => $product->image_url,
                'category' => [
                    'id' => $product->category->id ?? null,
                    'name' => $product->category->category_name ?? null,
                ],
                'variants' => $product->variants->map(fn($v) => [
                    'id' => $v->id,
                    'size' => $v->size,
                    'price' => (float) $v->price,
                    'discount_price' => $v->discount_price ? (float) $v->discount_price : null,
                ]),
            ];
        });

        $categories = $products->pluck('category.name', 'category.id')->unique()->filter()->values();

        return inertia('TableOrder/Index', [
            'table' => [
                'id' => $table->id,
                'table_name' => $table->table_name,
                'qr_code' => $table->qr_code,
                'area' => $table->area,
                'capacity' => $table->capacity,
            ],
            'products' => $products,
            'categories' => $categories->map(fn($name, $i) => ['id' => $i + 1, 'name' => $name])->values(),
            'bankInfo' => [
                'account_number' => config('services.bank.account_number'),
                'short_name' => config('services.bank.short_name'),
                'account_name' => config('services.bank.account_name'),
            ],
        ]);
    }

    public function store(Request $request, $qr_code)
    {
        $table = Table::where('qr_code', $qr_code)->firstOrFail();

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER'],
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $orderDetails = [];

            foreach ($request->items as $item) {
                $variant = \App\Models\ProductVariant::findOrFail($item['variant_id']);
                $price = $variant->discount_price ?? $variant->price;
                $subtotal = $price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderDetails[] = new OrderDetail([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'note' => $item['note'] ?? null,
                ]);
            }

            $isOnline = $request->payment_method === 'BANK_TRANSFER';

            $order = Order::create([
                'user_id' => Auth::id(),
                'table_id' => $table->id,
                'total_amount' => $totalAmount,
                'discount_amount' => 0,
                'final_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'payment_status' => 'PENDING',
                'order_type' => 'DINE_IN',
                'status' => 'PENDING',
            ]);

            $order->orderDetails()->saveMany($orderDetails);

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'amount' => $totalAmount,
                'payment_status' => 'PENDING',
            ]);

            $table->update(['status' => 'OCCUPIED']);

            DB::commit();

            $order->load('payment');
            broadcast(new OrderCreated($order))->toOthers();

            $orderCode = 'DH' . str_pad($order->id, 8, '0', STR_PAD_LEFT);

            if ($isOnline) {
                $qrUrl = $this->generateQrUrl($order->final_amount, $orderCode);

                return response()->json([
                    'success' => true,
                    'message' => 'Đơn hàng đã được tạo',
                    'redirect' => route('table.order.payment', $order->id),
                    'order' => [
                        'id' => $order->id,
                        'order_code' => $orderCode,
                        'final_amount' => $order->final_amount,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt món thành công! Nhân viên sẽ phục vụ bạn ngay.',
                'redirect' => route('table.order.success', $order->id),
                'order' => [
                    'id' => $order->id,
                    'order_code' => $orderCode,
                    'final_amount' => $order->final_amount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function success(Order $order)
    {
        $orderCode = 'DH' . str_pad($order->id, 8, '0', STR_PAD_LEFT);

        return inertia('TableOrder/Success', [
            'order' => [
                'id' => $order->id,
                'order_code' => $orderCode,
                'final_amount' => $order->final_amount,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'table_name' => $order->table->table_name ?? null,
                'created_at' => $order->created_at,
            ],
        ]);
    }


    public function payment(Order $order)
    {
        $orderCode = 'DH' . str_pad($order->id, 8, '0', STR_PAD_LEFT);
        $qrUrl = $this->generateQrUrl($order->final_amount, $orderCode);

        return inertia('TableOrder/Payment', [
            'order' => [
                'id' => $order->id,
                'order_code' => $orderCode,
                'final_amount' => $order->final_amount,
                'status' => $order->status,
                'table_name' => $order->table->table_name ?? null,
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
        if ($order->payment) {
            $order->payment->update(['payment_status' => 'PAID']);
        }

        broadcast(new OrderPaymentConfirmed($order))->toOthers();

        return redirect()->route('table.order.success', $order->id)->with('toast-success', 'Xác nhận thanh toán thành công');
    }

    private function generateQrUrl(float $amount, string $orderCode): string
    {
        $accountNumber = config('services.bank.account_number');
        $bankName = config('services.bank.short_name');

        return "https://img.vietqr.io/image/{$bankName}-{$accountNumber}-compact2.png?amount={$amount}&addInfo={$orderCode}";
    }
}