<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponUserSeeder extends Seeder
{
    public function run(): void
    {
        // Tắt khóa ngoại để xóa sạch bảng phục vụ việc test
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('coupon_user')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Giả định bạn đã có User ID 6, 7  và các Coupon ID từ 1 đến 3 (từ CouponSeeder trước)
        DB::table('coupon_user')->insert([
            // 👤 Người dùng ID = 1 sở hữu 3 mã khác nhau
            [
                'user_id'     => 6,
                'coupon_id'   => 1, // Mã CHAOMUONG (Giảm 20k)
                'is_used'     => false, // Chưa dùng
                'used_at'     => null,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'user_id'     => 7,
                'coupon_id'   => 2, // Mã SVFOLY (Giảm 10%)
                'is_used'     => false, // Chưa dùng
                'used_at'     => null,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'user_id'     => 8,
                'coupon_id'   => 3, // Mã DOIDIEM2K (Mã đổi điểm 2.000đ)
                'is_used'     => true, // Món này giả định ĐÃ SỬ DỤNG RỒI
                'used_at'     => Carbon::now()->subDays(2), // Đã dùng cách đây 2 ngày
                'created_at'  => Carbon::now()->subDays(5),
                'updated_at'  => Carbon::now()->subDays(2),
            ],

            // 👤 Người dùng ID = 2 cũng sở hữu mã đổi điểm 2k giống User 1
            [
                'user_id'     => 6,
                'coupon_id'   => 3,
                'is_used'     => false,
                'used_at'     => null,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
        ]);
    }
}