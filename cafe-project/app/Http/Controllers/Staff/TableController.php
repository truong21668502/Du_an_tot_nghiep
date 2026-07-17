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