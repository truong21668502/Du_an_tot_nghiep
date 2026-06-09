<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportReceiptSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('import_receipts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('import_receipts')->insert([
            [
                'id'            => 1,
                'user_id'       => 1, // Admin nhập kho
                'supplier_name' => 'Công ty Đại lý Sữa Vinamilk & Dalat Milk',
                'total_cost'    => 1410000.00, // Khớp chi tiết phiếu 1
                'created_at'    => Carbon::now()->subDays(10),
                'updated_at'    => Carbon::now()->subDays(10),
            ],
            [
                'id'            => 2,
                'user_id'       => 1,
                'supplier_name' => 'Nhà cung cấp Cà phê hạt Robusta & Espresso Tây Nguyên',
                'total_cost'    => 3215000.00, // Đã cộng thêm 15k tiền muối hồng ở phiếu 2
                'created_at'    => Carbon::now()->subDays(7),
                'updated_at'    => Carbon::now()->subDays(7),
            ],
            [
                'id'            => 3,
                'user_id'       => 1,
                'supplier_name' => 'Chợ Đầu Mối Trái Cây & Nông Sản Đà Nẵng',
                'total_cost'    => 1205000.00, // Đã cộng thêm tiền trứng gà tươi mua kèm ở chợ
                'created_at'    => Carbon::now()->subDays(3),
                'updated_at'    => Carbon::now()->subDays(3),
            ],
            [
                'id'            => 4,
                'user_id'       => 1,
                'supplier_name' => 'Tổng kho Nguyên liệu Pha chế Toàn Cầu Nguyên An',
                'total_cost'    => 3420000.00, // Đã gom toàn bộ đá viên, topping đóng hộp, nước cốt lọt sẵn vào đây
                'created_at'    => Carbon::now()->subHours(5),
                'updated_at'    => Carbon::now()->subHours(5),
            ],
        ]);
    }
}