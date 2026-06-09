<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        // Tắt ràng buộc khóa ngoại để xóa sạch dữ liệu cũ phục vụ test
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('coupons')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('coupons')->insert([
            [
                'code' => 'CHAOMUONG',
                'discount_type' => 'FIXED',
                'discount_value' => 20000.00, // Giảm 20k
                'max_discount_amount' => null, // Cố định thì không cần mục này
                'min_order_value' => 50000.00, // Đơn từ 50k mới được áp dụng
                'usage_limit' => 100,          // Giới hạn 100 lượt dùng tổng hệ thống
                'used_count' => 0,
                'expiration_date' => Carbon::now()->addMonths(2), // Hết hạn sau 2 tháng
                'status' => 'ACTIVE',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'SVFOLY',
                'discount_type' => 'PERCENTAGE',
                'discount_value' => 10.00,           // Giảm 10%
                'max_discount_amount' => 15000.00,   // Giảm tối đa 15k tránh lỗ vốn
                'min_order_value' => 40000.00,       // Đơn từ 40k
                'usage_limit' => 200,
                'used_count' => 0,
                'expiration_date' => Carbon::now()->addMonth(),
                'status' => 'ACTIVE',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'DOIDIEM2K',
                'discount_type' => 'FIXED',
                'discount_value' => 2000.00,          // Giảm 2.000đ
                'max_discount_amount' => null,
                'min_order_value' => 0.00,            // Đơn nào cũng dùng được
                'usage_limit' => null,                // Không giới hạn số lượng đổi toàn hệ thống
                'used_count' => 0,
                'expiration_date' => Carbon::now()->addYear(), // Hạn dài cho việc đổi điểm
                'status' => 'ACTIVE',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}