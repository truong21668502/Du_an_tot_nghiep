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
                'title' => null,
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1785940825/banner_6_menu_moi_hn4vc5.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => null,
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1785941177/banner_7_tradao_zla1pq.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => null,
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780983258/banner_1_khai_truong_jkncuo.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => null,
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780983258/banner_5_mon_moi_xq89lc.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => null,
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'theme' => 'light',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780983259/banner_4_mon_noi_bat_u3imia.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Nắng ấm áp, Cà phê đậm đà',
                'description' => "Bắt đầu ngày mới đầy năng lượng với hương vị cà phê mộc mạc và không gian ngập tràn ánh nắng tại Nắng Coffee.",
                'button_text' => "Xem menu",
                'button_url' => "http://localhost:8000/thuc-don",
                'theme' => 'dark',
                'text_align' => 'center',
                'position' => 'center',
                'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1783495774/background_nangcoffee_gdibni.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
