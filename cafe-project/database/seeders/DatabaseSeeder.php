<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductImageSeeder::class,
            ProductVariantSeeder::class,
            TableSeeder::class,
            MaterialSeeder::class,
            RecipeSeeder::class,
            CouponSeeder::class,
            CouponUserSeeder::class,
            ImportReceiptSeeder::class,
            ImportReceiptDetailSeeder::class,
            PostCategorySeeder::class,
            PostSeeder::class,
            ProhibitedWordSeeder::class,
            BannerSeeder::class,
            SettingSeeder::class,
        ]);
    }

    //lệnh cho ae chạy nhanh toàn bộ migration và seeder
    //php artisan migrate:fresh --seed
    
}
