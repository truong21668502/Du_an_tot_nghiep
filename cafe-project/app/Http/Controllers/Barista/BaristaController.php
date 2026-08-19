<?php

namespace App\Http\Controllers\Barista;

use App\Http\Controllers\Controller;
use App\Models\OrderDetail;
use App\Events\BaristaDetailUpdated;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BaristaController extends Controller
{
    
    //Hàng đợi pha chế: lấy tất cả món đang chờ hoặc đang pha

    public function queue()
    {
        $queue = OrderDetail::with([
            'order.table',
            'product',
            'variant.recipes.material',
        ])
        ->whereIn('barista_status', ['PENDING', 'PREPARING'])
        ->whereHas('order', fn($q) => $q->whereIn('status', ['PROCESSING']))
        ->orderBy('created_at', 'asc')
        ->get();

        $todayDone = OrderDetail::where('barista_status', 'COMPLETED')
            ->whereDate('updated_at', today())
            ->count();

        return Inertia::render('Barista/Queue', [
            'initialQueue' => $queue,
            'todayDone'    => $todayDone,
        ]);
    }

    // Lịch sử pha chế trong ngày hôm nay
    public function history(Request $request)
    {
        $search = $request->input('search');

        $historyQuery = OrderDetail::with([
            'order.table',
            'product',
            'variant',
        ])
        ->where('barista_status', 'COMPLETED')
        ->whereDate('updated_at', today());

        if ($search) {
            $historyQuery->where(function ($q) use ($search) {
                // Tìm theo tên món
                $q->whereHas('product', function ($productQ) use ($search) {
                    $productQ->where('product_name', 'LIKE', "%{$search}%");
                })
                // Tìm theo mã đơn
                ->orWhere('order_id', 'LIKE', "%{$search}%")
                // Tìm theo tên bàn
                ->orWhereHas('order.table', function ($tableQ) use ($search) {
                    $tableQ->where('table_name', 'LIKE', "%{$search}%");
                })
                // Tìm theo ghi chú
                ->orWhere('note', 'LIKE', "%{$search}%");

                // Tìm theo mang đi / giao hàng
                $searchLower = mb_strtolower(trim($search), 'UTF-8');
                $types = [];
                if (str_contains($searchLower, 'mang') || str_contains($searchLower, 'take')) {
                    $types[] = 'TAKE_AWAY';
                }
                if (str_contains($searchLower, 'giao') || str_contains($searchLower, 'ship') || str_contains($searchLower, 'deli')) {
                    $types[] = 'DELIVERY';
                }
                
                if (!empty($types)) {
                    $q->orWhereHas('order', function($orderQ) use ($types) {
                        $orderQ->whereIn('order_type', $types);
                    });
                }
            });
        }

        $history = $historyQuery->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Barista/History', [
            'history' => $history,
            'filters' => $request->only('search'),
        ]);
    }
    
    //Cập nhật trạng thái pha chế của từng món PENDING → PREPARING → COMPLETED
    public function updateStatus(Request $request, OrderDetail $detail)
    {
        $transitions = [
            'PENDING'   => 'PREPARING',
            'PREPARING' => 'COMPLETED',
        ];

        $currentStatus = $detail->barista_status;

        if (!isset($transitions[$currentStatus])) {
            return response()->json(['error' => 'Trạng thái không hợp lệ để chuyển tiếp.'], 400);
        }

        $newStatus = $transitions[$currentStatus];
        
        if ($newStatus === 'COMPLETED') {
            $stockService = new \App\Services\StockService();
            // Tải quan hệ variant.recipes và product để sử dụng trong transaction
            $detail->load(['variant.recipes', 'product']);
            
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($detail, $stockService, $newStatus) {
                    foreach ($detail->variant->recipes as $recipe) {
                        $qtyToDeduct = (float) $recipe->quantity_needed * (int) $detail->quantity;
                        $stockService->consume(
                            $recipe->material_id, 
                            $qtyToDeduct, 
                            'order', 
                            $detail->order_id, 
                            "Pha chế món {$detail->product->product_name} x{$detail->quantity}"
                        );
                    }
                    $detail->update(['barista_status' => $newStatus]);
                });
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        } else {
            $detail->update(['barista_status' => $newStatus]);
        }

        // Broadcast realtime để Staff thấy trạng thái món thay đổi
        broadcast(new BaristaDetailUpdated($detail->load(['order.table', 'product', 'variant'])))->toOthers();

        return response()->json([
            'id'             => $detail->id,
            'barista_status' => $newStatus,
        ]);
    }
}