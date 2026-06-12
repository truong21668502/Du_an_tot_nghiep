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

        if ($fullReservationTime->isPast()) {
            abort(422, 'Không thể đặt bàn cho thời gian trong quá khứ.');
        }

        $userId = $request->user()->id;

        // ✅ Check: user đã có reservation active ở khung giờ gần đây chưa
        $duplicateSlot = TableReservation::where('user_id', $userId)
            ->whereIn('status', ['PENDING', 'CONFIRMED'])
            ->whereBetween('reservation_time', [
                $fullReservationTime->copy()->subHours(2),
                $fullReservationTime->copy()->addHours(2),
            ])
            ->exists();

        if ($duplicateSlot) {
            abort(422, 'Bạn đã có lượt đặt bàn khác gần khung giờ này. Vui lòng chọn giờ khác hoặc hủy lượt đặt cũ.');
        }

        // ✅ Check: giới hạn tổng số lượt đặt active trong ngày
        // ✅ Check: giới hạn tổng số lượt đặt active trong ngày
        $maxPerDay = 3; // điều chỉnh số này theo policy quán

        $countToday = TableReservation::where('user_id', $userId)
            ->whereIn('status', ['PENDING', 'CONFIRMED'])
            ->whereDate('reservation_time', $fullReservationTime->toDateString())
            ->count();

        if ($countToday >= $maxPerDay) {
            abort(422, "Bạn đã đạt giới hạn {$maxPerDay} lượt đặt bàn trong ngày {$fullReservationTime->format('d/m/Y')}.");
        }

        DB::transaction(function () use ($validated, $request, $fullReservationTime) {
            $table = Table::lockForUpdate()->findOrFail($validated['table_id']);

            // ✅ Check trùng lịch của BÀN với reservation khác
            $conflict = TableReservation::where('table_id', $validated['table_id'])
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->whereBetween('reservation_time', [
                    $fullReservationTime->copy()->subHours(2),
                    $fullReservationTime->copy()->addHours(2),
                ])
                ->exists();

            if ($conflict) {
                abort(422, 'Bàn này đã có người đặt vào khung giờ gần đó (trong vòng 2 tiếng).');
            }

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
            if ($fullReservationTime->isToday()) {
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