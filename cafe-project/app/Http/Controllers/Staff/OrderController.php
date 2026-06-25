<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
// Import thêm các Event bạn sẽ dùng sau này
// use App\Events\OrderAccepted;
// use App\Events\OrderCompleted;

class OrderController extends Controller
{
    public function index()
    {
        // Lấy cả đơn chờ tiếp nhận và đơn đang xử lý
        $activeOrders = Order::with(['table', 'orderDetails.product', 'orderDetails.variant'])
            ->whereIn('status', ['PENDING', 'PROCESSING']) 
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Staff/Orders', [
            'initialOrders' => $activeOrders,     
        ]);
    }

    public function accept(Order $order)
    {
        // Chỉ cho phép tiếp nhận đơn đang PENDING
        if ($order->status !== 'PENDING') {
            return redirect()->back()->with('error', 'Đơn hàng này đã được xử lý!');
        }

        $order->update(['status' => 'PROCESSING']);
        
        // Mở khóa dòng này sau này: Bắn tín hiệu sang màn hình Barista để họ bắt đầu pha chế
        // và báo về điện thoại khách hàng "Đơn của bạn đang được chuẩn bị"
        // broadcast(new OrderAccepted($order))->toOthers();
        
        return redirect()->back();
    }

    // Hàm xử lí hoàn thành đơn
    public function complete(Order $order)
    {
        $order->update(['status' => 'COMPLETED']);

        // Mở khóa dòng này sau này: Báo về điện thoại khách hàng "Chúc bạn ngon miệng"
        // broadcast(new OrderCompleted($order))->toOthers();
        
        return redirect()->back();
    }
}