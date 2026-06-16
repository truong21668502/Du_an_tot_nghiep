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

        $maxPerDay = 3;

        $countToday = TableReservation::where('user_id', $userId)
            ->whereIn('status', ['PENDING', 'CONFIRMED'])
            ->whereDate('reservation_time', $fullReservationTime->toDateString())
            ->count();

        if ($countToday >= $maxPerDay) {
            abort(422, "Bạn đã đạt giới hạn {$maxPerDay} lượt đặt bàn trong ngày {$fullReservationTime->format('d/m/Y')}.");
        }

        DB::transaction(function () use ($validated, $request, $fullReservationTime) {
            $table = Table::lockForUpdate()->findOrFail($validated['table_id']);

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

            TableReservation::create([
                'table_id' => $validated['table_id'],
                'user_id' => $request->user()->id,
                'phone_number' => $validated['phone_number'],
                'guest_count' => $validated['guest_count'],
                'reservation_time' => $fullReservationTime,
                'note' => $validated['note'] ?? null,
                'status' => 'PENDING',
            ]);

            // Broadcast ngay để admin thấy reservation mới (dù bàn vẫn EMPTY)
            broadcast(new TableStatusUpdated($table->fresh()));

            // Nếu giờ đặt trong 15 phút tới → đổi bàn thành RESERVED luôn
            if (
                $table->status === 'EMPTY'
                && $fullReservationTime->between(now(), now()->addMinutes(15))
            ) {
                $table->update(['status' => 'RESERVED']);
                broadcast(new TableStatusUpdated($table->fresh()));
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Đặt bàn thành công!',
        ]);
    }

    public function cancel(Request $request, TableReservation $reservation)
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền hủy lượt đặt bàn này.');
        }

        if (!in_array($reservation->status, ['PENDING', 'CONFIRMED'])) {
            abort(422, 'Lượt đặt bàn này không thể hủy.');
        }

        if (
            $reservation->reservation_time->isFuture()
            && now()->diffInMinutes($reservation->reservation_time) < 10
        ) {
            abort(422, 'Không thể hủy đặt bàn trong vòng 10 phút trước giờ hẹn.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'CANCELLED']);

            $table = Table::findOrFail($reservation->table_id);

            $hasOtherActive = $table->reservations()
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->where('id', '!=', $reservation->id)
                ->exists();

            if (!$hasOtherActive && $table->status === 'RESERVED') {
                $table->update(['status' => 'EMPTY']);
            }

            $table->load([
                'reservations' => function ($query) {
                    $query->whereIn('status', ['PENDING', 'CONFIRMED', 'ARRIVED'])
                        ->with('user:id,full_name,phone_number')
                        ->orderBy('reservation_time', 'asc');
                }
            ]);

            broadcast(new TableStatusUpdated($table));
        });

        return response()->json([
            'success' => true,
            'message' => 'Hủy đặt bàn thành công.',
        ]);
    }
}