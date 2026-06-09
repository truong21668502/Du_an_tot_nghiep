<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Tắt khóa ngoại để truncate làm sạch bảng cũ trước khi seed lại dữ liệu
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('posts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $posts = [
            [
                'category_id'   => 3, // Góc Review
                'user_id'       => 1, // Admin nhập kho
                'title'         => 'Review chi tiết Cà Phê Muối – Cơn sốt bùng nổ vị giác tại quán Nắng Coffee',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/ca_phe_muoi_shhqrh.png',
                'content'       => '<h3>Hương vị mằn mặn béo ngậy khó quên</h3><p>Nếu bạn đang tìm kiếm một trải nghiệm mới lạ thay cho ly đen đá hay sữa đá quen thuộc, <strong>Cà Phê Muối</strong> chính là câu trả lời xuất sắc nhất. Sự kết hợp táo bạo giữa vị đắng đậm đà của hạt Robusta rang vừa cùng lớp kem muối đánh bông mịn màng phủ trên bề mặt.</p><p>Vị mặn nhẹ từ lớp kem không hề lấn lướt cà phê mà đóng vai trò như một chất xúc tác hoàn hảo, làm tôn lên hậu vị ngọt thanh và giảm bớt vị đắng gắt. Một lựa chọn tuyệt vời cho những buổi sáng cần sự tỉnh táo hoặc một góc làm việc yên tĩnh tại quán.</p>',
                'status'        => 'PUBLISHED',
                'published_at'  => now(),
            ],
            [
                'category_id'   => 1, // Tin tức & Sự kiện
                'user_id'       => 1, 
                'title'         => 'Chào hè cực đã với chương trình ưu đãi Trà Đào Cam Sả mua 2 tặng 1 tại Nắng Coffee',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004902/tra_dao_cam_sa_u0lrpd.png',
                'content'       => '<h3>Đập tan cái nóng, nhận ngay ưu đãi khủng</h3><p>Để xua tan những ngày nắng nóng oi bức, chúng tôi mang đến chương trình ưu đãi đặc biệt: <strong>Mua 2 ly Trà Đào Cam Sả tặng ngay 1 ly bất kỳ</strong> trong menu trà trái cây! Thời gian áp dụng từ thứ Hai đến thứ Sáu tuần này.</p><p>Trà Đào Cam Sả là sự hòa quyện hoàn hảo giữa hương sả thanh mát, vị chua ngọt dồi dào vitamin C từ cam sành tươi và những miếng đào chín giòn bùi. Lên lịch ngay với hội bạn thân để không bỏ lỡ bữa tiệc giải nhiệt cực đã này nhé!</p>',
                'status'        => 'PUBLISHED',
                'published_at'  => now(),
            ],
            [
                'category_id'   => 2, // Câu chuyện Cà phê
                'user_id'       => 1,
                'title'         => 'Hành trình di sản từ thức uống gốc Hà Thành đến ly Cà Phê Kem Trứng béo ngậy',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004901/ca_phe_kem_trung_hunwwi.png',
                'content'       => '<h3>Nghệ thuật đánh bông trứng và giữ lửa cà phê</h3><p><strong>Cà Phê Kem Trứng</strong> không đơn thuần là một món giải khát, đó là một nét văn hóa tinh tế. Xuất phát từ lòng Hà Nội cổ kính, món nước này đòi hỏi sự tỉ mỉ tối đa từ người Barista khi xử lý lòng đỏ trứng gà tươi.</p><p>Trứng được đánh bông mịn màng, mềm mại như lụa nhưng không hề bị tanh, tan chảy ngay khi chạm vào nền cà phê đen sóng sánh nguyên chất ấm nóng bên dưới. Sự đối lập giữa vị béo ngậy, ngọt thanh của trứng và vị đắng nồng nàn của cà phê tạo nên một bản giao hưởng hương vị tuyệt mĩ.</p>',
                'status'        => 'PUBLISHED',
                'published_at'  => now(),
            ],
            [
                'category_id'   => 3, // Góc Review
                'user_id'       => 1,
                'title'         => 'Đánh giá menu Nước Ép Trái Cây Tươi: Lựa chọn "xanh" cho ngày làm việc năng động',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/nuoc_ep_trai_cay_gzqkpn.png',
                'content'       => '<h3>Giữ trọn vitamin tự nhiên 100% sạch</h3><p>Xu hướng làm việc tại quán kết hợp với lối sống lành mạnh đang được các bạn trẻ cực kỳ ưa chuộng. Hôm nay, hãy cùng review dòng <strong>Nước Cam Ép</strong> và <strong>Nước Ép Ổi</strong> – hai đại diện xuất sắc luôn nằm trong top gọi món của phái đẹp.</p><p>Trái cây được ép trực tiếp ngay tại quầy khi bạn order, hoàn toàn không pha loãng và không lạm dụng đường hóa học. Vị chua thanh, ngọt tự nhiên giàu vitamin C không chỉ giúp bạn giải khát tức thì mà còn hỗ trợ tăng sức đề kháng và nuôi dưỡng làn da rạng rỡ suốt ngày dài ngồi phòng máy lạnh.</p>',
                'status'        => 'PUBLISHED',
                'published_at'  => now(),
            ],
            [
                'category_id'   => 4, // Tuyển dụng
                'user_id'       => 1,
                'title'         => 'Tìm kiếm đồng đội: Tuyển dụng vị trí Barista và Phục vụ Part-time/Full-time tháng này',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004902/tuyen_dung_v5wrss.png',
                'content'       => '<h3>Gia nhập gia đình Nắng Coffee năng động và sáng tạo</h3><p>Để đáp ứng nhu cầu phục vụ khách hàng ngày một chu đáo hơn, chúng tôi tìm kiếm những mảnh ghép tài năng tiếp theo cho các vị trí: <strong>Barista (Pha chế)</strong> và <strong>Nhân viên Phục vụ</strong> (xoay ca linh hoạt dành cho sinh viên).</p><p>Nếu bạn có niềm đam mê với hương vị hạt cà phê Robusta, yêu thích môi trường làm việc trẻ trung, không ngại giao tiếp và muốn học hỏi quy trình vận hành quán chuyên nghiệp, hãy gửi ngay CV về cho chúng tôi. Quyền lợi bao gồm mức lương thưởng cạnh tranh, training bài bản và lộ trình thăng tiến rõ ràng!</p>',
                'status'        => 'PUBLISHED',
                'published_at'  => now(),
            ],
        ];

        foreach ($posts as $post) {
            DB::table('posts')->insert([
                'category_id'   => $post['category_id'],
                'user_id'       => $post['user_id'],
                'title'         => $post['title'],
                // Đảm bảo slug unique tuyệt đối bằng cách gắn thêm hậu tố ngẫu nhiên để không bị lỗi SEO chèn trùng
                'slug'          => Str::slug($post['title']) . '-' . rand(100, 999), 
                'thumbnail_url' => $post['thumbnail_url'],
                'content'       => $post['content'],
                'status'        => $post['status'],
                'published_at'  => $post['published_at'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}