<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
class OrderController extends Controller
{
    public function index()
    {
        // Lấy cả đơn chờ tiếp nhận và đơn đang xử lý
        $activeOrders = Order::with(['table', 'orderDetails.product'])
            ->whereIn('status', ['PENDING', 'PROCESSING']) 
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Staff/Orders', [
            'initialOrders' => $activeOrders,     
        ]);
    }

    public function accept(Order $order)
    {
        $order->update(['status' => 'PROCESSING']);
        // broadcast(new OrderAccepted($order))->toOthers();
        return redirect()->back();
    }

    // Hàm xử lí hoàn thành đơn
    public function complete(Order $order)
    {
        $order->update(['status' => 'COMPLETED']);
        return redirect()->back();
    }
}