<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TableReservation;
use Illuminate\Support\Facades\DB;
use App\Events\TableStatusUpdated;

class BookingController extends Controller
{
    public function index()
    {
        return Inertia::render('Booking');
    }

    public function tables()
    {
        $table = Table::orderBy('table_name')
            ->get(['id', 'table_name', 'area', 'capacity', 'qr_code', 'status']);

        return response()->json([
            'success' => true,
            'data' => $table,
        ]);
    }

    public function reservations(Request $request)
    {
        $date = $request->query('date', today()->toDateString());

        $reservations = TableReservation::with('table:id,table_name')
            ->whereDate('reservation_time', $date)
            ->where('user_id', $request->user()->id)
            ->orderBy('reservation_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'phone_number' => 'required|string|min:10|max:20',
            'guest_count' => 'required|integer|min:1',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required|date_format:H:i',
            'note' => 'nullable|string|max:255',
        ]);

        $fullReservationTime = \Carbon\Carbon::parse(
            $validated['reservation_date'] . ' ' . $validated['reservation_time']
        );

        DB::transaction(function () use ($validated, $request, $fullReservationTime) {
            $table = Table::lockForUpdate()->findOrFail($validated['table_id']);

            if ($table->status !== 'EMPTY') {
                abort(422, 'Bàn này vừa được đặt bởi người khác.');
            }

            TableReservation::create([
                'table_id' => $validated['table_id'],
                'user_id' => $request->user()->id,
                'phone_number' => $validated['phone_number'],
                'guest_count' => $validated['guest_count'],
                'reservation_time' => $fullReservationTime,
                'note' => $validated['note'] ?? null,
                'status' => 'PENDING',
            ]);

            // ✅ Chỉ RESERVED nếu đặt bàn trong hôm nay
            $isToday = $fullReservationTime->isToday();
            if ($isToday) {
                $table->update(['status' => 'RESERVED']);
                broadcast(new TableStatusUpdated($table->fresh()));
            }
            // Nếu đặt ngày mai trở đi → bàn vẫn EMPTY, không broadcast
        });

        return response()->json([
            'success' => true,
            'message' => 'Đặt bàn thành công!',
        ]);
    }
}