<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        //tắt kiểm tra khoá ngoại để dọn dữ liệu cũ
        Schema::disableForeignKeyConstraints();

        // Xóa sạch dữ liệu cũ để tránh trùng lặp
        DB::table('tables')->truncate();

        $tables = [
            // --- KHU VỰC: TẦNG TRỆT --- 4 bàn
            [
                'table_name' => 'Bàn Đơn T1.01',
                'area'       => 'Tầng trệt',
                'capacity'   => 2, // Bàn nhỏ 2 người
                'qr_code'    => 'QR_T1_01',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Đơn T1.02',
                'area'       => 'Tầng trệt',
                'capacity'   => 2,
                'qr_code'    => 'QR_T1_02',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Tiêu Chuẩn T1.03',
                'area'       => 'Tầng trệt',
                'capacity'   => 4, // Bàn phổ thông 4 người
                'qr_code'    => 'QR_T1_03',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Sofa T1.04',
                'area'       => 'Tầng trệt',
                'capacity'   => 6, // Bàn Sofa lớn cho nhóm 6 người
                'qr_code'    => 'QR_T1_04',
                'status'     => 'EMPTY',
            ],

            // --- KHU VỰC: TẦNG LẦU --- 4 bàn
            [
                'table_name' => 'Bàn Học L1.01',
                'area'       => 'Tầng lầu',
                'capacity'   => 4,
                'qr_code'    => 'QR_L1_01',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Học L1.02',
                'area'       => 'Tầng lầu',
                'capacity'   => 4,
                'qr_code'    => 'QR_L1_02',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Cửa Sổ L1.03',
                'area'       => 'Tầng lầu',
                'capacity'   => 2,
                'qr_code'    => 'QR_L1_03',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Nhóm L1.04',
                'area'       => 'Tầng lầu',
                'capacity'   => 8, // Bàn dài học nhóm 8 người
                'qr_code'    => 'QR_L1_04',
                'status'     => 'EMPTY',
            ],

            // --- KHU VỰC: SÂN VƯỜN --- 4 bàn
            [
                'table_name' => 'Bàn Ô Dù SV.01',
                'area'       => 'Sân vườn',
                'capacity'   => 4,
                'qr_code'    => 'QR_SV_01',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Ô Dù SV.02',
                'area'       => 'Sân vườn',
                'capacity'   => 4,
                'qr_code'    => 'QR_SV_02',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Tròn SV.03',
                'area'       => 'Sân vườn',
                'capacity'   => 4,
                'qr_code'    => 'QR_SV_03',
                'status'     => 'EMPTY',
            ],
            [
                'table_name' => 'Bàn Dại SV.04',
                'area'       => 'Sân vườn',
                'capacity'   => 6,
                'qr_code'    => 'QR_SV_04',
                'status'     => 'EMPTY',
            ],
        ];

        foreach ($tables as $table) {
            DB::table('tables')->insert([
                'table_name' => $table['table_name'],
                'area'       => $table['area'],
                'capacity'   => $table['capacity'], // Đã đẩy sức chứa vào DB
                'qr_code'    => $table['qr_code'],
                'status'     => $table['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}