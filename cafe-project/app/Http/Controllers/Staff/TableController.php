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
        // Lấy tất cả bàn và nạp tất cả các đơn hàng liên quan
        $tables = Table::with(['orders' => function($query) {
            $query->with(['details.product', 'details.variant', 'payment'])
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

        // Nạp lại tất cả đơn hàng cho bàn này sau khi update status
        $table->load(['orders' => function($query) {
            $query->with(['details.product', 'details.variant', 'payment'])
                    ->latest();
        }]);

        broadcast(new TableStatusUpdated($table));

        return redirect()->back();
    }
}