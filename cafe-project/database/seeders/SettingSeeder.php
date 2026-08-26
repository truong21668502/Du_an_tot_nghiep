<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // =====================================================
            // Voucher tặng khách hàng mới
            // =====================================================
            [
                'group' => 'voucher_gift',
                'key' => 'voucher_gift',
                'value' => 'CHAOMUNG',
                'type' => 'text',
                'description' => 'Mã voucher tặng cho khách hàng mới đăng ký',
            ],


            // =====================================================
            // Thông tin cửa hàng
            // =====================================================
            [
                'group' => 'shop_info',
                'key' => 'shop_email',
                'value' => 'truong21669502@gmail.com',
                'type' => 'text',
                'description' => 'Email cửa hàng',
            ],

            [
                'group' => 'shop_info',
                'key' => 'shop_hotline',
                'value' => '0862859576',
                'type' => 'text',
                'description' => 'Hotline liên hệ',
            ],

            [
                'group' => 'shop_info',
                'key' => 'shop_address',
                'value' => '137 Đường Nguyễn Thị Thập, Thanh Khê, Đà Nẵng, Việt Nam',
                'type' => 'text',
                'description' => 'Địa chỉ cửa hàng',
            ],

            [
                'group' => 'shop_info',
                'key' => 'shop_lat',
                'value' => '16.0757601',
                'type' => 'text',
                'description' => 'Vĩ độ cửa hàng',
            ],

            [
                'group' => 'shop_info',
                'key' => 'shop_lng',
                'value' => '108.1699827',
                'type' => 'text',
                'description' => 'Kinh độ cửa hàng',
            ],

            [
                'group' => 'shop_info',
                'key' => 'goong_place_id',
                'value' => 'hAh7vAlJrX1hhWgQr2Ccnn8mcLOhe7Hsfx80EqJTn-V5gVYar1Kf42K-eBmhl1JvoYA5Cbb3TdZI2VYbrVOtnHiBZDmZbrHYe79JXZ-pn_Z6tJloJb7OFn1KpeCKfj-Tf',
                'type' => 'text',
                'description' => 'Goong Place ID của cửa hàng',
            ],


            // =====================================================
            // Giao hàng
            // =====================================================
            [
                'group' => 'delivery',
                'key' => 'delivery_radius',
                'value' => '5',
                'type' => 'number',
                'description' => 'Bán kính giao hàng tối đa (km)',
            ],

            [
                'group' => 'delivery',
                'key' => 'shipping_fees',
                'value' => json_encode([
                    [
                        'from_km' => 0,
                        'to_km' => 2,
                        'fee' => 10000,
                    ],
                    [
                        'from_km' => 2,
                        'to_km' => 3,
                        'fee' => 15000,
                    ],
                    [
                        'from_km' => 3,
                        'to_km' => 4,
                        'fee' => 20000,
                    ],
                    [
                        'from_km' => 4,
                        'to_km' => 5,
                        'fee' => 25000,
                    ],
                ]),
                'type' => 'json',
                'description' => 'Phí giao hàng theo khoảng cách (JSON)',
            ],


            // =====================================================
            // Website
            // =====================================================
            [
                'group' => 'website',
                'key' => 'social_facebook',
                'value' => 'https://www.facebook.com/profile.php?id=61581909366342',
                'type' => 'text',
                'description' => 'Link Facebook',
            ],

            [
                'group' => 'website',
                'key' => 'social_zalo',
                'value' => 'https://zalo.me/0862859576',
                'type' => 'text',
                'description' => 'Link Zalo',
            ],

            [
                'group' => 'website',
                'key' => 'google_maps_embed_url',
                'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d958.4502622652067!2d108.16879227575338!3d16.075810615347628!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x312d0763e938a625%3A0xed2edc58d1b6fe5b!2zQ2FvIMSR4bqzbmcgRlBUIFBvbHl0ZWNobmljIMSQw6AgTuG6tW5n!5e0!3m2!1svi!2s!4v1780736609360!5m2!1svi!2s',
                'type' => 'text',
                'description' => 'Liên kết Google Maps dùng cho iframe',
            ],
        ];


        // =========================================================
        // Lưu settings
        // =========================================================
        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                [
                    'key' => $setting['key'],
                ],
                $setting
            );
        }
        Cache::forget('shop_settings_grouped');

        $voucherCode = Setting::where('key', 'voucher_gift')->value('value');

        if (!empty($voucherCode)) {

            $coupon = Coupon::query()
                ->where('code', $voucherCode)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiration_date')
                        ->orWhere('expiration_date', '>=', now());
                })
                ->first();

            if ($coupon) {

                User::query()
                    ->select('id')
                    ->chunkById(500, function ($users) use ($coupon) {

                        foreach ($users as $user) {

                            // Không tặng trùng voucher
                            $exists = CouponUser::query()
                                ->where('user_id', $user->id)
                                ->where('coupon_id', $coupon->id)
                                ->exists();

                            if (!$exists) {
                                CouponUser::create([
                                    'user_id' => $user->id,
                                    'coupon_id' => $coupon->id,
                                    'is_used' => false,
                                    'used_at' => null,
                                ]);
                            }
                        }
                    });
            }
        }
    }
}