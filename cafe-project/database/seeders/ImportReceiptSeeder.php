<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportReceiptSeeder extends Seeder
{
    /**
     * ⭐ Public static để ImportReceiptDetailSeeder dùng lại CHÍNH XÁC cùng một nguồn dữ liệu.
     * Tránh khai báo trùng 2 nơi (nguyên nhân gây lệch total_cost ở bản trước).
     */
    public static function data(): array
    {
        return [
            1 => [
                'user_id' => 1,
                'supplier_name' => 'Công ty Đại lý Sữa Vinamilk & Dalat Milk',
                'note' => 'Nhập định kỳ nhóm sữa - kem nền hàng tuần',
                'status' => 'active',
                'created_at' => Carbon::now()->subDays(10),
                'items' => [
                    ['material_id' => 3, 'quantity' => 13.00, 'unit_price' => 30000.00],
                    ['material_id' => 4, 'quantity' => 15.00, 'unit_price' => 42000.00],
                    ['material_id' => 5, 'quantity' => 4.00, 'unit_price' => 65000.00],
                    ['material_id' => 8, 'quantity' => 10.00, 'unit_price' => 33000.00],
                ],
            ],
            2 => [
                'user_id' => 1,
                'supplier_name' => 'Nhà cung cấp Cà phê hạt Robusta & Espresso Tây Nguyên',
                'note' => null,
                'status' => 'active',
                'created_at' => Carbon::now()->subDays(7),
                'items' => [
                    ['material_id' => 1, 'quantity' => 10.00, 'unit_price' => 140000.00],
                    ['material_id' => 2, 'quantity' => 10.00, 'unit_price' => 180000.00],
                    ['material_id' => 36, 'quantity' => 1.00, 'unit_price' => 15000.00],
                ],
            ],
            3 => [
                'user_id' => 1,
                'supplier_name' => 'Chợ Đầu Mối Trái Cây & Nông Sản Đà Nẵng',
                'note' => 'Hàng tươi, cần xuất trước theo FEFO',
                'status' => 'active',
                'created_at' => Carbon::now()->subDays(3),
                'items' => [
                    ['material_id' => 15, 'quantity' => 15.00, 'unit_price' => 25000.00],
                    ['material_id' => 16, 'quantity' => 10.00, 'unit_price' => 15000.00],
                    ['material_id' => 17, 'quantity' => 5.00, 'unit_price' => 20000.00],
                    ['material_id' => 18, 'quantity' => 5.00, 'unit_price' => 22000.00],
                    ['material_id' => 19, 'quantity' => 4.00, 'unit_price' => 45000.00],
                    ['material_id' => 20, 'quantity' => 4.00, 'unit_price' => 40000.00],
                    ['material_id' => 35, 'quantity' => 5.00, 'unit_price' => 25000.00],
                ],
            ],
            4 => [
                'user_id' => 1,
                'supplier_name' => 'Tổng kho Nguyên liệu Pha chế Toàn Cầu Nguyên An',
                'note' => 'Nhập gộp topping, syrup, đá viên và nước cốt lọt sẵn',
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(5),
                'items' => [
                    ['material_id' => 6, 'quantity' => 10.00, 'unit_price' => 28000.00],
                    ['material_id' => 7, 'quantity' => 5.00, 'unit_price' => 70000.00],
                    ['material_id' => 9, 'quantity' => 1.00, 'unit_price' => 150000.00],
                    ['material_id' => 10, 'quantity' => 1.50, 'unit_price' => 160000.00],
                    ['material_id' => 11, 'quantity' => 5.00, 'unit_price' => 45000.00],
                    ['material_id' => 12, 'quantity' => 2.00, 'unit_price' => 210000.00],
                    ['material_id' => 13, 'quantity' => 2.00, 'unit_price' => 210000.00],
                    ['material_id' => 14, 'quantity' => 2.00, 'unit_price' => 175000.00],
                    ['material_id' => 21, 'quantity' => 2.00, 'unit_price' => 35000.00],
                    ['material_id' => 22, 'quantity' => 5.00, 'unit_price' => 55000.00],
                    ['material_id' => 23, 'quantity' => 3.00, 'unit_price' => 60000.00],
                    ['material_id' => 24, 'quantity' => 2.00, 'unit_price' => 40000.00],
                    ['material_id' => 25, 'quantity' => 5.00, 'unit_price' => 38000.00],
                    ['material_id' => 26, 'quantity' => 2.00, 'unit_price' => 80000.00],
                    ['material_id' => 27, 'quantity' => 50.00, 'unit_price' => 4000.00],
                    ['material_id' => 28, 'quantity' => 2.00, 'unit_price' => 25000.00],
                    ['material_id' => 29, 'quantity' => 4.00, 'unit_price' => 20000.00],
                    ['material_id' => 30, 'quantity' => 4.00, 'unit_price' => 22000.00],
                    ['material_id' => 31, 'quantity' => 2.00, 'unit_price' => 220000.00],
                    ['material_id' => 32, 'quantity' => 2.00, 'unit_price' => 115000.00],
                    ['material_id' => 33, 'quantity' => 4.00, 'unit_price' => 35000.00],
                    ['material_id' => 34, 'quantity' => 20.00, 'unit_price' => 7000.00],
                ],
            ],
        ];
    }

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('import_receipts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $insertData = [];
        foreach (self::data() as $id => $receipt) {
            $totalCost = collect($receipt['items'])
                ->sum(fn($item) => $item['quantity'] * $item['unit_price']);

            $insertData[] = [
                'id' => $id,
                'user_id' => $receipt['user_id'],
                'supplier_name' => $receipt['supplier_name'],
                'total_cost' => round($totalCost, 2),
                'note' => $receipt['note'],
                'status' => $receipt['status'],
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancel_reason' => null,
                'created_at' => $receipt['created_at'],
                'updated_at' => $receipt['created_at'],
            ];
        }

        DB::table('import_receipts')->insert($insertData);
    }
}