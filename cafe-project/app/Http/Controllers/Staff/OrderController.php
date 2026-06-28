<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table; 
use App\Events\OrderCreated; 
use App\Events\TableStatusUpdated;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $activeOrders = Order::with(['table', 'orderDetails.product', 'orderDetails.variant'])
            ->whereIn('status', ['PENDING', 'PROCESSING']) 
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Staff/Orders', [
            'initialOrders' => $activeOrders,     
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'required',
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric',
        ]);

        $order = Order::create([
            'order_type' => $request->order_type,
            'table_id' => $request->order_type === 'DINE_IN' ? $request->table_id : null,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'status' => 'PENDING',
            'total_amount' => $request->total_amount,
            'final_amount' => $request->total_amount,
            'discount_amount' => 0,
        ]);

        foreach($request->items as $item) {
            $order->orderDetails()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'note' => $item['note'],
            ]);
        }

        if ($order->table_id) {
            $table = Table::find($order->table_id);
            if ($table) {
                $table->update(['status' => 'OCCUPIED']);
                broadcast(new TableStatusUpdated($table));
            }
        }
        
        broadcast(new OrderCreated($order->load(['table', 'orderDetails.product', 'orderDetails.variant'])));

        return redirect()->back()->with('success', 'Đơn hàng đã được tạo!');
    }

    public function accept(Order $order)
    {
        if ($order->status !== 'PENDING') {
            return redirect()->back()->with('error', 'Đơn hàng này đã được xử lý!');
        }

        $order->update(['status' => 'PROCESSING']);
        return redirect()->back();
    }

    public function complete(Order $order)
    {
        $order->update(['status' => 'COMPLETED']);
        
        if ($order->table_id) {
            $remainingOrders = Order::where('table_id', $order->table_id)
                                    ->whereIn('status', ['PENDING', 'PROCESSING'])
                                    ->count();
            if ($remainingOrders === 0) {
                $table = Table::find($order->table_id);
                $table->update(['status' => 'EMPTY']);
                broadcast(new TableStatusUpdated($table));
            }
        }

        return redirect()->back();
    }

    public function cancel(Order $order)
    {
        if ($order->status !== 'PENDING') {
            return redirect()->back()->with('error', 'Chỉ có thể hủy đơn hàng đang chờ xử lý!');
        }

        $order->update(['status' => 'CANCELLED']);
        
        if ($order->table_id) {
            $remainingOrders = Order::where('table_id', $order->table_id)
                                    ->whereIn('status', ['PENDING', 'PROCESSING'])
                                    ->count();
            if ($remainingOrders === 0) {
                $table = Table::find($order->table_id);
                if ($table) {
                    $table->update(['status' => 'EMPTY']);
                    broadcast(new TableStatusUpdated($table));
                }
            }
        }

        return redirect()->back();
    }
}