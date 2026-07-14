<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

Route::get('/ai-api', function(){

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . env('GEMINI_API_KEY'),
    'Content-Type' => 'application/json',
])->post(
    'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
    [
        'model' => 'gemini-3.1-flash-lite',
        'messages' => [
            [
                'role' => 'user',
                'content' => 'chào bạn'
            ]
        ]
    ]
);

$message = $response->json('choices.0.message.content');

dd($message);
echo $message;
});


// Public Routes - Không cần đăng nhập
require __DIR__ . '/web/public.php';

// Customer Routes - Yêu cầu đăng nhập với role CUSTOMER
require __DIR__ . '/web/customer.php';

// Admin Routes - Yêu cầu đăng nhập với role ADMIN
require __DIR__ . '/web/admin.php';

// Staff Routes - Yêu cầu đăng nhập với role STAFF
require __DIR__ . '/web/staff.php';

// Barista Routes - Yêu cầu đăng nhập với role BARISTA
require __DIR__ . '/web/barista.php';

// API Routes
require __DIR__ . '/web/api.php';

// Authentication Routes
require __DIR__ . '/auth.php';
