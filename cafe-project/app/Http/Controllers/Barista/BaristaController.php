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

    /**
     * Lịch sử pha chế trong ngày hôm nay
     */
    public function history()
    {
        $history = OrderDetail::with([
            'order.table',
            'product',
            'variant',
        ])
        ->where('barista_status', 'COMPLETED')
        ->whereDate('updated_at', today())
        ->orderBy('updated_at', 'desc')
        ->get();

        return Inertia::render('Barista/History', [
            'history' => $history,
        ]);
    }

    /**
     * Cập nhật trạng thái pha chế của từng món
     * PENDING → PREPARING → COMPLETED
     */
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
            // Load necessary relationships to get the recipe and material
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
