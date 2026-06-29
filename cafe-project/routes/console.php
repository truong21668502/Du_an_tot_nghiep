<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Order;
use Illuminate\Support\Facades\DB;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('reservations:activate')->everyMinute();
Schedule::command('reservations:release-expired')->everyMinute();



Schedule::call(function () {
    $expiredOrders = Order::where('status', 'PENDING')
        ->whereHas('payment', function ($query) {
            $query->where('payment_method', 'BANK_TRANSFER')
                ->where('payment_status', 'PENDING');
        })
        ->where('created_at', '<=', now()->subMinutes(15))
        ->get();

    foreach ($expiredOrders as $order) {
        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 'CANCELLED',
                'cancel_reason' => 'Tự động hủy do quá 15 phút chưa thanh toán VNPay',
            ]);
            $order->payment->update(['payment_status' => 'FAILED']);
        });
    }
})->everyMinute();