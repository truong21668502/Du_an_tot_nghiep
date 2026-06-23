<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Staff\OrderController;
use App\Models\Order;
use App\Events\OrderCreated;
use App\Http\Controllers\Staff\TableController;
use App\Models\Table;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:STAFF'])->prefix('nhan-vien')->name('staff.')->group(function () {
    
    // Route dashboard
    Route::get('/bang-dieu-khien', function () {
        $activeOrders = Order::with(['table', 'orderDetails.product'])
            ->whereIn('status', ['PENDING', 'PROCESSING']) 
            ->orderBy('created_at', 'asc')
            ->get();
        // Lấy danh sách bàn cùng với các đặt trước đang chờ/xác nhận để hiển thị trên dashboard
        $tables = Table::with(['reservations' => function($query) {
            $query->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->with('user') // Lấy thông tin khách hàng đặt bàn
                ->orderBy('reservation_time', 'asc');
        }])->get();

        return Inertia::render('Staff/Dashboard', [
            'initialOrders' => $activeOrders,
            'initialTables' => $tables 
        ]);
    })->name('dashboard');

    // Route quản lý đơn hàng
    Route::get('/don-hang', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('/don-hang/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::patch('/don-hang/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    // Route quản lý bàn (Sơ đồ mặt bằng)
    Route::get('/so-do-ban', function () {
        $tables = Table::with(['reservations' => function($query) {
            $query->whereIn('status', ['PENDING', 'CONFIRMED'])
                    ->with('user')
                    ->orderBy('reservation_time', 'asc');
        }])->get(); 

        return Inertia::render('Staff/Bookings', [
            'initialTables' => $tables
        ]);
    })->name('bookings.index');
    
    Route::patch('/so-do-ban/{table}/trang-thai', [TableController::class, 'updateStatus'])->name('tables.update-status');
    Route::patch('/so-do-ban/{reservation}/trang-thai', [TableController::class, 'updateReservationStatus'])->name('reservations.update-status');

    // Route test tạo đơn hàng giả và bắn event real-time
    Route::get('/test-tao-don', function () {
        $order = Order::create([
            'total_amount' => 50000, 
            'discount_amount' => 0,
            'final_amount' => 50000, 
            'payment_status' => 'PENDING',
            'order_type' => 'DINE_IN',
            'table_id' => 1, 
            'status' => 'PENDING', 
        ]);

        $order->orderDetails()->create([
            'product_id' => 2, // Cà phê sữa đá
            'variant_id' => 3, 
            'quantity' => 1, 
            'unit_price' => 20000
        ]);

        // Bắn event
        broadcast(new OrderCreated($order));

        return "Đã tạo đơn hàng giả thành công và bắn event real-time!";
    });
});