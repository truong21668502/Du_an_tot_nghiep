<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use Carbon\Carbon;

class ImportReceiptDetailSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('import_receipt_details')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Mảng dữ liệu gốc giữ nguyên như bạn đã viết (quantity theo input_unit)
        $rows = [
            ['receipt_id' => 1, 'material_id' => 3, 'quantity' => 13.00, 'unit_price' => 30000.00, 'created_at' => Carbon::now()->subDays(10)],
            ['receipt_id' => 1, 'material_id' => 4, 'quantity' => 15.00, 'unit_price' => 42000.00, 'created_at' => Carbon::now()->subDays(10)],
            ['receipt_id' => 1, 'material_id' => 5, 'quantity' => 4.00, 'unit_price' => 65000.00, 'created_at' => Carbon::now()->subDays(10)],
            ['receipt_id' => 1, 'material_id' => 8, 'quantity' => 10.00, 'unit_price' => 33000.00, 'created_at' => Carbon::now()->subDays(10)],

            ['receipt_id' => 2, 'material_id' => 1, 'quantity' => 10.00, 'unit_price' => 140000.00, 'created_at' => Carbon::now()->subDays(7)],
            ['receipt_id' => 2, 'material_id' => 2, 'quantity' => 10.00, 'unit_price' => 180000.00, 'created_at' => Carbon::now()->subDays(7)],
            ['receipt_id' => 2, 'material_id' => 36, 'quantity' => 1.00, 'unit_price' => 15000.00, 'created_at' => Carbon::now()->subDays(7)],

            ['receipt_id' => 3, 'material_id' => 15, 'quantity' => 15.00, 'unit_price' => 25000.00, 'created_at' => Carbon::now()->subDays(3)],
            ['receipt_id' => 3, 'material_id' => 16, 'quantity' => 10.00, 'unit_price' => 15000.00, 'created_at' => Carbon::now()->subDays(3)],
            ['receipt_id' => 3, 'material_id' => 17, 'quantity' => 5.00, 'unit_price' => 20000.00, 'created_at' => Carbon::now()->subDays(3)],
            ['receipt_id' => 3, 'material_id' => 18, 'quantity' => 5.00, 'unit_price' => 22000.00, 'created_at' => Carbon::now()->subDays(3)],
            ['receipt_id' => 3, 'material_id' => 19, 'quantity' => 4.00, 'unit_price' => 45000.00, 'created_at' => Carbon::now()->subDays(3)],
            ['receipt_id' => 3, 'material_id' => 20, 'quantity' => 4.00, 'unit_price' => 40000.00, 'created_at' => Carbon::now()->subDays(3)],
            ['receipt_id' => 3, 'material_id' => 35, 'quantity' => 5.00, 'unit_price' => 25000.00, 'created_at' => Carbon::now()->subDays(3)],

            ['receipt_id' => 4, 'material_id' => 6, 'quantity' => 10.00, 'unit_price' => 28000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 7, 'quantity' => 5.00, 'unit_price' => 70000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 9, 'quantity' => 1.00, 'unit_price' => 150000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 10, 'quantity' => 1.50, 'unit_price' => 160000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 11, 'quantity' => 5.00, 'unit_price' => 45000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 12, 'quantity' => 2.00, 'unit_price' => 210000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 13, 'quantity' => 2.00, 'unit_price' => 210000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 14, 'quantity' => 2.00, 'unit_price' => 175000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 21, 'quantity' => 2.00, 'unit_price' => 35000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 22, 'quantity' => 5.00, 'unit_price' => 55000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 23, 'quantity' => 3.00, 'unit_price' => 60000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 24, 'quantity' => 2.00, 'unit_price' => 40000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 25, 'quantity' => 5.00, 'unit_price' => 38000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 26, 'quantity' => 2.00, 'unit_price' => 80000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 27, 'quantity' => 50.00, 'unit_price' => 4000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 28, 'quantity' => 2.00, 'unit_price' => 25000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 29, 'quantity' => 4.00, 'unit_price' => 20000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 30, 'quantity' => 4.00, 'unit_price' => 22000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 31, 'quantity' => 2.00, 'unit_price' => 220000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 32, 'quantity' => 2.00, 'unit_price' => 115000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 33, 'quantity' => 4.00, 'unit_price' => 35000.00, 'created_at' => Carbon::now()->subHours(5)],
            ['receipt_id' => 4, 'material_id' => 34, 'quantity' => 20.00, 'unit_price' => 7000.00, 'created_at' => Carbon::now()->subHours(5)],
        ];

        // Lấy sẵn exchange_rate của tất cả material liên quan, tránh query lặp trong vòng lặp
        $materialIds = collect($rows)->pluck('material_id')->unique();
        $exchangeRates = Material::whereIn('id', $materialIds)->pluck('exchange_rate', 'id');

        $now = Carbon::now();
        $insertData = collect($rows)->map(function ($row) use ($exchangeRates, $now) {
            $exchangeRate = $exchangeRates[$row['material_id']] ?? 1;
            $stockChange = round($row['quantity'] * $exchangeRate, 2);


            return [
                'receipt_id' => $row['receipt_id'],
                'material_id' => $row['material_id'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                // ⭐ Quy đổi tự động sang base_unit dựa vào exchange_rate thật của material
                'stock_change' => round($row['quantity'] * $exchangeRate, 2),
                'remaining_quantity' => $stockChange,
                'created_at' => $row['created_at'],
                'updated_at' => $row['created_at'],
            ];
        })->toArray();

        DB::table('import_receipt_details')->insert($insertData);
    }
}