<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportReceiptDetailSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('import_receipt_details')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('import_receipt_details')->insert([
            // ════════════════════════════════════════════════
            // PHIẾU NHẬP 1: ĐẠI LÝ SỮA (ID 3, 4, 5, 8) -> Tổng: 1,410,000đ
            // ════════════════════════════════════════════════
            [
                'receipt_id'  => 1,
                'material_id' => 3, // Sữa đặc Larosee (Hộp)
                'quantity'    => 13.00, // 13 Hộp x 300ml = 3900ml
                'unit_price'  => 30000.00,
                'created_at'  => Carbon::now()->subDays(10),
                'updated_at'  => Carbon::now()->subDays(10),
            ],
            [
                'receipt_id'  => 1,
                'material_id' => 4, // Sữa tươi Dalat Milk (Lít)
                'quantity'    => 15.00, // 15 Lít = 15000ml
                'unit_price'  => 42000.00,
                'created_at'  => Carbon::now()->subDays(10),
                'updated_at'  => Carbon::now()->subDays(10),
            ],
            [
                'receipt_id'  => 1,
                'material_id' => 5, // Whipping cream (Hộp)
                'quantity'    => 4.00, // 4 Hộp x 500ml = 2000ml
                'unit_price'  => 65000.00,
                'created_at'  => Carbon::now()->subDays(10),
                'updated_at'  => Carbon::now()->subDays(10),
            ],
            [
                'receipt_id'  => 1,
                'material_id' => 8, // Sữa chua hũ Vinamilk (Lốc)
                'quantity'    => 10.00, // 10 Lốc x 4 hũ = 40 hũ
                'unit_price'  => 33000.00,
                'created_at'  => Carbon::now()->subDays(10),
                'updated_at'  => Carbon::now()->subDays(10),
            ],

            // ════════════════════════════════════════════════
            // PHIẾU NHẬP 2: NÔNG SẢN TÂY NGUYÊN (ID 1, 2, 36) -> Tổng: 3,215,000đ
            // ════════════════════════════════════════════════
            [
                'receipt_id'  => 2,
                'material_id' => 1, // Cà phê phin thô (Kg)
                'quantity'    => 10.00, // 10 Kg thô
                'unit_price'  => 140000.00,
                'created_at'  => Carbon::now()->subDays(7),
                'updated_at'  => Carbon::now()->subDays(7),
            ],
            [
                'receipt_id'  => 2,
                'material_id' => 2, // Hạt Espresso pha máy (Kg)
                'quantity'    => 10.00, // 10 Kg hạt
                'unit_price'  => 180000.00,
                'created_at'  => Carbon::now()->subDays(7),
                'updated_at'  => Carbon::now()->subDays(7),
            ],
            [
                'receipt_id'  => 2,
                'material_id' => 36, // Muối tinh Hồng Himalaya (Gói)
                'quantity'    => 1.00, // 1 Gói = 500g
                'unit_price'  => 15000.00,
                'created_at'  => Carbon::now()->subDays(7),
                'updated_at'  => Carbon::now()->subDays(7),
            ],

            // ════════════════════════════════════════════════
            // PHIẾU NHẬP 3: CHỢ ĐẦU MỐI TRÁI CÂY TƯƠI (ID 15 - 20 & 35) -> Tổng: 1,205,000đ
            // ════════════════════════════════════════════════
            [
                'receipt_id'  => 3,
                'material_id' => 15, // Cam sành tươi (Kg)
                'quantity'    => 15.00, // 15 Kg = 15000g
                'unit_price'  => 25000.00,
                'created_at'  => Carbon::now()->subDays(3),
                'updated_at'  => Carbon::now()->subDays(3),
            ],
            [
                'receipt_id'  => 3,
                'material_id' => 16, // Dưa hấu đỏ (Kg)
                'quantity'    => 10.00, // 10 Kg = 10000g
                'unit_price'  => 15000.00,
                'created_at'  => Carbon::now()->subDays(3),
                'updated_at'  => Carbon::now()->subDays(3),
            ],
            [
                'receipt_id'  => 3,
                'material_id' => 17, // Thơm quả tươi (Kg)
                'quantity'    => 5.00,  // 5 Kg = 5000g
                'unit_price'  => 20000.00,
                'created_at'  => Carbon::now()->subDays(3),
                'updated_at'  => Carbon::now()->subDays(3),
            ],
            [
                'receipt_id'  => 3,
                'material_id' => 18, // Ổi hồng tươi (Kg)
                'quantity'    => 5.00,  // 5 Kg = 5000g
                'unit_price'  => 22000.00,
                'created_at'  => Carbon::now()->subDays(3),
                'updated_at'  => Carbon::now()->subDays(3),
            ],
            [
                'receipt_id'  => 3,
                'material_id' => 19, // Bơ sáp chín (Kg)
                'quantity'    => 4.00,  // 4 Kg = 4000g
                'unit_price'  => 45000.00,
                'created_at'  => Carbon::now()->subDays(3),
                'updated_at'  => Carbon::now()->subDays(3),
            ],
            [
                'receipt_id'  => 3,
                'material_id' => 20, // Xoài Cát chín (Kg)
                'quantity'    => 4.00,  // 4 Kg = 4000g
                'unit_price'  => 40000.00,
                'created_at'  => Carbon::now()->subDays(3),
                'updated_at'  => Carbon::now()->subDays(3),
            ],
            [
                'receipt_id'  => 3,
                'material_id' => 35, // Lòng đỏ trứng gà tươi (Vỉ)
                'quantity'    => 5.00,  // 5 Vỉ x 10 cái = 50 cái
                'unit_price'  => 25000.00, // 25k một vỉ trứng lẻ ở chợ
                'created_at'  => Carbon::now()->subDays(3),
                'updated_at'  => Carbon::now()->subDays(3),
            ],

            // ════════════════════════════════════════════════
            // PHIẾU NHẬP 4: TỔNG KHO NGUYÊN AN (CÁC MÃ CÒN LẠI) -> Tổng: 3,420,000đ
            // ════════════════════════════════════════════════
            [
                'receipt_id'  => 4,
                'material_id' => 6, // Nước cốt dừa tươi (Hộp)
                'quantity'    => 10.00, // 10 Hộp x 400ml = 4000ml
                'unit_price'  => 28000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 7, // Bột sữa béo (Túi)
                'quantity'    => 5.00, // 5 Túi x 1000g = 5000g
                'unit_price'  => 70000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 9, // Cốt Hồng Trà Assam (Túi thô để ủ)
                'quantity'    => 1.00, // 1 Túi thô ủ ra 20000ml cốt nước trà
                'unit_price'  => 150000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 10, // Cốt Trà Xanh Lài (Túi thô)
                'quantity'    => 1.50, // 1.5 Túi thô ủ ra 30000ml cốt nước trà
                'unit_price'  => 160000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 11, // Syrup đường nước (Chai)
                'quantity'    => 5.00, // 5 Chai x 1000ml = 5000ml
                'unit_price'  => 45000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 12, // Syrup Đào Monin (Chai)
                'quantity'    => 2.00, // 2 Chai x 750ml = 1500ml
                'unit_price'  => 210000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 13, // Syrup Vải Monin (Chai)
                'quantity'    => 2.00, // 2 Chai x 750ml = 1500ml
                'unit_price'  => 210000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 14, // Syrup Dâu Tây Torani (Chai)
                'quantity'    => 2.00, // 2 Chai x 750ml = 1500ml
                'unit_price'  => 175000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 21, // Cam vàng cắt lát decor (Kg)
                'quantity'    => 2.00, // 2 Kg quả thô cắt được 60 lát decor
                'unit_price'  => 35000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 22, // Đào ngâm đóng hộp (Hũ)
                'quantity'    => 5.00, // 5 Hũ sắt x 8 miếng = 40 miếng
                'unit_price'  => 55000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 23, // Vải ngâm đóng hộp (Hũ)
                'quantity'    => 3.00, // 3 Hũ x 20 quả = 60 quả vải
                'unit_price'  => 60000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 24, // Xí muội mặn ngọt (Gói)
                'quantity'    => 2.00, // 2 Gói x 50 quả = 100 quả xí muội
                'unit_price'  => 40000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 25, // Trân châu đen thành phẩm (Túi)
                'quantity'    => 5.00, // 5 Túi x 1000g = 5000g
                'unit_price'  => 38000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 26, // Hạt chia ngâm nở (Túi)
                'quantity'    => 2.00, // 2 Túi x 500g = 1000g
                'unit_price'  => 80000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 27, // Đá viên giòn tan (Kg)
                'quantity'    => 50.00, // 50 Kg đá x 1000g = 50000g
                'unit_price'  => 4000.00, // 4k một ký đá lẻ
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 28, // Nước cốt sả cây (Lít)
                'quantity'    => 2.00, // 2 Lít = 2000ml
                'unit_price'  => 25000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 29, // Nước cốt chanh tươi (Chai)
                'quantity'    => 4.00, // 4 Chai x 500ml = 2000ml
                'unit_price'  => 20000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 30, // Nước cốt tắc nguyên chất (Chai)
                'quantity'    => 4.00, // 4 Chai x 500ml = 2000ml
                'unit_price'  => 22000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 31, // Bột Matcha Nhật Bản (Túi)
                'quantity'    => 2.00, // 2 Túi x 500g = 1000g
                'unit_price'  => 220000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 32, // Bột Cacao nguyên chất (Hộp)
                'quantity'    => 2.00, // 2 Hộp x 500g = 1000g
                'unit_price'  => 115000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 33, // Nước cốt chanh dây (Chai)
                'quantity'    => 4.00, // 4 Chai x 500ml = 2000ml
                'unit_price'  => 35000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
            [
                'receipt_id'  => 4,
                'material_id' => 34, // Nước Soda đóng chai (Chai)
                'quantity'    => 20.00, // 20 Chai x 320ml = 6400ml
                'unit_price'  => 7000.00,
                'created_at'  => Carbon::now()->subHours(5),
                'updated_at'  => Carbon::now()->subHours(5),
            ],
        ]);
    }
}