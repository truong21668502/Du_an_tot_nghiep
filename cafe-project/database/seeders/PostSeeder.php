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
                'category_id' => 3, // Góc Review
                'user_id' => 1, // Admin nhập kho
                'title' => 'Review chi tiết Cà Phê Muối – Cơn sốt bùng nổ vị giác tại quán Nắng Coffee',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/ca_phe_muoi_shhqrh.png',
                'content' => '<h3>Hương vị mằn mặn béo ngậy khó quên</h3><p>Nếu bạn đang tìm kiếm một trải nghiệm mới lạ thay cho ly đen đá hay sữa đá quen thuộc, <strong>Cà Phê Muối</strong> chính là câu trả lời xuất sắc nhất. Sự kết hợp táo bạo giữa vị đắng đậm đà của hạt Robusta rang vừa cùng lớp kem muối đánh bông mịn màng phủ trên bề mặt.</p><p>Vị mặn nhẹ từ lớp kem không hề lấn lướt cà phê mà đóng vai trò như một chất xúc tác hoàn hảo, làm tôn lên hậu vị ngọt thanh và giảm bớt vị đắng gắt. Một lựa chọn tuyệt vời cho những buổi sáng cần sự tỉnh táo hoặc một góc làm việc yên tĩnh tại quán.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 1, // Tin tức & Sự kiện
                'user_id' => 1,
                'title' => 'Chào hè cực đã với chương trình ưu đãi Trà Đào Cam Sả mua 2 tặng 1 tại Nắng Coffee',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004902/tra_dao_cam_sa_u0lrpd.png',
                'content' => '<h3>Đập tan cái nóng, nhận ngay ưu đãi khủng</h3><p>Để xua tan những ngày nắng nóng oi bức, chúng tôi mang đến chương trình ưu đãi đặc biệt: <strong>Mua 2 ly Trà Đào Cam Sả tặng ngay 1 ly bất kỳ</strong> trong menu trà trái cây! Thời gian áp dụng từ thứ Hai đến thứ Sáu tuần này.</p><p>Trà Đào Cam Sả là sự hòa quyện hoàn hảo giữa hương sả thanh mát, vị chua ngọt dồi dào vitamin C từ cam sành tươi và những miếng đào chín giòn bùi. Lên lịch ngay với hội bạn thân để không bỏ lỡ bữa tiệc giải nhiệt cực đã này nhé!</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 2, // Câu chuyện Cà phê
                'user_id' => 1,
                'title' => 'Hành trình di sản từ thức uống gốc Hà Thành đến ly Cà Phê Kem Trứng béo ngậy',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004901/ca_phe_kem_trung_hunwwi.png',
                'content' => '<h3>Nghệ thuật đánh bông trứng và giữ lửa cà phê</h3><p><strong>Cà Phê Kem Trứng</strong> không đơn thuần là một món giải khát, đó là một nét văn hóa tinh tế. Xuất phát từ lòng Hà Nội cổ kính, món nước này đòi hỏi sự tỉ mỉ tối đa từ người Barista khi xử lý lòng đỏ trứng gà tươi.</p><p>Trứng được đánh bông mịn màng, mềm mại như lụa nhưng không hề bị tanh, tan chảy ngay khi chạm vào nền cà phê đen sóng sánh nguyên chất ấm nóng bên dưới. Sự đối lập giữa vị béo ngậy, ngọt thanh của trứng và vị đắng nồng nàn của cà phê tạo nên một bản giao hưởng hương vị tuyệt mĩ.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 3, // Góc Review
                'user_id' => 1,
                'title' => 'Đánh giá menu Nước Ép Trái Cây Tươi: Lựa chọn "xanh" cho ngày làm việc năng động',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/nuoc_ep_trai_cay_gzqkpn.png',
                'content' => '<h3>Giữ trọn vitamin tự nhiên 100% sạch</h3><p>Xu hướng làm việc tại quán kết hợp với lối sống lành mạnh đang được các bạn trẻ cực kỳ ưa chuộng. Hôm nay, hãy cùng review dòng <strong>Nước Cam Ép</strong> và <strong>Nước Ép Ổi</strong> – hai đại diện xuất sắc luôn nằm trong top gọi món của phái đẹp.</p><p>Trái cây được ép trực tiếp ngay tại quầy khi bạn order, hoàn toàn không pha loãng và không lạm dụng đường hóa học. Vị chua thanh, ngọt tự nhiên giàu vitamin C không chỉ giúp bạn giải khát tức thì mà còn hỗ trợ tăng sức đề kháng và nuôi dưỡng làn da rạng rỡ suốt ngày dài ngồi phòng máy lạnh.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 4, // Tuyển dụng
                'user_id' => 1,
                'title' => 'Tìm kiếm đồng đội: Tuyển dụng vị trí Barista và Phục vụ Part-time/Full-time tháng này',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004902/tuyen_dung_v5wrss.png',
                'content' => '<h3>Gia nhập gia đình Nắng Coffee năng động và sáng tạo</h3><p>Để đáp ứng nhu cầu phục vụ khách hàng ngày một chu đáo hơn, chúng tôi tìm kiếm những mảnh ghép tài năng tiếp theo cho các vị trí: <strong>Barista (Pha chế)</strong> và <strong>Nhân viên Phục vụ</strong> (xoay ca linh hoạt dành cho sinh viên).</p><p>Nếu bạn có niềm đam mê với hương vị hạt cà phê Robusta, yêu thích môi trường làm việc trẻ trung, không ngại giao tiếp và muốn học hỏi quy trình vận hành quán chuyên nghiệp, hãy gửi ngay CV về cho chúng tôi. Quyền lợi bao gồm mức lương thưởng cạnh tranh, training bài bản và lộ trình thăng tiến rõ ràng!</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 2, // Câu chuyện Cà phê
                'user_id' => 1,
                'title' => 'Bạc Xỉu – Ly sữa nóng "mang hồn cà phê" dành cho người mới bắt đầu',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/bac_xiu_placeholder.png',
                'content' => '<h3>Khi cà phê chỉ là nền, sữa mới là nhân vật chính</h3><p><strong>Bạc Xỉu</strong> vốn được xem là "phiên bản nhẹ nhàng" dành cho những ai chưa quen với vị đắng gắt của cà phê phin truyền thống. Tỷ lệ sữa đặc và sữa tươi chiếm phần lớn, chỉ điểm xuyết thêm chút cà phê để dậy mùi thơm đặc trưng.</p><p>Ly Bạc Xỉu tại quán được pha theo công thức gia truyền, sữa béo ngậy hòa quyện cùng đá viên tan chậm, giữ trọn hương vị từ ngụm đầu đến ngụm cuối. Đây chính là lựa chọn an toàn cho các bạn nữ hoặc người mới lần đầu ghé quán.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 3, // Góc Review
                'user_id' => 1,
                'title' => 'Trải nghiệm Trà Sữa Trân Châu Đường Đen: Vị ngọt khó cưỡng gây thương nhớ',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/tra_sua_duong_den_placeholder.png',
                'content' => '<h3>Từng viên trân châu dẻo dai quyện vị đường đen thắng khéo</h3><p><strong>Trà Sữa Trân Châu Đường Đen</strong> tiếp tục là món "quốc dân" chưa bao giờ hết hot tại Nắng Coffee. Lớp đường đen được thắng thủ công tạo vệt caramel bắt mắt chảy dọc thành ly, hòa cùng vị béo của sữa tươi và hương trà ô long thanh nhẹ.</p><p>Điểm cộng lớn nhất nằm ở phần trân châu được nấu mới mỗi ngày, dẻo dai vừa miệng, không bị cứng hay quá ngọt gắt. Một ly vừa đủ no bụng lại vừa thỏa mãn cơn thèm ngọt giữa buổi chiều.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 1, // Tin tức & Sự kiện
                'user_id' => 1,
                'title' => 'Ra mắt dòng sản phẩm mới: Matcha Latte Đá Xay phong cách Nhật Bản',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/matcha_latte_placeholder.png',
                'content' => '<h3>Làn gió mới cho tín đồ mê vị trà xanh</h3><p>Nắng Coffee chính thức giới thiệu <strong>Matcha Latte Đá Xay</strong>, sử dụng bột trà xanh Uji nhập khẩu trực tiếp từ Nhật Bản, mang đến vị đắng chát thanh tao đặc trưng không lẫn vào đâu được.</p><p>Kết hợp cùng lớp kem sữa béo mịn phía trên và đá xay nhuyễn mát lạnh, sản phẩm mới hứa hẹn sẽ trở thành lựa chọn hot trong mùa hè này. Ưu đãi giảm 15% cho 100 ly đầu tiên trong ngày ra mắt!</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 2, // Câu chuyện Cà phê
                'user_id' => 1,
                'title' => 'Cold Brew là gì? Bí quyết ủ lạnh 12 giờ tạo nên tách cà phê êm dịu',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/cold_brew_placeholder.png',
                'content' => '<h3>Chậm rãi chiết xuất, trọn vẹn hương vị</h3><p><strong>Cold Brew</strong> là phương pháp pha chế cà phê bằng nước lạnh trong thời gian dài, thường từ 12 đến 24 giờ, thay vì dùng nhiệt như cách pha truyền thống. Nhờ vậy, cà phê giữ được vị ngọt tự nhiên, giảm đáng kể độ chua và đắng gắt.</p><p>Tại Nắng Coffee, hạt Arabica được xay vừa và ủ lạnh đúng 14 giờ trong bình chuyên dụng, cho ra thành phẩm có màu hổ phách trong veo cùng hương thơm dịu nhẹ, rất thích hợp cho những ai yêu thích vị cà phê êm và ít gắt.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 3, // Góc Review
                'user_id' => 1,
                'title' => 'Review Sinh Tố Bơ Sáp: Béo mịn như kem, ngọt tự nhiên không cần thêm đường',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/sinh_to_bo_placeholder.png',
                'content' => '<h3>Món quốc dân dành cho hội yêu vị béo</h3><p><strong>Sinh Tố Bơ Sáp</strong> được xay từ những trái bơ sáp chín tới, kết hợp cùng sữa đặc và đá viên xay nhuyễn cho ra kết cấu sánh mịn như kem tươi. Vị béo tự nhiên của bơ hòa quyện cùng độ ngọt vừa phải, không hề gây ngán.</p><p>Đây là món uống lý tưởng để bổ sung năng lượng, đặc biệt phù hợp cho bữa xế chiều hoặc thay thế bữa sáng nhẹ nhàng. Quán cam kết chọn bơ tuyển từ vựa trái cây quen, không dùng bơ non hay bơ để đông lạnh.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 1, // Tin tức & Sự kiện
                'user_id' => 1,
                'title' => 'Khai trương chi nhánh mới: Ngàn ưu đãi hấp dẫn chào đón khách hàng thân thiết',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/khai_truong_placeholder.png',
                'content' => '<h3>Nắng Coffee chính thức mở rộng thêm không gian mới</h3><p>Nhằm phục vụ tốt hơn nhu cầu của khách hàng, Nắng Coffee hân hạnh thông báo <strong>khai trương chi nhánh mới</strong> với không gian rộng rãi, thoáng mát, được thiết kế theo phong cách tối giản hiện đại.</p><p>Nhân dịp đặc biệt này, toàn bộ menu thức uống được giảm giá 20% trong 3 ngày đầu khai trương, cùng nhiều phần quà hấp dẫn dành cho 50 khách hàng check-in đầu tiên mỗi ngày. Hẹn gặp các bạn tại địa điểm mới nhé!</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 2, // Câu chuyện Cà phê
                'user_id' => 1,
                'title' => 'Từ hạt Arabica Cầu Đất đến ly Cappuccino thơm nồng vân sữa nghệ thuật',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/cappuccino_placeholder.png',
                'content' => '<h3>Nghệ thuật Latte Art trên nền cà phê cao nguyên</h3><p><strong>Cappuccino</strong> tại Nắng Coffee được tuyển chọn từ hạt Arabica trồng ở vùng Cầu Đất, Đà Lạt – nơi có khí hậu mát mẻ quanh năm tạo nên hương vị chua thanh, thơm nhẹ đặc trưng của vùng cao nguyên.</p><p>Lớp bọt sữa được đánh bông tơi mịn, kết hợp cùng kỹ thuật rót tạo hình Latte Art tinh tế, không chỉ ngon miệng mà còn đẹp mắt để check-in. Mỗi ly Cappuccino là một tác phẩm nhỏ của người Barista.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 3, // Góc Review
                'user_id' => 1,
                'title' => 'Đánh giá Soda Chanh Dây: Vị chua ngọt sảng khoái giải nhiệt tức thì',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/soda_chanh_day_placeholder.png',
                'content' => '<h3>Bùng nổ vị giác với hàng ngàn bọt khí li ti</h3><p><strong>Soda Chanh Dây</strong> là sự kết hợp giữa vị chua thanh đặc trưng của chanh dây tươi và nước soda sủi bọt mát lạnh. Từng ngụm nước tạo cảm giác sảng khoái tức thì, cực kỳ phù hợp để giải nhiệt vào những ngày oi bức.</p><p>Hạt chanh dây lấm tấm bên trong không chỉ tạo điểm nhấn thị giác mà còn mang đến kết cấu thú vị khi thưởng thức. Đây là món uống được lòng cả người lớn lẫn các bạn nhỏ nhờ vị chua ngọt hài hòa, không quá gắt.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 4, // Tuyển dụng
                'user_id' => 1,
                'title' => 'Cơ hội thực tập sinh Marketing tại Nắng Coffee: Học hỏi, cống hiến và phát triển',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/tuyen_dung_marketing_placeholder.png',
                'content' => '<h3>Cùng xây dựng câu chuyện thương hiệu đầy cảm hứng</h3><p>Nắng Coffee đang tìm kiếm các bạn sinh viên năng động, yêu thích lĩnh vực <strong>Marketing và Truyền thông</strong> để đồng hành trong vai trò thực tập sinh. Công việc bao gồm lên ý tưởng nội dung, chụp ảnh sản phẩm và quản lý fanpage.</p><p>Đây là cơ hội tuyệt vời để các bạn trẻ cọ xát thực tế, học hỏi quy trình xây dựng thương hiệu F&B bài bản từ những người có kinh nghiệm. Ứng viên phù hợp sẽ được xem xét ký hợp đồng chính thức sau kỳ thực tập.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 2, // Câu chuyện Cà phê
                'user_id' => 1,
                'title' => 'Trà Ô Long Sữa Nướng: Hương vị mộc mạc gợi nhớ ký ức tuổi thơ',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/tra_o_long_sua_nuong_placeholder.png',
                'content' => '<h3>Khi trà ô long gặp gỡ hương sữa nướng thơm lừng</h3><p><strong>Trà Ô Long Sữa Nướng</strong> mang đến trải nghiệm vị giác độc đáo khi kết hợp giữa vị chát nhẹ, hậu ngọt của trà ô long thượng hạng cùng hương thơm béo ngậy đặc trưng của sữa nướng caramen hóa.</p><p>Từng ngụm trà đem lại cảm giác ấm áp, gợi nhớ về hương vị bánh sữa nướng quen thuộc thời thơ ấu. Món uống này phù hợp cho những buổi chiều muốn tìm chút bình yên và hoài niệm giữa nhịp sống hối hả.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 1, // Tin tức & Sự kiện
                'user_id' => 1,
                'title' => 'Nắng Coffee chính thức triển khai app đặt hàng online, tích điểm đổi quà siêu tiện lợi',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/app_dat_hang_placeholder.png',
                'content' => '<h3>Đặt món dễ dàng chỉ với vài thao tác chạm</h3><p>Nhằm nâng cao trải nghiệm khách hàng, Nắng Coffee vừa chính thức ra mắt <strong>ứng dụng đặt hàng online</strong>, cho phép khách hàng đặt món, thanh toán và theo dõi đơn hàng ngay trên điện thoại mà không cần xếp hàng chờ đợi.</p><p>Đặc biệt, mỗi đơn hàng đặt qua app sẽ được tích điểm thưởng để đổi lấy nước uống miễn phí hoặc các phần quà hấp dẫn khác. Tải app ngay hôm nay để nhận ưu đãi 50% cho đơn hàng đầu tiên!</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 3, // Góc Review
                'user_id' => 1,
                'title' => 'Review Yakult Đào: Sự kết hợp lạ miệng giữa men vi sinh và đào ngâm giòn ngọt',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/yakult_dao_placeholder.png',
                'content' => '<h3>Vị chua dịu lên men cực kích thích vị giác</h3><p><strong>Yakult Đào</strong> là món thức uống trẻ trung, kết hợp giữa vị chua nhẹ đặc trưng của men vi sinh Yakult cùng những lát đào ngâm giòn ngọt thanh mát. Sự pha trộn này tạo nên hương vị vừa lạ vừa quen, rất dễ gây nghiện.</p><p>Đá bào mịn cùng chút syrup đào giúp cân bằng vị chua, mang lại cảm giác sảng khoái tức thì. Đây là món uống được giới trẻ đặc biệt yêu thích vì vừa ngon miệng vừa tốt cho hệ tiêu hóa.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 2, // Câu chuyện Cà phê
                'user_id' => 1,
                'title' => 'Espresso nguyên chất: Tinh túy cô đọng trong từng giọt cà phê đậm đặc',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/espresso_placeholder.png',
                'content' => '<h3>Chuẩn mực của mọi ly cà phê pha máy</h3><p><strong>Espresso</strong> được xem là nền tảng của hầu hết các loại thức uống cà phê pha máy hiện đại. Với áp suất chiết xuất cao trong thời gian ngắn, espresso cô đọng trọn vẹn hương vị đậm đà cùng lớp crema vàng nâu đặc trưng trên bề mặt.</p><p>Tại Nắng Coffee, mỗi shot espresso được canh chỉnh chính xác về thời gian và định lượng, đảm bảo vị đắng vừa phải, hậu ngọt rõ ràng, không bị cháy khét. Phù hợp cho những tín đồ cà phê thực thụ muốn thưởng thức hương vị nguyên bản nhất.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 3, // Góc Review
                'user_id' => 1,
                'title' => 'Trải nghiệm Chocolate Đá Xay: Ngọt ngào tan chảy cho hội mê socola',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/chocolate_da_xay_placeholder.png',
                'content' => '<h3>Đắm chìm trong lớp socola đậm đặc mịn màng</h3><p><strong>Chocolate Đá Xay</strong> được pha chế từ bột cacao nguyên chất, xay cùng đá viên và sữa tươi tạo nên kết cấu sánh mịn như kem, phủ thêm lớp whipping cream béo ngậy cùng sốt chocolate rưới nghệ thuật bên trên.</p><p>Đây là món uống "gây nghiện" dành cho những tín đồ mê vị ngọt của socola, đặc biệt phù hợp để thưởng thức cùng bạn bè trong những buổi tán gẫu cuối tuần. Vị ngọt đậm đà nhưng không hề gắt, cân bằng tốt với vị béo của sữa.</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
            [
                'category_id' => 1, // Tin tức & Sự kiện
                'user_id' => 1,
                'title' => 'Sự kiện Cupping Cà Phê cuối tuần: Cùng khám phá hương vị từ 5 vùng nguyên liệu khác nhau',
                'thumbnail_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1781004903/cupping_event_placeholder.png',
                'content' => '<h3>Trải nghiệm như một chuyên gia thử nếm cà phê</h3><p>Cuối tuần này, Nắng Coffee tổ chức buổi <strong>Cupping Cà Phê</strong> miễn phí dành cho khách hàng yêu thích tìm hiểu sâu về hương vị. Bạn sẽ được nếm thử và so sánh 5 loại hạt cà phê đến từ các vùng trồng khác nhau như Cầu Đất, Sơn La, Khe Sanh.</p><p>Chuyên gia pha chế của quán sẽ trực tiếp hướng dẫn cách nhận biết hương thơm, độ chua, độ đắng và hậu vị đặc trưng của từng vùng nguyên liệu. Số lượng chỗ ngồi có hạn, hãy nhanh tay đăng ký để không bỏ lỡ trải nghiệm thú vị này!</p>',
                'status' => 'PUBLISHED',
                'published_at' => now(),
            ],
        ];

        foreach ($posts as $post) {
            DB::table('posts')->insert([
                'category_id' => $post['category_id'],
                'user_id' => $post['user_id'],
                'title' => $post['title'],
                // Đảm bảo slug unique tuyệt đối bằng cách gắn thêm hậu tố ngẫu nhiên để không bị lỗi SEO chèn trùng
                'slug' => Str::slug($post['title']) . '-' . rand(100, 999),
                'thumbnail_url' => $post['thumbnail_url'],
                'content' => $post['content'],
                'status' => $post['status'],
                'published_at' => $post['published_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}