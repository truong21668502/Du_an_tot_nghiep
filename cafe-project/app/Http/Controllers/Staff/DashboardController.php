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
        $activeOrders = Order::with(['table', 'orderDetails.product'])
            ->whereIn('status', ['PENDING', 'PROCESSING']) 
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Lấy toàn bộ danh sách bàn
        $tables = Table::with(['orders' => function($query) {
            // Chỉ lấy đơn hàng chưa hoàn tất thanh toán của bàn đó
            $query->where('payment_status', 'PENDING')
                ->with('orderDetails.product', 'orderDetails.variant')
                ->latest(); // Lấy đơn mới nhất
        }])->orderBy('id', 'asc')->get();

        return Inertia::render('Staff/Dashboard', [
            'initialOrders' => $activeOrders,
            'initialTables' => $tables 
        ]);
    }
}