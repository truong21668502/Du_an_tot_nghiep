<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TableReservation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'date_range']);
        $query = TableReservation::with(['user:id,full_name,email', 'table:id,table_name,area,capacity']);

        // Tìm kiếm theo Tên khách, Số điện thoại hoặc Tên bàn
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('phone_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('user', function ($u) use ($request) {
                        $u->where('full_name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('table', function ($t) use ($request) {
                        $t->where('table_name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Lọc theo trạng thái đặt bàn
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo khoảng thời gian giám sát (Mặc định nếu trống sẽ lấy trong tháng này)
        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('reservation_time', Carbon::today());
                    break;
                case 'tomorrow':
                    $query->whereDate('reservation_time', Carbon::tomorrow());
                    break;
                case 'this_week':
                    $query->whereBetween('reservation_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('reservation_time', Carbon::now()->month)
                        ->whereYear('reservation_time', Carbon::now()->year);
                    break;
            }
        }

        // Tính toán các chỉ số thống kê nhanh (Dashboard) hiển thị phía trên cho Admin
        $todayStr = Carbon::today()->toDateString();
        $status = [
            'total_pending'   => TableReservation::where('status', 'PENDING')->count(),
            'total_confirmed' => TableReservation::where('status', 'CONFIRMED')->count(),
            'total_arrived'   => TableReservation::where('status', 'ARRIVED')->whereDate('reservation_time', $todayStr)->count(),
            'total_cancelled' => TableReservation::where('status', 'CANCELLED')->whereDate('reservation_time', $todayStr)->count(),
        ];

        $reservations = $query->orderBy('reservation_time', 'desc')
                            ->paginate(10)
                            ->withQueryString();

        return Inertia::render('Admin/Reservations/Index', [
            'reservations' => $reservations,
            'filters'      => $filters,
            'status'        => $status
        ]);
    }

    // Quyền tối cao của Admin: Ép trạng thái (Override) nếu nhân viên làm sai
    public function overrideStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:PENDING,CONFIRMED,ARRIVED,CANCELLED']);
        
        $reservation = TableReservation::findOrFail($id);
        $reservation->update(['status' => $request->status]);

        return redirect()->back()->with('toast-success', 'Admin đã ghi đè trạng thái lịch đặt thành công!');
    }
}