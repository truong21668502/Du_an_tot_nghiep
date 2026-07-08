<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\OrderController;
use App\Http\Controllers\Staff\TableController;
use App\Models\Order;
use App\Models\Table;
use App\Events\OrderCreated;
use App\Events\TableStatusUpdated;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:STAFF,ADMIN'])->prefix('nhan-vien')->name('staff.')->group(function () {
    
    // Route dashboard
    Route::get('/bang-dieu-khien', [DashboardController::class, 'index'])->name('dashboard');

    // Route quản lý đơn hàng
    Route::get('/don-hang', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/don-hang', [OrderController::class, 'store'])->name('orders.store');
    Route::patch('/don-hang/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::patch('/don-hang/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::patch('/don-hang/{order}/huy', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('/don-hang/{order}/xac-nhan-thanh-toan', [OrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
    Route::get('/don-hang/{order}/vnpay-url', [OrderController::class, 'getVnpayUrl'])->name('orders.vnpay-url');

    // Route quản lý bàn (Sơ đồ mặt bằng)
    Route::get('/so-do-ban', [TableController::class, 'index'])->name('tables.index');
    Route::patch('/so-do-ban/{table}/trang-thai', [TableController::class, 'updateStatus'])->name('tables.update-status');

    // Route test tạo đơn hàng giả và bắn event real-time
    Route::get('/test-tao-don', function () {
        $order = Order::create([
            'total_amount' => 20000, 
            'discount_amount' => 0,
            'final_amount' => 20000, 
            'order_type' => 'DINE_IN',
            'table_id' => 1, 
            'status' => 'PENDING', 
        ]);

        $order->orderDetails()->create([
            'product_id' => 2, 
            'variant_id' => 3, 
            'quantity' => 1, 
            'unit_price' => 20000
        ]);

        if ($order->table_id) {
            $table = Table::find($order->table_id);
            if ($table && $table->status === 'EMPTY') {
                $table->update(['status' => 'OCCUPIED']);
                broadcast(new TableStatusUpdated($table));
            }
        }

        $order->load(['table', 'orderDetails.product']);
        broadcast(new OrderCreated($order));

        return "Đã tạo đơn hàng giả, lưu trạng thái bàn vào DB và bắn event real-time!";
    });
});