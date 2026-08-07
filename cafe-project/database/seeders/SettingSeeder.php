<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ---- Thông tin cửa hàng (shop_info) ----
            [
                'group' => 'shop_info',
                'key'   => 'shop_email',
                'value' => 'nangcoffee@gmail.com',
                'type'  => 'text',
                'description' => 'Email cửa hàng',
            ],
            [
                'group' => 'shop_info',
                'key'   => 'shop_hotline',
                'value' => '0123 456 789',
                'type'  => 'text',
                'description' => 'Hotline liên hệ',
            ],
            [
                'group' => 'shop_info',
                'key'   => 'shop_address',
                'value' => '54 Nguyễn Lương Bằng, Hòa Khánh Bắc, Liên Chiểu, Đà Nẵng',
                'type'  => 'text',
                'description' => 'Địa chỉ cửa hàng',
            ],
            [
                'group' => 'shop_info',
                'key'   => 'shop_lat',
                'value' => '16.0757601', // tọa độ mặc định Đà Nẵng
                'type'  => 'text',
                'description' => 'Vĩ độ cửa hàng',
            ],
            [
                'group' => 'shop_info',
                'key'   => 'shop_lng',
                'value' => '108.1699827',
                'type'  => 'text',
                'description' => 'Kinh độ cửa hàng',
            ],
            [
                'group' => 'shop_info',
                'key'   => 'goong_place_id',
                'value' => '',
                'type'  => 'text',
                'description' => 'Goong Place ID của cửa hàng',
            ],

            // ---- Giao hàng (delivery) ----
            [
                'group' => 'delivery',
                'key'   => 'goong_place_id',
                'value' => '',
                'type'  => 'text',
                'description' => 'Goong Place ID (dùng cho delivery)',
            ],
            [
                'group' => 'delivery',
                'key'   => 'delivery_radius',
                'value' => '5',
                'type'  => 'number',
                'description' => 'Bán kính giao hàng (km)',
            ],
            [
                'group' => 'delivery',
                'key'   => 'cash_payment_limit',
                'value' => '500000',
                'type'  => 'number',
                'description' => 'Giới hạn thanh toán tiền mặt (VNĐ)',
            ],
            [
                'group' => 'delivery',
                'key'   => 'shipping_fees',
                'value' => json_encode([
                    ['from_km' => 0, 'to_km' => 2, 'fee' => 10000],
                    ['from_km' => 2, 'to_km' => 3, 'fee' => 15000],
                    ['from_km' => 3, 'to_km' => 4, 'fee' => 20000],
                    ['from_km' => 4, 'to_km' => 5, 'fee' => 25000],
                ]),
                'type'  => 'json',
                'description' => 'Phí ship theo khoảng cách (JSON)',
            ],
            [
                'group' => 'delivery',
                'key'   => 'google_maps_api_key',
                'value' => env('GOOGLE_MAPS_API_KEY', ''),
                'type'  => 'text',
                'description' => 'Google Maps API Key (cho iframe)',
            ],

            // ---- Website ----
            [
                'group' => 'website',
                'key'   => 'social_facebook',
                'value' => 'https://facebook.com/nangcoffee',
                'type'  => 'text',
                'description' => 'Link Facebook',
            ],
            [
                'group' => 'website',
                'key'   => 'social_zalo',
                'value' => 'https://zalo.me/0123456789',
                'type'  => 'text',
                'description' => 'Link Zalo',
            ],
            [
                'group' => 'website',
                'key'   => 'copyright',
                'value' => '© 2026 Nắng Coffee. All rights reserved.',
                'type'  => 'text',
                'description' => 'Copyright text',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}