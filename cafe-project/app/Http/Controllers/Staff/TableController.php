<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Table;
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

        $oldStatus = $table->status;
        $newStatus = $request->status;

        $table->update(['status' => $newStatus]);

        if ($oldStatus === 'RESERVED' && $newStatus === 'OCCUPIED') {
            $table->reservations()
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->update(['status' => 'ARRIVED']);
        }

        if ($newStatus === 'EMPTY') {
            // Chỉ hủy reservation đã qua giờ, giữ lại reservation tương lai
            $table->reservations()
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->where('reservation_time', '<', now())
                ->update(['status' => 'CANCELLED']);
        }

        $table->load([
            'reservations' => function ($query) {
                $query->whereIn('status', ['PENDING', 'CONFIRMED', 'ARRIVED'])
                    ->with('user:id,full_name,phone_number')
                    ->orderBy('reservation_time', 'asc');
            }
        ]);

        broadcast(new TableStatusUpdated($table))->toOthers();

        return redirect()->back();
    }
}