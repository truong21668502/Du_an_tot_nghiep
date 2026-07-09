<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Table;
use App\Events\OrderStatusUpdated;
use App\Events\TableStatusUpdated;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
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

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if (!$order->wasChanged('status')) {
            return;
        }

        broadcast(new OrderStatusUpdated($order));

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

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
