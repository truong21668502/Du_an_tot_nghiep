<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        // Tắt ràng buộc khóa ngoại để tiến hành xóa sạch dữ liệu cũ
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('recipes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Định nghĩa lượng tiêu hao nguyên liệu chuẩn cho Size M (Đã đồng bộ ID Material mới từ 1 - 36)
        $recipesTemplate = [
            // --- Nhóm Cà Phê ---
            'black_coffee' => [
                ['material_id' => 1, 'quantity' => 50],   // 50ml Cà phê phin
                ['material_id' => 11, 'quantity' => 25],  // 25ml Syrup đường
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'milk_coffee' => [
                ['material_id' => 1, 'quantity' => 40],   // 40ml Cà phê phin
                ['material_id' => 3, 'quantity' => 30],   // 30ml Sữa đặc
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'bac_xiu' => [
                ['material_id' => 1, 'quantity' => 15],   // 15ml Cà phê phin (nhấp môi tạo màu)
                ['material_id' => 3, 'quantity' => 40],   // 40ml Sữa đặc
                ['material_id' => 4, 'quantity' => 100],  // 100ml Sữa tươi
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'salt_coffee' => [
                ['material_id' => 1, 'quantity' => 40],   // 40ml Cà phê phin
                ['material_id' => 3, 'quantity' => 25],   // 25ml Sữa đặc
                ['material_id' => 5, 'quantity' => 40],   // 40ml Whipping cream tạo bọt kem mặn
                ['material_id' => 36, 'quantity' => 2],   // 2g Muối tinh hồng Himalaya
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'coconut_coffee' => [
                ['material_id' => 1, 'quantity' => 30],   // 30ml Cà phê phin
                ['material_id' => 3, 'quantity' => 30],   // 30ml Sữa đặc
                ['material_id' => 6, 'quantity' => 60],   // 60ml Nước cốt dừa tươi
                ['material_id' => 27, 'quantity' => 180], // 180g Đá viên để xay
            ],
            'milk_fresh_coffee' => [
                ['material_id' => 1, 'quantity' => 30],   // 30ml Cà phê phin
                ['material_id' => 4, 'quantity' => 100],  // 100ml Sữa tươi
                ['material_id' => 3, 'quantity' => 20],   // 20ml Sữa đặc
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'americano' => [
                ['material_id' => 2, 'quantity' => 40],   // 40ml Cốt Espresso (2 shot)
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
                // Nước lọc tinh khiết không cần trừ kho nguyên liệu chính
            ],
            'latte_cappuccino' => [
                ['material_id' => 2, 'quantity' => 30],   // 30ml Cốt Espresso
                ['material_id' => 4, 'quantity' => 150],  // 150ml Sữa tươi đánh bọt
                ['material_id' => 11, 'quantity' => 10],  // 10ml Syrup đường kích vị
            ],
            'egg_coffee' => [
                ['material_id' => 1, 'quantity' => 50],   // 50ml Cà phê phin bốc khói
                ['material_id' => 3, 'quantity' => 20],   // 20ml Sữa đặc
                ['material_id' => 35, 'quantity' => 2],   // 2 Cái Lòng đỏ trứng gà tươi
                ['material_id' => 5, 'quantity' => 15],   // 15ml Whipping cream đánh bông kem trứng
            ],

            // --- Nhóm Trà & Trà Trái Cây ---
            'peach_tea' => [
                ['material_id' => 10, 'quantity' => 120], // 120ml Cốt Trà Xanh Lài
                ['material_id' => 11, 'quantity' => 20],  // 20ml Syrup đường
                ['material_id' => 12, 'quantity' => 15],  // 15ml Syrup đào Monin
                ['material_id' => 28, 'quantity' => 20],  // 20ml Nước cốt sả cây
                ['material_id' => 21, 'quantity' => 1],   // 1 Lát Cam vàng decor
                ['material_id' => 22, 'quantity' => 2],   // 2 Miếng Đào ngâm Topping
                ['material_id' => 27, 'quantity' => 180], // 180g Đá viên
            ],
            'lychee_tea' => [
                ['material_id' => 10, 'quantity' => 120], // 120ml Cốt Trà Xanh Lài
                ['material_id' => 11, 'quantity' => 20],  // 20ml Syrup đường
                ['material_id' => 13, 'quantity' => 15],  // 15ml Syrup vải Monin
                ['material_id' => 23, 'quantity' => 3],   // 3 Quả vải ngâm Topping
                ['material_id' => 27, 'quantity' => 180], // 180g Đá viên
            ],
            'lemon_tea' => [
                ['material_id' => 10, 'quantity' => 150], // 150ml Cốt trà lài
                ['material_id' => 11, 'quantity' => 30],  // 30ml Syrup đường
                ['material_id' => 29, 'quantity' => 15],  // 15ml Cốt chanh tươi sành
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'kumquat_tea' => [
                ['material_id' => 10, 'quantity' => 150], // 150ml Cốt trà lài
                ['material_id' => 11, 'quantity' => 30],  // 30ml Syrup đường
                ['material_id' => 30, 'quantity' => 15],  // 15ml Cốt tắc nguyên chất
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'milk_tea_plain' => [
                ['material_id' => 9, 'quantity' => 100],  // 100ml Cốt hồng trà Assam
                ['material_id' => 7, 'quantity' => 25],   // 25g Bột sữa béo bột
                ['material_id' => 3, 'quantity' => 25],   // 25ml Sữa đặc Larosee
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'milk_tea_boba' => [
                ['material_id' => 9, 'quantity' => 100],  // 100ml Cốt hồng trà Assam
                ['material_id' => 7, 'quantity' => 25],   // 25g Bột sữa béo
                ['material_id' => 3, 'quantity' => 25],   // 25ml Sữa đặc
                ['material_id' => 25, 'quantity' => 40],  // 40g Trân châu đen thành phẩm
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'matcha_latte' => [
                ['material_id' => 31, 'quantity' => 5],   // 5g Bột Matcha Nhật Bản Uji
                ['material_id' => 4, 'quantity' => 120],  // 120ml Sữa tươi thanh trùng
                ['material_id' => 11, 'quantity' => 25],  // 25ml Syrup đường
                ['material_id' => 27, 'quantity' => 120], // 120g Đá viên
            ],
            'strawberry_tea' => [
                ['material_id' => 10, 'quantity' => 120], // 120ml Cốt Trà Xanh Lài
                ['material_id' => 11, 'quantity' => 20],  // 20ml Syrup đường
                ['material_id' => 14, 'quantity' => 20],  // 20ml Syrup Dâu Tây Torani
                ['material_id' => 27, 'quantity' => 180], // 180g Đá viên
            ],
            'lychee_chia_tea' => [
                ['material_id' => 10, 'quantity' => 120],
                ['material_id' => 11, 'quantity' => 20],
                ['material_id' => 13, 'quantity' => 15],  // Syrup vải
                ['material_id' => 23, 'quantity' => 2],   // 2 Quả vải Topping
                ['material_id' => 26, 'quantity' => 10],  // 10g Hạt chia ngâm nở
                ['material_id' => 27, 'quantity' => 180],
            ],
            'kumquat_muoi_tea' => [
                ['material_id' => 10, 'quantity' => 150],
                ['material_id' => 11, 'quantity' => 30],
                ['material_id' => 30, 'quantity' => 15],  // Cốt tắc
                ['material_id' => 24, 'quantity' => 2],   // 2 Quả xí muội mặn ngọt
                ['material_id' => 27, 'quantity' => 150],
            ],

            // --- Nhóm Nước Ép & Sinh Tố ---
            'orange_juice' => [
                ['material_id' => 15, 'quantity' => 250], // 250g Quả cam tươi thô để vắt nước
                ['material_id' => 11, 'quantity' => 25],  // 25ml Syrup đường
                ['material_id' => 27, 'quantity' => 100], // 100g Đá viên
            ],
            'watermelon_juice' => [
                ['material_id' => 16, 'quantity' => 180], // 180g Dưa hấu đỏ tươi cắt thỏi
                ['material_id' => 11, 'quantity' => 15],  // 15ml Syrup đường
                ['material_id' => 27, 'quantity' => 100],
            ],
            'pineapple_juice' => [
                ['material_id' => 17, 'quantity' => 150], // 150g Quả thơm chín thô
                ['material_id' => 11, 'quantity' => 20],  // 20ml Syrup đường
                ['material_id' => 27, 'quantity' => 100],
            ],
            'guava_juice' => [
                ['material_id' => 18, 'quantity' => 180], // 180g Ổi hồng tươi thô
                ['material_id' => 11, 'quantity' => 20],  // 20ml Syrup đường
                ['material_id' => 27, 'quantity' => 100],
            ],
            'avocado_smoothie' => [
                ['material_id' => 19, 'quantity' => 130], // 130g Thịt bơ sáp chín thô
                ['material_id' => 4, 'quantity' => 50],   // 50ml Sữa tươi
                ['material_id' => 3, 'quantity' => 35],   // 35ml Sữa đặc Larosee
                ['material_id' => 27, 'quantity' => 180], // 180g Đá viên xay nhuyễn
            ],
            'mango_smoothie' => [
                ['material_id' => 20, 'quantity' => 150], // 150g Thịt xoài cát chín thô
                ['material_id' => 4, 'quantity' => 50],
                ['material_id' => 3, 'quantity' => 30],
                ['material_id' => 27, 'quantity' => 180],
            ],

            // --- Nhóm Đồ Uống Khác ---
            'yogurt_ice' => [
                ['material_id' => 8, 'quantity' => 1],    // 1 Hũ Sữa chua Vinamilk thương phẩm
                ['material_id' => 3, 'quantity' => 35],   // 35ml Sữa đặc
                ['material_id' => 29, 'quantity' => 5],   // 5ml Cốt chanh tạo vị thanh chua
                ['material_id' => 27, 'quantity' => 200], // 200g Đá tuyết
            ],
            'cacao_ice' => [
                ['material_id' => 32, 'quantity' => 15],  // 15g Bột Cacao nguyên chất Choco
                ['material_id' => 4, 'quantity' => 60],   // 60ml Sữa tươi thanh trùng
                ['material_id' => 3, 'quantity' => 30],   // 30ml Sữa đặc
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'passion_fruit' => [
                ['material_id' => 33, 'quantity' => 45],  // 45ml Nước cốt chanh dây đã lọc hạt
                ['material_id' => 11, 'quantity' => 35],  // 35ml Syrup đường nước
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
            'soda_lemon' => [
                ['material_id' => 34, 'quantity' => 150], // 150ml Nước Soda Schweppes đóng chai
                ['material_id' => 11, 'quantity' => 30],  // 30ml Syrup đường
                ['material_id' => 29, 'quantity' => 20],  // 20ml Nước cốt chanh tươi sành
                ['material_id' => 27, 'quantity' => 150], // 150g Đá viên
            ],
        ];

        // Ma trận ánh xạ chính xác từ product_id thực tế (1 - 30) sang khóa mảng công thức mẫu
        $productMapping = [
            1  => 'black_coffee',      2  => 'milk_coffee',       3  => 'bac_xiu', 
            4  => 'salt_coffee',       5  => 'coconut_coffee',    6  => 'milk_fresh_coffee', 
            7  => 'americano',         8  => 'latte_cappuccino',  9  => 'latte_cappuccino', 
            10 => 'egg_coffee',        11 => 'peach_tea',         12 => 'lychee_tea', 
            13 => 'lemon_tea',         14 => 'kumquat_tea',       15 => 'milk_tea_plain', 
            16 => 'milk_tea_boba',     17 => 'matcha_latte',      18 => 'strawberry_tea', 
            19 => 'lychee_chia_tea',   20 => 'kumquat_muoi_tea',  21 => 'orange_juice', 
            22 => 'watermelon_juice',  23 => 'pineapple_juice',   24 => 'guava_juice', 
            25 => 'avocado_smoothie',  26 => 'mango_smoothie',    27 => 'yogurt_ice', 
            28 => 'cacao_ice',         29 => 'passion_fruit',     30 => 'soda_lemon'
        ];

        $variantIdCounter = 1;

        for ($productId = 1; $productId <= 30; $productId++) {
            $templateKey = $productMapping[$productId];
            $currentTemplate = $recipesTemplate[$templateKey];

            // ════════════════════════════════════════════════
            // 1. CHÈN ĐỊNH LƯỢNG CHO BIẾN THỂ SIZE M
            // ════════════════════════════════════════════════
            $variantIdM = $variantIdCounter;
            foreach ($currentTemplate as $item) {
                DB::table('recipes')->insert([
                    'variant_id'      => $variantIdM,
                    'material_id'     => $item['material_id'],
                    'quantity_needed' => $item['quantity'],
                ]);
            }
            $variantIdCounter++; // Tăng ID sang biến thể Size L của cùng sản phẩm đó

            // ════════════════════════════════════════════════
            // 2. CHÈN ĐỊNH LƯỢNG CHO BIẾN THỂ SIZE L (Tăng 20%)
            // ════════════════════════════════════════════════
            $variantIdL = $variantIdCounter;
            foreach ($currentTemplate as $item) {
                // Topping/Nguyên liệu đếm chiếc (Hũ, cái, lát, quả) thuộc ID: 8(Sữa chua), 21(Lát cam), 22(Miếng đào), 23(Quả vải), 24(Xí muội), 35(Trứng)
                // Các nguyên liệu đặc thù này khi làm size L lớn hơn bắt buộc phải làm tròn lên bằng ceil()
                $quantityL = in_array($item['material_id'], [8, 21, 22, 23, 24, 35]) 
                    ? ceil($item['quantity'] * 1.2) 
                    : $item['quantity'] * 1.2;

                DB::table('recipes')->insert([
                    'variant_id'      => $variantIdL,
                    'material_id'     => $item['material_id'],
                    'quantity_needed' => $quantityL,
                ]);
            }
            $variantIdCounter++; // Chuyển tiếp sang cặp biến thể của sản phẩm kế tiếp
        }
    }
}