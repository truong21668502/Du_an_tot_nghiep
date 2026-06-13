<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductVariant;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        // Định nghĩa giá gốc của Size M cho từng loại sản phẩm (ID từ 1 đến 30)
        // Giá được thiết kế cực kỳ bình dân và hợp lý cho quán cà phê
        $basePrices = [
            // --- Nhóm Cà Phê (ID 1 - 10) ---
            1  => 15000, // Cà Phê Đen Đá
            2  => 20000, // Cà Phê Sữa Đá
            3  => 20000, // Bạc Xỉu
            4  => 25000, // Cà Phê Muối
            5  => 30000, // Cà Phê Cốt Dừa (Món đặc trưng giá cao hơn chút)
            6  => 25000, // Cà Phê Sữa Tươi
            7  => 25000, // Americano
            8  => 30000, // Latte
            9  => 30000, // Cappuccino
            10 => 29000, // Cà Phê Kem Trứng

            // --- Nhóm Trà & Trà Trái Cây (ID 11 - 20) ---
            11 => 29000, // Trà Đào Cam Sả
            12 => 25000, // Trà Vải
            13 => 15000, // Trà Chanh
            14 => 15000, // Trà Tắc
            15 => 25000, // Trà Sữa Truyền Thống
            16 => 29000, // Trà Sữa Trân Châu
            17 => 35000, // Matcha Latte
            18 => 29000, // Trà Dâu
            19 => 32000, // Trà Vải Hạt Chia
            20 => 20000, // Trà Tắc Xí Muội

            // --- Nhóm Nước Ép & Sinh Tố (ID 21 - 26) ---
            21 => 25000, // Nước Cam Ép
            22 => 22000, // Nước Ép Dưa Hấu
            23 => 22000, // Nước Ép Thơm (Dứa)
            24 => 22000, // Nước Ép Ổi
            25 => 32000, // Sinh Tố Bơ
            26 => 32000, // Sinh Tố Xoài

            // --- Nhóm Đồ Uống Khác (ID 27 - 30) ---
            27 => 20000, // Sữa Chua Đánh Đá
            28 => 29000, // Cacao Đá (Nóng)
            29 => 20000, // Chanh Dây Đá
            30 => 20000, // Soda Chanh Đường
        ];

        // Lặp qua từng sản phẩm để tự động tạo 2 size M và L
        foreach ($basePrices as $productId => $priceM) {
            
            // 1. Tạo biến thể cho Size M
            ProductVariant::create([
                'product_id'       => $productId,
                'size'             => 'M',
                'price'            => $priceM,
                'discount_price'   => null, // Mặc định không giảm giá
                'sale_date_start'  => null,
                'sale_date_end'    => null, 
                'sold'             => 0,    // Đã bán mặc định bằng 0 theo yêu cầu
                'status'           => 'AVAILABLE',
            ]);

            // 2. Tính toán giá cho Size L (Lớn hơn size M 20%)
            $priceL = $priceM * 1.2; 

            // Tạo biến thể cho Size L
            ProductVariant::create([
                'product_id'       => $productId,
                'size'             => 'L',
                'price'            => $priceL,
                'discount_price'   => null,
                'sale_date_start'  => null,
                'sale_date_end'    => null, 
                'sold'             => 0,    // Đã bán mặc định bằng 0 theo yêu cầu
                'status'           => 'AVAILABLE',
            ]);
        }
    }
}