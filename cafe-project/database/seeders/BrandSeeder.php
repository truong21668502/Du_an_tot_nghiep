<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        Brand::create([
            'brand_name'=>'Nắng Coffee',
            'logo_url'=>'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780996916/NangCoffee_logo_fullmau_wl8jbz.png',
            'description'=>'Thương hiệu cà phê và đồ uống hiện đại với hương vị gần gũi, phù hợp mọi khách hàng.'
        ]);
    }
}