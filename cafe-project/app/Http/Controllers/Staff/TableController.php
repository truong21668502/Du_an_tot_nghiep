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
        $request->validate([
            'status' => 'required|in:EMPTY,OCCUPIED,RESERVED'
        ]);

        $oldStatus = $table->status;
        $newStatus = $request->status;

        $table->update(['status' => $newStatus]);

        if ($oldStatus === 'RESERVED' && $newStatus === 'OCCUPIED') {
            $table->reservations()
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->update(['status' => 'ARRIVED']);
        }

        $table->load(['reservations' => function($query) {
            $query->whereIn('status', ['PENDING', 'CONFIRMED', 'ARRIVED'])
                ->orderBy('reservation_time', 'asc');
        }]);

        broadcast(new TableStatusUpdated($table))->toOthers();

        return redirect()->back();
    }

    public function updateReservationStatus(Request $request, TableReservation $reservation)
    {
        $request->validate([
            'status' => 'required|in:CONFIRMED,CANCELLED'
        ]);

        // Cập nhật trạng thái phiếu đặt bàn
        $reservation->update(['status' => $request->status]);

        // Cập nhật lại trạng thái của cái Bàn đó
        /** @var \App\Models\Table $table */
        $table = $reservation->table;
        
        if ($request->status === 'CANCELLED') {
            // Nếu từ chối -> Bàn trở lại trạng thái TRỐNG
            $table->update(['status' => 'EMPTY']);
        } elseif ($request->status === 'CONFIRMED') {
            // Nếu xác nhận -> Đảm bảo bàn đang ở trạng thái ĐÃ ĐẶT
            $table->update(['status' => 'RESERVED']);
        }

        // Load lại dữ liệu chuẩn để bắn Real-time
        $table->load(['reservations' => function($query) {
            $query->whereIn('status', ['PENDING', 'CONFIRMED', 'ARRIVED'])
                ->with('user')
                ->orderBy('reservation_time', 'asc');
        }]);

        broadcast(new TableStatusUpdated($table))->toOthers();

        return redirect()->back();
    }
}