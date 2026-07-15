<?php

namespace App\Observers;

use App\Events\OrderStatusUpdated;
use App\Events\TableStatusUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order): void
    {
        if ($order->table_id) {
            $table = $order->table()->first();
            if ($table) {
                $table->update(['status' => 'OCCUPIED']);
                broadcast(new TableStatusUpdated($table));
            }
        }
    }

    public function updated(Order $order): void
    {
        Log::info('OrderObserver::updated được gọi', [
            'order_id' => $order->id,
            'wasChanged' => $order->wasChanged('status'),
            'status' => $order->status,
        ]);

        if (!$order->wasChanged('status')) {
            return;
        }
        if ($order->status === 'COMPLETED') {
            app(\App\Services\RecipeStockService::class)->deductStock($order);
        }

        try {
            event(new OrderStatusUpdated($order));
            Log::info('Dispatch OrderStatusUpdated THÀNH CÔNG', ['order_id' => $order->id]);
        } catch (\Throwable $e) {
            Log::error('Dispatch OrderStatusUpdated LỖI: ' . $e->getMessage());
        }

        if ($order->table_id && in_array($order->status, ['COMPLETED', 'CANCELLED'])) {
            $hasActiveOrder = Order::where('table_id', $order->table_id)
                ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                ->where('id', '!=', $order->id)
                ->exists();

            if (!$hasActiveOrder) {
                $table = $order->table()->first();
                if ($table) {
                    $table->update(['status' => 'EMPTY']);
                    broadcast(new TableStatusUpdated($table));
                }
            }
        }
    }

    public function deleted(Order $order): void
    {
    }
    public function restored(Order $order): void
    {
    }
    public function forceDeleted(Order $order): void
    {
    }
}