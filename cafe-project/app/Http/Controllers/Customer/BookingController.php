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

        $reservations = TableReservation::with('table:id, table_name')
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
            'reservation_time' => 'required|date|after:now',
            'note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $table = Table::lockForUpdate()->findOrFail($validated['table_id']);

            if ($table->status !== 'EMPTY') {
                abort(422, 'Bàn này vừa được đặt bởi người khác.');
            }

            TableReservation::create([
                ...$validated,
                'user_id' => $request->user()->id,
                'status' => 'PENDING',
            ]);

            $table->update(['status' => 'RESERVED']);

            broadcast(new TableStatusUpdated($table->fresh()))->toOthers();
        });

        return response()->json([
            'success' => true,
            'message' => 'Đặt bàn thành công!',
        ]);
    }
}
