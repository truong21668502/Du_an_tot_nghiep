<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Tạo mảng dữ liệu trước để code nhìn ngắn gọn, dễ quản lý
        $categories = [
            [
                'category_name' => 'Cà Phê',
                'description'   => 'Các loại cà phê truyền thống và hiện đại.'
            ],
            [
                'category_name' => 'Trà & Trà Trái Cây',
                'description'   => 'Nhóm đồ uống thanh mát, phù hợp nhiều thời điểm.'
            ],
            [
                'category_name' => 'Nước Ép & Sinh Tố',
                'description'   => 'Đồ uống trái cây tươi tốt cho sức khỏe.'
            ],
            [
                'category_name' => 'Đồ Uống Khác',
                'description'   => 'Các lựa chọn không chứa cà phê.'
            ],
        ];

        // Lặp qua mảng và tự động tạo slug từ tên danh mục
        foreach ($categories as $category) {
            Category::create([
                'category_name' => $category['category_name'],
                'description'   => $category['description'],
                'slug'          => Str::slug($category['category_name']), // Tự động hóa ở đây!
            ]);
        }
    }
}