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
        // Lấy tất cả bàn và chỉ nạp các đơn hàng đang hoạt động (bao gồm cả hoàn thành)
        $tables = Table::with(['orders' => function($query) {
            $query->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED'])
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

        // Nếu dọn bàn, ta tách các đơn hàng cũ (đã hoàn thành/hủy) ra khỏi bàn để khách mới vào không thấy
        if ($request->status === 'EMPTY') {
            // Kiểm tra xem có đơn hàng nào chưa hoàn thành hoặc chưa thanh toán không
            $hasUnfinishedOrders = $table->orders()->where(function($query) {
                $query->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                      ->orWhere(function($subQuery) {
                          $subQuery->where('status', 'COMPLETED')
                                   ->whereHas('payment', function($paymentQuery) {
                                       $paymentQuery->where('payment_status', '!=', 'PAID');
                                   });
                      });
            })->exists();

            if ($hasUnfinishedOrders) {
                return redirect()->back()->withErrors(['status' => 'Bàn còn đơn chưa hoàn thành hoặc chưa thanh toán, không thể dọn!']);
            }

            $table->orders()->whereIn('status', ['COMPLETED', 'CANCELLED'])->update(['table_id' => null]);
        }

        // Chỉ nạp lại các đơn hàng đang hoạt động cho bàn này
        $table->load(['orders' => function($query) {
            $query->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED'])
                    ->with(['details.product', 'details.variant', 'payment'])
                    ->latest();
        }]);

        broadcast(new TableStatusUpdated($table));

        return redirect()->back();
    }
}