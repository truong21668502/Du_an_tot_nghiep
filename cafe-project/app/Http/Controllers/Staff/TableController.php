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
            // Dùng whereHas để lọc các đơn có payment_status = PENDING trong bảng payments
            $query->whereHas('payment', function($q) {
                    $q->where('payment_status', 'PENDING');
                })
                // Nhớ load thêm 'payment' để giao diện Vue có thể truy xuất
                ->with(['details.product', 'details.variant', 'payment'])
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

        $table->update([
            'status' => $request->status
        ]);

        // nạp lại danh sách đơn hàng chưa thanh toán để gửi về frontend
        $table->load(['orders' => function($query) {
            // Dùng whereHas để lọc các đơn có payment_status = PENDING trong bảng payments
            $query->whereHas('payment', function($q) {
                    $q->where('payment_status', 'PENDING');
                })
                ->with(['details.product', 'details.variant', 'payment'])
                ->latest();
        }]);

        broadcast(new TableStatusUpdated($table));

        return redirect()->back();
    }
}