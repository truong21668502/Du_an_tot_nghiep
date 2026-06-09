<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tắt khóa ngoại để làm sạch bảng cũ khi chạy lại lệnh test
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('post_categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            [
                'name' => 'Tin tức & Sự kiện',
                'description' => 'Cập nhật các chương trình khuyến mãi, sự kiện đặc biệt và hoạt động mới nhất của quán.',
            ],
            [
                'name' => 'Câu chuyện Cà phê',
                'description' => 'Nơi chia sẻ về nguồn gốc các hạt cà phê, nghệ thuật pha chế và văn hóa thưởng thức.',
            ],
            [
                'name' => 'Góc Review',
                'description' => 'Đánh giá chi tiết về các món nước uống mới, không gian học tập và làm việc tại quán.',
            ],
            [
                'name' => 'Tuyển dụng',
                'description' => 'Thông tin tìm kiếm đồng đội, các vị trí Barista, phục vụ part-time và full-time với môi trường năng động.',
            ],
        ];

        foreach ($categories as $cat) {
            DB::table('post_categories')->insert([
                'name'        => $cat['name'],
                // Tự động chuyển đổi tên danh mục thành chuỗi slug SEO (ví dụ: "Tin tức & Sự kiện" thành "tin-tuc-su-kien")
                'slug'        => Str::slug($cat['name']), 
                'description' => $cat['description'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}