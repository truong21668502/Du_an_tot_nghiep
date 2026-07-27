<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('banners')->insert([
            [
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780986430/banner_2_tich_diem_d20uky.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780983258/banner_3_follow_fanpage_phkjgg.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780983258/banner_1_khai_truong_jkncuo.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780983258/banner_5_mon_moi_xq89lc.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780983259/banner_4_mon_noi_bat_u3imia.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1783495774/background_nangcoffee_gdibni.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
