<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Events\OrderCreated;
use App\Events\TableStatusUpdated;
use App\Events\OrderPaymentConfirmed;
use App\Events\OrderCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $activeOrders = Order::with(['table', 'details.product', 'details.variant', 'payment'])
            ->whereIn('status', ['PENDING', 'PROCESSING', 'READY'])
            ->where(function ($q) {
                $q->where('order_type', '!=', 'DELIVERY')
                    ->orWhereHas('payment', function ($pq) {
                        $pq->where('payment_method', 'CASH')
                            ->orWhere('payment_status', 'PAID');
                    });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Staff/Orders', [
            'initialOrders' => $activeOrders,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'required',
            'items' => 'required|array|min:1',
            'items.*.note' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric',
        ]);

        // Tạo đơn hàng
        $order = Order::create([
            'order_type' => $request->order_type,
            'table_id' => $request->order_type === 'DINE_IN' ? $request->table_id : null,
            'status' => 'PENDING',
            'source' => 'STAFF',
            'total_amount' => $request->total_amount,
            'final_amount' => $request->total_amount,
            'discount_amount' => 0,
        ]);

        // Tạo chi tiết món
        foreach ($request->items as $item) {
            $order->details()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'note' => $item['note'],
            ]);
        }

        // Tạo bảng ghi vào payment
        $order->payment()->create([
            'payment_method' => $request->payment_method ?? 'CASH',
            'payment_status' => $request->payment_status ?? 'PENDING',
            'amount' => $request->total_amount,
        ]);

        // Cập nhật lại trạng thái bàn
        if ($order->table_id) {
            $table = Table::find($order->table_id);
            if ($table) {
                $table->update(['status' => 'OCCUPIED']);
                broadcast(new TableStatusUpdated($table));
            }
        }

        // Load thêm quan hệ 'payment' để bắn qua Vue
        broadcast(new OrderCreated($order->load(['table', 'details.product', 'details.variant', 'payment'])));

        return redirect()->back()->with('success', 'Đơn hàng đã được tạo!');
    }

    public function accept(Order $order, Request $request)
    {
        if ($order->status !== 'PENDING') {
            return response()->json(['error' => 'Đơn hàng này đã được xử lý!'], 400);
        }

        $order->update(['status' => 'PROCESSING']);

        // Nếu request đến bằng axios (không phải Inertia full visit), trả JSON
        if ($request->expectsJson() || $request->header('X-Inertia') === null) {
            return response()->json(['status' => 'PROCESSING']);
        }

        return redirect()->back();
    }

    public function complete(Order $order)
    {
        $newStatus = $order->order_type === 'DELIVERY' ? 'READY' : 'COMPLETED';
        $order->update(['status' => $newStatus]);
        return redirect()->back();
    }

    public function confirmPayment(Order $order)
    {
        $order->load('payment');

        if ($order->payment && $order->payment->payment_status === 'PAID') {
            return back()->with('error', 'Đơn hàng này đã được thanh toán!');
        }

        DB::beginTransaction();
        try {
            // Cập nhật trạng thái tiền
            if ($order->payment) {
                $order->payment->update([
                    'payment_status' => 'PAID',
                    'payment_time' => now(),
                ]);
            }

            DB::commit();

            // Bắn sự kiện để Frontend tự update icon thanh toán
            broadcast(new OrderPaymentConfirmed($order));

            return back()->with('success', 'Đã xác nhận thanh toán đơn hàng!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi thanh toán: ' . $e->getMessage());
        }
    }

    public function getVnpayUrl(Order $order, Request $request)
    {
        if ($order->payment && $order->payment->payment_method === 'CASH') {
            return response()->json(['error' => 'Đơn hàng này thanh toán bằng tiền mặt'], 400);
        }
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
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang #' . $order->id,
            'vnp_OrderType' => 'billpayment',
            'vnp_ReturnUrl' => route('vnpay.return'),
            'vnp_TxnRef' => $order->id . '_' . now()->timestamp,
        ];

        ksort($params);

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
        $vnpSecureHash = hash_hmac('sha512', $hashData, $hashSecret);
        $vnpayUrl = $baseUrl . "?" . $hashData . '&vnp_SecureHash=' . $vnpSecureHash;

        return response()->json(['url' => $vnpayUrl]);
    }

    public function cancel(Order $order)
    {
        if ($order->status !== 'PENDING') {
            return redirect()->back()->with('error', 'Chỉ có thể hủy đơn hàng đang chờ xử lý!');
        }

        $order->update(['status' => 'CANCELLED']);

        if ($order->table_id) {
            $remainingOrders = Order::where('table_id', $order->table_id)
                ->whereIn('status', ['PENDING', 'PROCESSING'])
                ->count();
            if ($remainingOrders === 0) {
                $table = Table::find($order->table_id);
                if ($table) {
                    $table->update(['status' => 'EMPTY']);
                    broadcast(new TableStatusUpdated($table));
                }
            }
        }

        broadcast(new OrderCancelled($order));

        return redirect()->back();
    }

    public function createData()
    {
        return response()->json([
            'products' => Product::where('is_active', 'Đang bán')->with('variants')->get(),
            'tables' => Table::orderBy('id', 'asc')->get(),
        ]);
    }
}