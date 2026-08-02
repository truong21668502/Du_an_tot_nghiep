<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('materials')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ⭐ shelf_life_after_opening_days: chỉ set cho nhóm hàng dễ hỏng sau khi MỞ NẮP
        // (sữa/kem/nước cốt/topping tươi). Nhóm khô (bột, hạt, gia vị, đá, syrup đóng chai kín)
        // để null vì không áp dụng hoặc bảo quản được rất lâu sau khi mở.
        // ⚠️ Đây là giá trị giả định theo kinh nghiệm F&B phổ biến, bạn nên rà lại theo thực tế quán.
        $shelfLifeAfterOpening = [
            3 => 20,  // Sữa đặc đã mở nắp, để tủ mát
            4 => 5,   // Sữa tươi thanh trùng
            5 => 3,   // Whipping cream đã mở hộp
            6 => 3,   // Nước cốt dừa tươi đã mở hộp
            8 => 7,   // Sữa chua hũ đã mở
            9 => 2,   // Cốt hồng trà đã ủ (dùng trong ngày/hôm sau)
            10 => 2,   // Cốt trà xanh lài đã ủ
            12 => 90,  // Syrup Đào Monin đã mở
            13 => 90,  // Syrup Vải Monin đã mở
            14 => 90,  // Syrup Dâu Tây Torani đã mở
            28 => 5,   // Nước cốt sả cây
            29 => 7,   // Nước cốt chanh tươi
            30 => 7,   // Nước cốt tắc
            33 => 5,   // Nước cốt chanh dây đã lọc
            34 => 2,   // Soda đã mở chai (mất gas nhanh)
            35 => 1,   // Lòng đỏ trứng gà tươi
        ];

        $materials = [
            // ════════════════════════════════════════════════
            // 1. NHÓM CÀ PHÊ (ID 1 - 2)
            // ════════════════════════════════════════════════
            ['id' => 1, 'material_name' => 'Cà phê phin (Bột xay)', 'base_unit' => 'g', 'quantity_in_stock' => 10000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 10000.00],
            ['id' => 2, 'material_name' => 'Espresso (Hạt cà phê xay)', 'base_unit' => 'g', 'quantity_in_stock' => 10000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 8000.00],

            // ════════════════════════════════════════════════
            // 2. NHÓM SỮA, KEM NỀN & TOPPING SỮA (ID 3 - 8)
            // ════════════════════════════════════════════════
            ['id' => 3, 'material_name' => 'Sữa đặc Larosee', 'base_unit' => 'ml', 'quantity_in_stock' => 3900.00, 'input_unit' => 'Hộp', 'exchange_rate' => 300.00, 'min_stock' => 900.00, 'max_stock' => 6000.00],
            ['id' => 4, 'material_name' => 'Sữa tươi thanh trùng Dalat Milk', 'base_unit' => 'ml', 'quantity_in_stock' => 15000.00, 'input_unit' => 'Lít', 'exchange_rate' => 1000.00, 'min_stock' => 3000.00, 'max_stock' => 20000.00],
            ['id' => 5, 'material_name' => 'Whipping cream (Kem béo tạo bọt)', 'base_unit' => 'ml', 'quantity_in_stock' => 2000.00, 'input_unit' => 'Hộp', 'exchange_rate' => 500.00, 'min_stock' => 500.00, 'max_stock' => 4000.00],
            ['id' => 6, 'material_name' => 'Nước cốt dừa tươi', 'base_unit' => 'ml', 'quantity_in_stock' => 4000.00, 'input_unit' => 'Hộp', 'exchange_rate' => 400.00, 'min_stock' => 800.00, 'max_stock' => 6000.00],
            ['id' => 7, 'material_name' => 'Bột sữa béo (Pha trà sữa)', 'base_unit' => 'g', 'quantity_in_stock' => 5000.00, 'input_unit' => 'Túi', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 8000.00],
            ['id' => 8, 'material_name' => 'Sữa chua hũ Vinamilk', 'base_unit' => 'Hũ', 'quantity_in_stock' => 40.00, 'input_unit' => 'Lốc', 'exchange_rate' => 4.00, 'min_stock' => 12.00, 'max_stock' => 80.00],

            // ════════════════════════════════════════════════
            // 3. NHÓM CỐT TRÀ NỀN (ID 9 - 10)
            // ════════════════════════════════════════════════
            ['id' => 9, 'material_name' => 'Cốt Hồng Trà Assam (Đã ủ)', 'base_unit' => 'ml', 'quantity_in_stock' => 20000.00, 'input_unit' => 'Túi', 'exchange_rate' => 20000.00, 'min_stock' => 5000.00, 'max_stock' => 40000.00],
            ['id' => 10, 'material_name' => 'Cốt Trà Xanh Lài (Đã ủ)', 'base_unit' => 'ml', 'quantity_in_stock' => 30000.00, 'input_unit' => 'Túi', 'exchange_rate' => 20000.00, 'min_stock' => 5000.00, 'max_stock' => 40000.00],

            // ════════════════════════════════════════════════
            // 4. NHÓM SYRUP & ĐƯỜNG TẠO NGỌT (ID 11 - 14)
            // ════════════════════════════════════════════════
            ['id' => 11, 'material_name' => 'Syrup đường nước tạo ngọt', 'base_unit' => 'ml', 'quantity_in_stock' => 5000.00, 'input_unit' => 'Chai', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 8000.00],
            ['id' => 12, 'material_name' => 'Syrup Đào Monin', 'base_unit' => 'ml', 'quantity_in_stock' => 1500.00, 'input_unit' => 'Chai', 'exchange_rate' => 750.00, 'min_stock' => 750.00, 'max_stock' => 3000.00],
            ['id' => 13, 'material_name' => 'Syrup Vải Monin', 'base_unit' => 'ml', 'quantity_in_stock' => 1500.00, 'input_unit' => 'Chai', 'exchange_rate' => 750.00, 'min_stock' => 750.00, 'max_stock' => 3000.00],
            ['id' => 14, 'material_name' => 'Syrup Dâu Tây Torani', 'base_unit' => 'ml', 'quantity_in_stock' => 1500.00, 'input_unit' => 'Chai', 'exchange_rate' => 750.00, 'min_stock' => 750.00, 'max_stock' => 3000.00],

            // ════════════════════════════════════════════════
            // 5. NHÓM TRÁI CÂY TƯƠI (ÉP / XAY ĐỊNH LƯỢNG) (ID 15 - 20)
            // ════════════════════════════════════════════════
            ['id' => 15, 'material_name' => 'Cam sành/Cam vàng tươi quả', 'base_unit' => 'g', 'quantity_in_stock' => 15000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 3000.00, 'max_stock' => 20000.00],
            ['id' => 16, 'material_name' => 'Dưa hấu đỏ tươi', 'base_unit' => 'g', 'quantity_in_stock' => 10000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 2000.00, 'max_stock' => 15000.00],
            ['id' => 17, 'material_name' => 'Thơm (Dứa) quả tươi', 'base_unit' => 'g', 'quantity_in_stock' => 5000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 8000.00],
            ['id' => 18, 'material_name' => 'Ổi hồng tươi', 'base_unit' => 'g', 'quantity_in_stock' => 5000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 8000.00],
            ['id' => 19, 'material_name' => 'Bơ sáp chín cây', 'base_unit' => 'g', 'quantity_in_stock' => 4000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 6000.00],
            ['id' => 20, 'material_name' => 'Xoài Cát chín', 'base_unit' => 'g', 'quantity_in_stock' => 4000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 6000.00],

            // ════════════════════════════════════════════════
            // 6. NHÓM TOPPING & TRÁI CÂY ĐÓNG HỘP (ID 21 - 26)
            // ════════════════════════════════════════════════
            ['id' => 21, 'material_name' => 'Cam vàng cắt lát decor', 'base_unit' => 'Lát', 'quantity_in_stock' => 60.00, 'input_unit' => 'Kg', 'exchange_rate' => 30.00, 'min_stock' => 15.00, 'max_stock' => 100.00],
            ['id' => 22, 'material_name' => 'Đào ngâm đóng hộp (Kronos)', 'base_unit' => 'Miếng', 'quantity_in_stock' => 40.00, 'input_unit' => 'Hũ', 'exchange_rate' => 8.00, 'min_stock' => 16.00, 'max_stock' => 80.00],
            ['id' => 23, 'material_name' => 'Vải ngâm đóng hộp', 'base_unit' => 'Quả', 'quantity_in_stock' => 60.00, 'input_unit' => 'Hũ', 'exchange_rate' => 20.00, 'min_stock' => 20.00, 'max_stock' => 100.00],
            ['id' => 24, 'material_name' => 'Xí muội mặn ngọt', 'base_unit' => 'Quả', 'quantity_in_stock' => 100.00, 'input_unit' => 'Gói', 'exchange_rate' => 50.00, 'min_stock' => 50.00, 'max_stock' => 200.00],
            ['id' => 25, 'material_name' => 'Trân châu đen thành phẩm', 'base_unit' => 'g', 'quantity_in_stock' => 5000.00, 'input_unit' => 'Túi', 'exchange_rate' => 1000.00, 'min_stock' => 1000.00, 'max_stock' => 8000.00],
            ['id' => 26, 'material_name' => 'Hạt chia ngâm nở', 'base_unit' => 'g', 'quantity_in_stock' => 1000.00, 'input_unit' => 'Túi', 'exchange_rate' => 500.00, 'min_stock' => 300.00, 'max_stock' => 2000.00],

            // ════════════════════════════════════════════════
            // 7. CỐT NƯỚC ÉP LỌT SẴN, BỘT ĐỒ UỐNG & KHÁC (ID 27 - 36)
            // ════════════════════════════════════════════════
            ['id' => 27, 'material_name' => 'Đá viên giòn tan', 'base_unit' => 'g', 'quantity_in_stock' => 50000.00, 'input_unit' => 'Kg', 'exchange_rate' => 1000.00, 'min_stock' => 10000.00, 'max_stock' => 80000.00],
            ['id' => 28, 'material_name' => 'Nước cốt sả cây', 'base_unit' => 'ml', 'quantity_in_stock' => 2000.00, 'input_unit' => 'Lít', 'exchange_rate' => 1000.00, 'min_stock' => 500.00, 'max_stock' => 4000.00],
            ['id' => 29, 'material_name' => 'Nước cốt chanh tươi sành', 'base_unit' => 'ml', 'quantity_in_stock' => 2000.00, 'input_unit' => 'Chai', 'exchange_rate' => 500.00, 'min_stock' => 500.00, 'max_stock' => 4000.00],
            ['id' => 30, 'material_name' => 'Nước cốt tắc nguyên chất', 'base_unit' => 'ml', 'quantity_in_stock' => 2000.00, 'input_unit' => 'Chai', 'exchange_rate' => 500.00, 'min_stock' => 500.00, 'max_stock' => 4000.00],
            ['id' => 31, 'material_name' => 'Bột Matcha Nhật Bản Uji', 'base_unit' => 'g', 'quantity_in_stock' => 1000.00, 'input_unit' => 'Túi', 'exchange_rate' => 500.00, 'min_stock' => 300.00, 'max_stock' => 2000.00],
            ['id' => 32, 'material_name' => 'Bột Cacao nguyên chất Choco', 'base_unit' => 'g', 'quantity_in_stock' => 1000.00, 'input_unit' => 'Hộp', 'exchange_rate' => 500.00, 'min_stock' => 300.00, 'max_stock' => 2000.00],
            ['id' => 33, 'material_name' => 'Nước cốt chanh dây (Đã lọc hạt)', 'base_unit' => 'ml', 'quantity_in_stock' => 2000.00, 'input_unit' => 'Chai', 'exchange_rate' => 500.00, 'min_stock' => 500.00, 'max_stock' => 4000.00],
            ['id' => 34, 'material_name' => 'Nước Soda đóng chai (Schweppes)', 'base_unit' => 'ml', 'quantity_in_stock' => 6400.00, 'input_unit' => 'Chai', 'exchange_rate' => 320.00, 'min_stock' => 1600.00, 'max_stock' => 10000.00],
            ['id' => 35, 'material_name' => 'Lòng đỏ trứng gà tươi', 'base_unit' => 'Cái', 'quantity_in_stock' => 50.00, 'input_unit' => 'Vỉ', 'exchange_rate' => 10.00, 'min_stock' => 20.00, 'max_stock' => 100.00],
            ['id' => 36, 'material_name' => 'Muối tinh Hồng Himalaya', 'base_unit' => 'g', 'quantity_in_stock' => 500.00, 'input_unit' => 'Gói', 'exchange_rate' => 500.00, 'min_stock' => 200.00, 'max_stock' => 1500.00],
        ];

        foreach ($materials as $material) {
            DB::table('materials')->insert([
                'id' => $material['id'],
                'material_name' => $material['material_name'],
                'base_unit' => $material['base_unit'],
                'quantity_in_stock' => $material['quantity_in_stock'],
                'input_unit' => $material['input_unit'],
                'exchange_rate' => $material['exchange_rate'],
                'min_stock' => $material['min_stock'],
                'max_stock' => $material['max_stock'],
                'shelf_life_after_opening_days' => $shelfLifeAfterOpening[$material['id']] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}