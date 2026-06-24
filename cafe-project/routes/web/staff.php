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
            
        // Lấy toàn bộ danh sách bàn
        $tables = Table::with(['orders' => function($query) {
            // Chỉ lấy đơn hàng chưa hoàn tất thanh toán của bàn đó
            $query->where('payment_status', 'PENDING')
                ->with('orderDetails') // Kéo theo chi tiết món để đếm
                ->latest(); // Lấy đơn mới nhất
        }])->orderBy('id', 'asc')->get();

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
        // Lấy toàn bộ danh sách bàn
        $tables = Table::orderBy('id', 'asc')->get(); 

        return Inertia::render('Staff/Tables', [
            'initialTables' => $tables
        ]);
    })->name('bookings.index');
    
    // Route cập nhật trạng thái bàn (Trống <-> Có khách)
    Route::patch('/so-do-ban/{table}/trang-thai', [TableController::class, 'updateStatus'])->name('tables.update-status');

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

        // Nạp thông tin Bàn và Chi tiết món vào biến $order
        $order->load(['table', 'orderDetails.product']);

        // Bắn event
        broadcast(new OrderCreated($order));

        return "Đã tạo đơn hàng giả thành công và bắn event real-time!";
    });
});
