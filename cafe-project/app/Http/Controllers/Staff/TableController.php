<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Events\TableStatusUpdated;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::with(['orders' => function($query) {
            $query->where('payment_status', 'PENDING')
                ->with(['orderDetails.product', 'orderDetails.variant'])
                ->latest();
        }])->orderBy('id', 'asc')->get(); 

        return Inertia::render('Staff/Tables', [
            'initialTables' => $tables
        ]);
    }
    
    public function updateStatus(Request $request, Table $table)
    {
        $request->validate([
            'status' => 'required|in:EMPTY,OCCUPIED'
        ]);

        // cập nhật trạng thái bàn mới
        $table->update([
            'status' => $request->status
        ]);

        // nạp lại danh sách đơn hàng chưa thanh toán để gửi về frontend
        $table->load(['orders' => function($query) {
            $query->where('payment_status', 'PENDING')
                ->with(['orderDetails.product', 'orderDetails.variant'])
                ->latest();
        }]);

        // bắn sự kiện realtime cập nhật bàn
        broadcast(new TableStatusUpdated($table));

        return redirect()->back();
    }
}