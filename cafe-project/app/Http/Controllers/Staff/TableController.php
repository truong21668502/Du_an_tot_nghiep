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

    public function updateReservationStatus(Request $request, TableReservation $reservation)
    {
        $request->validate([
            'status' => 'required|in:CONFIRMED,CANCELLED'
        ]);

        $reservation->update(['status' => $request->status]);

        /** @var \App\Models\Table $table */
        $table = $reservation->table;

        if ($request->status === 'CANCELLED') {
            // Hủy → kiểm tra còn reservation PENDING/CONFIRMED nào khác không
            // Nếu không còn → bàn về EMPTY
            $hasOtherActive = $table->reservations()
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->where('id', '!=', $reservation->id)
                ->exists();

            if (!$hasOtherActive) {
                $table->update(['status' => 'EMPTY']);
            }

        } elseif ($request->status === 'CONFIRMED') {
            // Xác nhận → KHÔNG đổi status bàn ngay
            // Chỉ đổi RESERVED nếu giờ hẹn trong 15 phút tới (khớp với cron)
            if ($reservation->reservation_time->between(now(), now()->addMinutes(15))) {
                $table->update(['status' => 'RESERVED']);
            }
            // Nếu còn xa → giữ nguyên EMPTY, cron sẽ tự đổi khi đến giờ
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