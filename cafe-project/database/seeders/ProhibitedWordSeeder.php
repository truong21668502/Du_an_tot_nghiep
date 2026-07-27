<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProhibitedWordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $words = [
            // Từ ngữ thô tục, xúc phạm
            'đm', 'đkm', 'vcl', 'clm', 'óc', 'buồi', 'ngu',
            
            // Spam, quảng cáo, cờ bạc lừa đảo
            'cờ bạc', 'cá độ', 'đánh bạc', 'hack game', 'lừa đảo',
            
            // Từ ngữ nhạy cảm chính trị, phản động
            'phản động', 'đảo chính', 'biểu tình', 'bạo loạn', 'chính trị',
        ];

        $data = array_map(function ($word) {
            return [
                'word' => $word,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $words);

        DB::table('prohibited_words')->insert($data);
    }
}
