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

        // ⭐ hạn sử dụng KHI CHƯA MỞ, tính theo số ngày kể từ ngày nhập (created_at của phiếu).
        // Chỉ set cho nhóm hàng có FIFO/FEFO thực sự ý nghĩa (tươi sống, dễ hỏng theo thời gian).
        // Nhóm khô/đóng hộp/topping bền (đá, bột, syrup, trân châu, xí muội...) để null vì
        // hạn dùng dài không phải yếu tố quyết định khi xuất kho.
        // ⚠️ Số ngày là giả định hợp lý theo thực tế F&B, bạn rà lại theo hạn in trên bao bì thật.
        $expiryDaysFromImport = [
            3 => 180, // Sữa đặc (lon kín)
            4 => 10,  // Sữa tươi thanh trùng
            5 => 60,  // Whipping cream (hộp tiệt trùng)
            6 => 180, // Nước cốt dừa đóng hộp
            8 => 20,  // Sữa chua hũ
            15 => 14,  // Cam tươi
            16 => 10,  // Dưa hấu tươi
            17 => 7,   // Thơm tươi
            18 => 7,   // Ổi tươi
            19 => 5,   // Bơ sáp chín cây
            20 => 7,   // Xoài chín
            35 => 20,  // Trứng gà tươi
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
                    // ⭐ Quy đổi tự động sang base_unit dựa vào exchange_rate thật của material
                    'stock_change' => $stockChange,
                    'remaining_quantity' => $stockChange, // Lô mới nhập -> chưa bị trừ, còn nguyên
                    'expiry_date' => $expiryDate,
                    'opened_at' => null, // Lô chưa mở, chỉ set khi nhân viên bắt đầu dùng
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        DB::table('import_receipt_details')->insert($insertData);
    }
}