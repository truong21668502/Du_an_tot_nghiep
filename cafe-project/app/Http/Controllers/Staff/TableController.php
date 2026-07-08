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
        // Lấy tất cả bàn và chỉ nạp các đơn hàng đang hoạt động
        $tables = Table::with(['orders' => function($query) {
            $query->whereIn('status', ['PENDING', 'PROCESSING'])
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

        // Chỉ nạp lại các đơn hàng đang hoạt động cho bàn này
        $table->load(['orders' => function($query) {
            $query->whereIn('status', ['PENDING', 'PROCESSING'])
                    ->with(['details.product', 'details.variant', 'payment'])
                    ->latest();
        }]);

        broadcast(new TableStatusUpdated($table));

        return redirect()->back();
    }
}