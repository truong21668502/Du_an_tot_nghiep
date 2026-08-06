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
        // Lấy tất cả bàn và chỉ nạp các đơn hàng đang hoạt động (bao gồm cả hoàn thành)
        $tables = Table::with(['orders' => function($query) {
            $query->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED'])
                    ->with(['details.product', 'details.variant', 'payment'])
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

        // Nếu dọn bàn, ta tách các đơn hàng cũ (đã hoàn thành/hủy) ra khỏi bàn để khách mới vào không thấy
        if ($request->status === 'EMPTY') {
            // Kiểm tra xem có đơn hàng nào chưa hoàn thành hoặc chưa thanh toán không
            $hasUnfinishedOrders = $table->orders()->where(function($query) {
                $query->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                      ->orWhere(function($subQuery) {
                          $subQuery->where('status', 'COMPLETED')
                                   ->whereHas('payment', function($paymentQuery) {
                                       $paymentQuery->where('payment_status', '!=', 'PAID');
                                   });
                      });
            })->exists();

            if ($hasUnfinishedOrders) {
                return redirect()->back()->withErrors(['status' => 'Bàn còn đơn chưa hoàn thành hoặc chưa thanh toán, không thể dọn!']);
            }

            $table->orders()->whereIn('status', ['COMPLETED', 'CANCELLED'])->update(['table_id' => null]);
        }

        // Chỉ nạp lại các đơn hàng đang hoạt động cho bàn này
        $table->load(['orders' => function($query) {
            $query->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED'])
                    ->with(['details.product', 'details.variant', 'payment'])
                    ->latest();
        }]);

        broadcast(new TableStatusUpdated($table));

        return redirect()->back();
    }

    public function mergeTable(Request $request, Table $fromTable, Table $toTable)
    {
        if ($fromTable->id === $toTable->id) {
            return redirect()->back()->with('error', 'Không thể gộp cùng một bàn');
        }

        // Lấy các đơn hàng đang hoạt động của bàn A
        $activeOrders = $fromTable->orders()->whereIn('status', ['PENDING', 'PROCESSING', 'READY'])->get();

        foreach ($activeOrders as $order) {
            $order->update(['table_id' => $toTable->id]);
        }

        $newStatus = ($fromTable->status === 'OCCUPIED' || $toTable->status === 'OCCUPIED') ? 'OCCUPIED' : 'EMPTY';

        // Cập nhật sức chứa bàn B = B + A
        $toTable->update([
            'capacity' => $toTable->capacity + $fromTable->capacity,
            'status' => $newStatus
        ]);

        // Cập nhật bàn A thành trống và đánh dấu bị gộp
        $fromTable->update([
            'status' => 'EMPTY',
            'parent_table_id' => $toTable->id
        ]);

        // Gửi sự kiện cập nhật realtime cho cả 2 bàn
        broadcast(new TableStatusUpdated($fromTable));
        broadcast(new TableStatusUpdated($toTable));

        return redirect()->back()->with('success', 'Gộp bàn thành công');
    }

    public function unmergeTable(Request $request, Table $table)
    {
        // Kiểm tra xem bàn có đơn hàng chưa hoàn thành/thanh toán không
        $hasUnfinishedOrders = $table->orders()->where(function($query) {
            $query->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                  ->orWhere(function($subQuery) {
                      $subQuery->where('status', 'COMPLETED')
                               ->whereHas('payment', function($paymentQuery) {
                                   $paymentQuery->where('payment_status', '!=', 'PAID');
                               });
                  });
        })->exists();

        if ($hasUnfinishedOrders) {
            return redirect()->back()->with('error', 'Bàn đang có đơn chưa hoàn thành hoặc chưa thanh toán, không thể tách bàn!');
        }

        // Lấy tất cả bàn con đang bị gộp vào bàn này
        $children = Table::where('parent_table_id', $table->id)->get();
        if ($children->isEmpty()) {
            return redirect()->back()->with('error', 'Bàn này không có bàn gộp nào');
        }

        $totalCapacityToReduce = 0;
        foreach ($children as $child) {
            $totalCapacityToReduce += $child->capacity;
            $child->update([
                'parent_table_id' => null,
                'status' => 'EMPTY' // Trả lại trạng thái trống cho bàn con
            ]);
            broadcast(new TableStatusUpdated($child));
        }

        // Cập nhật lại sức chứa của bàn mẹ
        $table->update([
            'capacity' => $table->capacity - $totalCapacityToReduce
        ]);
        broadcast(new TableStatusUpdated($table));

        return redirect()->back()->with('success', 'Tách bàn thành công');
    }
}