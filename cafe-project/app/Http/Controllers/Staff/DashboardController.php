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
        // Đơn đang hoạt động (Chỉ lấy đơn Đang chờ và Đang làm để pha chế)
        $orders = Order::with(['table', 'details.product', 'details.variant', 'payment'])
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

        $tables = Table::with(['orders' => function ($query) {
            $query->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED'])
            ->with(['details.product', 'details.variant', 'payment']); 
        }])->get();

        // Truyền dữ liệu sang Inertia Vue
        return inertia('Staff/Dashboard', [
            'initialOrders' => $orders,
            'initialTables' => $tables,
        ]);
    }
}