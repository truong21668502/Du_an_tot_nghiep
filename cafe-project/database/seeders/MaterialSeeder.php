<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        // Tắt kiểm tra khóa ngoại để xóa sạch dữ liệu cũ mà không bị lỗi ràng buộc
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('materials')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $materials = [
            // ════════════════════════════════════════════════
            // 1. NHÓM CÀ PHÊ (ID 1 - 2)
            // ════════════════════════════════════════════════
            [
                'id' => 1,
                'material_name' => 'Cà phê phin (Bột xay)',
                'base_unit' => 'g',
                'quantity_in_stock' => 5000.00, // 5Kg bột cà phê xay
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00, // 1 Kg = 1000g
            ],
            [
                'id' => 2,
                'material_name' => 'Espresso (Hạt cà phê xay)',
                'base_unit' => 'g',
                'quantity_in_stock' => 4000.00, // 4Kg hạt cà phê xay cho máy espresso
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00, // 1 Kg = 1000g
            ],

            // ════════════════════════════════════════════════
            // 2. NHÓM SỮA, KEM NỀN & TOPPING SỮA (ID 3 - 8)
            // ════════════════════════════════════════════════
            [
                'id' => 3,
                'material_name' => 'Sữa đặc Larosee',
                'base_unit' => 'ml',
                'quantity_in_stock' => 3900.00,
                'input_unit' => 'Hộp',
                'exchange_rate' => 300.00,  // 1 Hộp sữa đặc quy đổi ra ~300ml
            ],
            [
                'id' => 4,
                'material_name' => 'Sữa tươi thanh trùng Dalat Milk',
                'base_unit' => 'ml',
                'quantity_in_stock' => 15000.00, // 15 Lít sữa tươi
                'input_unit' => 'Lít',
                'exchange_rate' => 1000.00, // 1 Lít = 1000ml
            ],
            [
                'id' => 5,
                'material_name' => 'Whipping cream (Kem béo tạo bọt)',
                'base_unit' => 'ml',
                'quantity_in_stock' => 2000.00,
                'input_unit' => 'Hộp',
                'exchange_rate' => 500.00,  // 1 Hộp = 500ml
            ],
            [
                'id' => 6,
                'material_name' => 'Nước cốt dừa tươi',
                'base_unit' => 'ml',
                'quantity_in_stock' => 4000.00,
                'input_unit' => 'Hộp',
                'exchange_rate' => 400.00,  // 1 Hộp giấy = 400ml
            ],
            [
                'id' => 7,
                'material_name' => 'Bột sữa béo (Pha trà sữa)',
                'base_unit' => 'g',
                'quantity_in_stock' => 5000.00,  // 5Kg bột sữa
                'input_unit' => 'Túi',
                'exchange_rate' => 1000.00, // 1 Túi = 1000g
            ],
            [
                'id' => 8,
                'material_name' => 'Sữa chua hũ Vinamilk',
                'base_unit' => 'Hũ',
                'quantity_in_stock' => 40.00,    // Có sẵn 40 hũ sữa chua
                'input_unit' => 'Lốc',
                'exchange_rate' => 4.00,     // 1 Lốc = 4 Hũ
            ],

            // ════════════════════════════════════════════════
            // 3. NHÓM CỐT TRÀ NỀN (ID 9 - 10)
            // ════════════════════════════════════════════════
            [
                'id' => 9,
                'material_name' => 'Cốt Hồng Trà Assam (Đã ủ)',
                'base_unit' => 'ml',
                'quantity_in_stock' => 20000.00, // 20 Lít cốt hồng trà
                'input_unit' => 'Túi',
                'exchange_rate' => 20000.00,
            ],
            [
                'id' => 10,
                'material_name' => 'Cốt Trà Xanh Lài (Đã ủ)',
                'base_unit' => 'ml',
                'quantity_in_stock' => 30000.00, // 30 Lít cốt trà lài
                'input_unit' => 'Túi',
                'exchange_rate' => 20000.00,
            ],

            // ════════════════════════════════════════════════
            // 4. NHÓM SYRUP & ĐƯỜNG TẠO NGỌT (ID 11 - 14)
            // ════════════════════════════════════════════════
            [
                'id' => 11,
                'material_name' => 'Syrup đường nước tạo ngọt',
                'base_unit' => 'ml',
                'quantity_in_stock' => 5000.00,
                'input_unit' => 'Chai',
                'exchange_rate' => 1000.00, // 1 Chai lớn = 1000ml
            ],
            [
                'id' => 12,
                'material_name' => 'Syrup Đào Monin',
                'base_unit' => 'ml',
                'quantity_in_stock' => 1500.00,
                'input_unit' => 'Chai',
                'exchange_rate' => 750.00,  // 1 Chai = 750ml
            ],
            [
                'id' => 13,
                'material_name' => 'Syrup Vải Monin',
                'base_unit' => 'ml',
                'quantity_in_stock' => 1500.00,
                'input_unit' => 'Chai',
                'exchange_rate' => 750.00,
            ],
            [
                'id' => 14,
                'material_name' => 'Syrup Dâu Tây Torani',
                'base_unit' => 'ml',
                'quantity_in_stock' => 1500.00,
                'input_unit' => 'Chai',
                'exchange_rate' => 750.00,
            ],

            // ════════════════════════════════════════════════
            // 5. NHÓM TRÁI CÂY TƯƠI (ÉP / XAY ĐỊNH LƯỢNG) (ID 15 - 20)
            // ════════════════════════════════════════════════
            [
                'id' => 15,
                'material_name' => 'Cam sành/Cam vàng tươi quả',
                'base_unit' => 'g',
                'quantity_in_stock' => 15000.00, // 15Kg cam thô để vắt nước ép
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 16,
                'material_name' => 'Dưa hấu đỏ tươi',
                'base_unit' => 'g',
                'quantity_in_stock' => 10000.00, // 10Kg dưa hấu thô
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 17,
                'material_name' => 'Thơm (Dứa) quả tươi',
                'base_unit' => 'g',
                'quantity_in_stock' => 5000.00,  // 5Kg thơm thô đã gọt vỏ
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 18,
                'material_name' => 'Ổi hồng tươi',
                'base_unit' => 'g',
                'quantity_in_stock' => 5000.00,
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 19,
                'material_name' => 'Bơ sáp chín cây',
                'base_unit' => 'g',
                'quantity_in_stock' => 4000.00,  // 4Kg bơ thô
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 20,
                'material_name' => 'Xoài Cát chín',
                'base_unit' => 'g',
                'quantity_in_stock' => 4000.00,
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00,
            ],

            // ════════════════════════════════════════════════
            // 6. NHÓM TOPPING & TRÁI CÂY ĐÓNG HỘP (ID 21 - 26)
            // ════════════════════════════════════════════════
            [
                'id' => 21,
                'material_name' => 'Cam vàng cắt lát decor',
                'base_unit' => 'Lát',
                'quantity_in_stock' => 60.00,    // Sẵn 60 lát cam vàng decor
                'input_unit' => 'Kg',
                'exchange_rate' => 30.00,    // 1 Kg cam cắt được tầm 30 lát
            ],
            [
                'id' => 22,
                'material_name' => 'Đào ngâm đóng hộp (Kronos)',
                'base_unit' => 'Miếng',
                'quantity_in_stock' => 40.00,    // Sẵn 40 miếng đào lớn
                'input_unit' => 'Hũ',
                'exchange_rate' => 8.00,     // 1 Hũ sắt = 8 miếng đào
            ],
            [
                'id' => 23,
                'material_name' => 'Vải ngâm đóng hộp',
                'base_unit' => 'Quả',
                'quantity_in_stock' => 60.00,    // Sẵn 60 quả vải ngâm
                'input_unit' => 'Hũ',
                'exchange_rate' => 20.00,    // 1 Hũ = 20 quả vải
            ],
            [
                'id' => 24,
                'material_name' => 'Xí muội mặn ngọt',
                'base_unit' => 'Quả',
                'quantity_in_stock' => 100.00,
                'input_unit' => 'Gói',
                'exchange_rate' => 50.00,    // 1 Gói = 50 quả xí muội
            ],
            [
                'id' => 25,
                'material_name' => 'Trân châu đen thành phẩm',
                'base_unit' => 'g',
                'quantity_in_stock' => 5000.00,  // Sẵn 5Kg trân châu chín
                'input_unit' => 'Túi',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 26,
                'material_name' => 'Hạt chia ngâm nở',
                'base_unit' => 'g',
                'quantity_in_stock' => 1000.00,
                'input_unit' => 'Túi',
                'exchange_rate' => 500.00,
            ],

            // ════════════════════════════════════════════════
            // 7. CỐT NƯỚC ÉP LỌT SẴN, BỘT ĐỒ UỐNG & KHÁC (ID 27 - 35)
            // ════════════════════════════════════════════════
            [
                'id' => 27,
                'material_name' => 'Đá viên giòn tan',
                'base_unit' => 'g',
                'quantity_in_stock' => 50000.00, // 50Kg đá viên
                'input_unit' => 'Kg',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 28,
                'material_name' => 'Nước cốt sả cây',
                'base_unit' => 'ml',
                'quantity_in_stock' => 2000.00,
                'input_unit' => 'Lít',
                'exchange_rate' => 1000.00,
            ],
            [
                'id' => 29,
                'material_name' => 'Nước cốt chanh tươi sành',
                'base_unit' => 'ml',
                'quantity_in_stock' => 2000.00,
                'input_unit' => 'Chai',
                'exchange_rate' => 500.00,
            ],
            [
                'id' => 30,
                'material_name' => 'Nước cốt tắc nguyên chất',
                'base_unit' => 'ml',
                'quantity_in_stock' => 2000.00,
                'input_unit' => 'Chai',
                'exchange_rate' => 500.00,
            ],
            [
                'id' => 31,
                'material_name' => 'Bột Matcha Nhật Bản Uji',
                'base_unit' => 'g',
                'quantity_in_stock' => 1000.00,
                'input_unit' => 'Túi',
                'exchange_rate' => 500.00,
            ],
            [
                'id' => 32,
                'material_name' => 'Bột Cacao nguyên chất Choco',
                'base_unit' => 'g',
                'quantity_in_stock' => 1000.00,
                'input_unit' => 'Hộp',
                'exchange_rate' => 500.00,
            ],
            [
                'id' => 33,
                'material_name' => 'Nước cốt chanh dây (Đã lọc hạt)',
                'base_unit' => 'ml',
                'quantity_in_stock' => 2000.00,
                'input_unit' => 'Chai',
                'exchange_rate' => 500.00,
            ],
            [
                'id' => 34,
                'material_name' => 'Nước Soda đóng chai (Schweppes)',
                'base_unit' => 'ml',
                'quantity_in_stock' => 6400.00, // ~20 chai soda
                'input_unit' => 'Chai',
                'exchange_rate' => 320.00,  // 1 Chai lẻ = 320ml
            ],
            [
                'id' => 35,
                'material_name' => 'Lòng đỏ trứng gà tươi',
                'base_unit' => 'Cái',
                'quantity_in_stock' => 50.00,    // Dùng cho cà phê kem trứng
                'input_unit' => 'Vỉ',
                'exchange_rate' => 10.00,
            ],
            [
                'id' => 36,
                'material_name' => 'Muối tinh Hồng Himalaya',
                'base_unit' => 'g',
                'quantity_in_stock' => 500.00,   // Dùng cho món Cà Phê Muối
                'input_unit' => 'Gói',
                'exchange_rate' => 500.00,
            ],
        ];

        // Lặp qua mảng và thực hiện chèn dữ liệu chuẩn hóa vào DB
        foreach ($materials as $material) {
            DB::table('materials')->insert([
                'id' => $material['id'],
                'material_name' => $material['material_name'],
                'base_unit' => $material['base_unit'],
                'quantity_in_stock' => $material['quantity_in_stock'],
                'input_unit' => $material['input_unit'],
                'exchange_rate' => $material['exchange_rate'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}