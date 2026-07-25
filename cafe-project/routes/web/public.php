<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\TestRealTime;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\PostController;
use App\Http\Controllers\Customer\PostCommentController;
use App\Http\Controllers\Customer\TableOrderController;
use App\Http\Controllers\Customer\ContactController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\VnpayController;

use App\Http\Controllers\Customer\ChatController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Trang chủ
use App\Http\Controllers\Customer\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');



// Thực đơn
Route::get('/thuc-don', [ProductController::class, 'index'])->name('customer.menu.index');
Route::get('/thuc-don/{slug}', [ProductController::class, 'show'])->name('product.show');

//!!! quan trong không rờ lung tung!!!
Route::get('/ban/{qr_code}', [TableOrderController::class, 'index'])->name('table.order');


Route::post('/orders', [OrderController::class, 'store'])->name('customer.orders.store');
Route::get('/don-hang/{order}/pending', [OrderController::class, 'pending'])->name('customer.orders.pending');

Route::get('/vnpay-ket-qua', [VnpayController::class, 'return'])->name('vnpay.return');


// Về chúng tôi
Route::get('/ve-chung-toi', function () {
    return Inertia::render('About');
})->name('about');

// Liên hệ
Route::get('/lien-he', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::post('/contact/send', [ContactController::class, 'send'])->name('contact.send');

route::get('/cc' , function () {
    return Inertia::render('cc');
});

// Blog
Route::get('/bai-viet', [PostController::class, 'index'])->name('blog.index');
Route::get('/bai-viet/danh-muc/{slug}', [PostController::class, 'category'])->name('blog.category');
Route::get('/bai-viet/{slug}', [PostController::class, 'dispatch'])->name('blog.dispatch');

Route::middleware('auth')->group(function () {
    Route::post('/bai-viet/{postId}/binh-luan', [PostCommentController::class, 'store'])
        ->name('comments.store');

    Route::put('/bai-viet/{postId}/binh-luan/{comment}', [PostCommentController::class, 'update'])
        ->name('comments.update');

    Route::delete('/bai-viet/{postId}/binh-luan/{comment}', [PostCommentController::class, 'destroy'])
        ->name('comments.destroy');
});


// !!!!!!!!!!!!!!! KHÔNG TỰ TIỆN RỜ VÀO QUAN TRỌNG !!!!!!!!!!!!!!!!!!!!!!!!!!
// Route::post('/chat', [ChatController::class, 'message'])->name('chat.message');
Route::get('/chat-test', function () {
    return Inertia::render('ChatTest');
});

Route::prefix('chat')->group(function () {
    Route::post('/', [ChatController::class, 'message'])->name('chat.message');
    
    // History management
    Route::get('/history', [ChatController::class, 'getHistory'])->name('chat.history');
    Route::get('/conversations', [ChatController::class, 'getConversations'])->name('chat.conversations');
    
    // Delete operations
    Route::delete('/messages', [ChatController::class, 'deleteMessages'])->name('chat.delete-messages');
    Route::delete('/messages/all', [ChatController::class, 'deleteAllMessages'])->name('chat.delete-all-messages');
    Route::delete('/conversation', [ChatController::class, 'deleteConversation'])->name('chat.delete-conversation');
    
    // Summary
    Route::post('/regenerate-summary', [ChatController::class, 'regenerateSummary'])->name('chat.regenerate-summary');
});

use Illuminate\Support\Facades\Http;

Route::get('/test-map', function () {
    // --- TỌA ĐỘ CAO ĐẲNG FPT POLYTECHNIC ĐÀ NẴNG ---
    $lat = 16.0757867;
    $lon = 108.1699074;
    $radius = 5000; // 5km

    $url = 'https://overpass-api.de/api/interpreter';


    // CÂU LỆNH TRUY VẤN: Lấy tất cả các thực thể (node, way, relation) có gắn thẻ số nhà trong bán kính 5km
    $query = '[out:json][timeout:60];(node["addr:housenumber"](around:' . $radius . ',' . $lat . ',' . $lon . ');way["addr:housenumber"](around:' . $radius . ',' . $lat . ',' . $lon . '););out tags;';

    // Gửi request lên hệ thống Overpass API
    $response = Http::asForm()
        ->withHeaders([
            'User-Agent' => 'Laravel-House-Scanner/1.0'
        ])
        ->post($url, [
            'data' => $query
        ]);

    if ($response->failed()) {
        return response()->json([
            'error' => 'Không thể kết nối API', 
            'details' => $response->body()
        ], 500);
    }

    $data = $response->json();
    $houseList = [];

    if (isset($data['elements'])) {
        foreach ($data['elements'] as $element) {
            $tags = $element['tags'] ?? [];
            $houseNumber = $tags['addr:housenumber'] ?? '';
            $streetName = $tags['addr:street'] ?? '';

            // Chỉ lấy các bản ghi có đầy đủ cả số nhà và tên đường
            if (!empty($houseNumber) && !empty($streetName)) {
                $fullAddress = $houseNumber . ' ' . $streetName;
                $houseList[] = $fullAddress;
            }
        }
    }

    // Loại bỏ các địa chỉ bị trùng lặp trong tệp dữ liệu
    $houseList = array_unique($houseList);

    // Sắp xếp danh sách địa chỉ nhà theo thứ tự tự nhiên (1, 2, 10, 100 thay vì 1, 10, 100, 2)
    sort($houseList, SORT_NATURAL);

    return response()->json([
        'success' => true,
        'location' => 'Khu vực quanh Cao đẳng FPT Polytechnic Đà Nẵng',
        'radius' => '5km',
        'total_houses_found' => count($houseList),
        'data' => array_values($houseList)
    ]);
});




