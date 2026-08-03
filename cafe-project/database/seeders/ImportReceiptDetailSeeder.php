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

        // ⭐ Số ngày hạn sử dụng được TĂNG so với thực tế F&P để đảm bảo mọi expiry_date
        // rơi vào tháng 9/2026 trở đi (phục vụ test cảnh báo sắp hết hạn / FEFO mà không
        // bị "hết hạn ngay khi seed"). Thứ tự tương đối giữa các nguyên liệu vẫn giữ nguyên
        // (cái nào lẽ ra hư nhanh hơn thì vẫn hết hạn sớm hơn trong nhóm tháng 9).
        // ⚠️ Đây là giá trị test/demo, KHÔNG phản ánh hạn dùng thật ngoài bao bì.
        $expiryDaysFromImport = [
            3 => 180, // Sữa đặc (lon kín)               -> ~Jan 2027 (không đổi)
            4 => 39,  // Sữa tươi thanh trùng              -> ~01/09
            5 => 60,  // Whipping cream                    -> ~22/09 (không đổi)
            6 => 180, // Nước cốt dừa đóng hộp              -> ~Jan 2027 (không đổi)
            8 => 43,  // Sữa chua hũ                        -> ~05/09
            15 => 41,  // Cam tươi                           -> ~10/09
            16 => 37,  // Dưa hấu tươi                       -> ~06/09
            17 => 34,  // Thơm tươi                          -> ~03/09
            18 => 33,  // Ổi tươi                            -> ~02/09
            19 => 32,  // Bơ sáp chín cây                    -> ~01/09
            20 => 35,  // Xoài chín                          -> ~04/09
            35 => 29,  // Trứng gà tươi                      -> ~01/09
        ];

        // ⭐ Lấy dữ liệu TỪ CHÍNH ImportReceiptSeeder — nguồn duy nhất, không khai báo trùng
        // để đảm bảo total_cost (đã tính ở receipt) luôn khớp 100% với tổng chi tiết ở đây.
        $receiptsData = ImportReceiptSeeder::data();

        $materialIds = collect($receiptsData)
            ->flatMap(fn($r) => $r['items'])
            ->pluck('material_id')
            ->unique();
        $exchangeRates = Material::whereIn('id', $materialIds)->pluck('exchange_rate', 'id');

        $insertData = [];
        foreach ($receiptsData as $receiptId => $receipt) {
            $createdAt = $receipt['created_at'];

            foreach ($receipt['items'] as $item) {
                $exchangeRate = $exchangeRates[$item['material_id']] ?? 1;
                $stockChange = round($item['quantity'] * $exchangeRate, 2);

                $days = $expiryDaysFromImport[$item['material_id']] ?? null;
                $expiryDate = $days !== null
                    ? Carbon::parse($createdAt)->copy()->addDays($days)->toDateString()
                    : null;

                $insertData[] = [
                    'receipt_id' => $receiptId,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'stock_change' => $stockChange,
                    'remaining_quantity' => $stockChange,
                    'expiry_date' => $expiryDate,
                    'opened_at' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        DB::table('import_receipt_details')->insert($insertData);
    }
}