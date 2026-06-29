<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $activeOrders = Order::with(['table', 'details.product'])
            ->whereIn('status', ['PENDING', 'PROCESSING']) 
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Lấy danh sách các bàn và các đơn hàng liên quan, chỉ lấy những đơn hàng có trạng thái "PENDING" trong bảng payments
        $tables = Table::with(['orders' => function($query) {
            // Thay vì kiểm tra trực tiếp, ta dùng whereHas để kiểm tra bảng payments
            $query->whereHas('payment', function($q) {
                    $q->where('payment_status', 'PENDING');
                })
                ->with(['details.product', 'details.variant', 'payment']) // Load thêm bảng payment
                ->latest(); 
        }])->orderBy('id', 'asc')->get();

        return Inertia::render('Staff/Dashboard', [
            'initialOrders' => $activeOrders,
            'initialTables' => $tables 
        ]);
    }
}