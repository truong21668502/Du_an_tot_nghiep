<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TableReservation;
use App\Events\TableStatusUpdated;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function updateStatus(Request $request, Table $table)
    {
        // Chỉ cho phép 2 trạng thái trống và đang sử dụng
        $request->validate([
            'status' => 'required|in:EMPTY,OCCUPIED'
        ]);

        // Lưu DB
        $table->update(['status' => $request->status]);

        // Bắn luồng Real-time qua cho các máy khác
        broadcast(new TableStatusUpdated($table))->toOthers();

        return redirect()->back();
    }
}