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
            'đm', 'đkm', 'vcl', 'clm', 'óc', 'buồi', 'ngu', 'cặc', 'đĩ', 'lồn', 'lợn', 'đéo', 'địt', 'đụ',
            
            // Spam, quảng cáo, cờ bạc lừa đảo
            'cờ bạc', 'cá độ', 'đánh bạc', 'hack game', 'lừa đảo',
            
            // Từ ngữ nhạy cảm chính trị, phản động
            'phản động', 'đảo chính', 'biểu tình', 'bạo loạn', 'chính trị',

            // Nội dung khiêu dâm, 18+
            'sex', 'xxx', 'porn', 'khiêu dâm', '18+', 'nude', 'sexy',

            // Nội dung liên quan đến ma túy, chất cấm
            'ma túy', 'cần sa', 'heroin', 'cocaine',

            // Nội dung liên quan đến bạo lực, tự sát
            'bạo lực', 'tự sát', 'giết người', 'đánh nhau', 'tấn công',

            // Nội dung liên quan đến vũ khí, súng đạn
            'súng', 'dao', 'bom', 'vũ khí',

            // Nội dung liên quan đến phân biệt chủng tộc, kỳ thị
            'kỳ thị', 'phân biệt chủng tộc', 'chủng tộc', 'người da đen', 'người da trắng', 'người châu Á', 'người Hồi giáo', 'người Do Thái', 'người LGBT', 'người đồng tính', 'người chuyển giới', 'người dị tính',

            // Nội dung liên quan đến tôn giáo, tín ngưỡng
            'tôn giáo', 'tín ngưỡng', 'phật giáo', 'cơ đốc giáo', 'hồi giáo', 'đạo hồi', 'đạo thiên chúa'
        ];

        $data = array_map(function ($word) {
            return [
                'word' => $word,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $words);

        DB::table('prohibited_words')->insertOrIgnore($data);
    }
}
