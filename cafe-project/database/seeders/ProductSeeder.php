<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ============================================================
            // CATEGORY 1: Cà Phê (category_id = 1) — 10 món
            // ============================================================
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Cà Phê Đen Đá',
                'short_description' => 'Cà phê nguyên chất đậm vị, khởi đầu ngày mới tỉnh táo.',
                'description'       => 'Cà phê đen đá được pha từ hạt Robusta chọn lọc, rang vừa để giữ trọn hương thơm mạnh mẽ và vị đắng cân bằng. Biểu tượng truyền thống của văn hóa cà phê Việt, thích hợp cho những ai yêu thích sự tỉnh táo thuần túy mỗi sáng.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/q_auto/f_auto/v1780720442/ca_phe_den_da_cb7rmy.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Cà Phê Sữa Đá',
                'short_description' => 'Thức uống quốc dân, cân bằng hoàn hảo giữa đắng và ngọt.',
                'description'       => 'Cà phê sữa đá là chuẩn mực cà phê Việt Nam — cà phê rang xay đậm đà hòa quyện cùng sữa đặc béo ngậy, thêm đá lạnh giòn tan. Phù hợp cho buổi sáng cần năng lượng hoặc những cuộc trò chuyện bên ly cà phê quen thuộc.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/q_auto/f_auto/v1780720446/ca_phe_sua_da_pnycxu.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Bạc Xỉu',
                'short_description' => 'Cà phê sữa nhẹ nhàng, thơm béo và dễ uống.',
                'description'       => 'Bạc xỉu là lựa chọn tinh tế cho những ai tìm kiếm sự dịu nhẹ — tỉ lệ sữa nhiều hơn cà phê mang lại vị ngọt thanh, béo mịn và hương cà phê thoang thoảng. Lý tưởng cho người mới bắt đầu thưởng thức cà phê hoặc ưa khẩu vị nhẹ nhàng.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720389/bac_xiu_z80skm.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Cà Phê Muối',
                'short_description' => 'Ngôi sao hiện đại — vị mặn, ngọt, đắng hoà quyện độc đáo.',
                'description'       => 'Cà phê muối kết hợp giữa cà phê đen đậm đà và lớp kem muối mịn màng phủ trên mặt ly. Vị mặn nhẹ cân bằng hoàn hảo với vị đắng cà phê, tạo nên dư vị khó quên — thức uống "ngôi sao" được giới trẻ yêu thích.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720444/ca_phe_muoi_skbxgx.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Cà Phê Cốt Dừa',
                'short_description' => 'Cà phê nguyên chất hòa quyện kem dừa béo thơm nhiệt đới.',
                'description'       => 'Cà phê cốt dừa là sáng tạo đặc trưng của Nắng Coffee — cà phê đen đậm đà kết hợp kem dừa tươi béo thơm, topping thêm dừa bào mỏng giòn. Hương vị nhiệt đới độc đáo, là món yêu thích của giới trẻ và du khách.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720403/ca_phe_cot_dua_abl2vn.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Cà Phê Sữa Tươi',
                'short_description' => 'Hương vị thanh tao, hiện đại cho buổi sáng nhẹ nhàng.',
                'description'       => 'Cà phê sữa tươi sử dụng sữa tươi thay vì sữa đặc, mang lại vị béo thanh tao, ít ngọt hơn và tinh tế hơn. Sự lựa chọn sáng giá của người trẻ hiện đại, phù hợp cho cả uống nóng lẫn uống đá.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720443/ca_phe_sua_tuoi_znvm2y.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Americano',
                'short_description' => 'Tinh hoa hạt cà phê nguyên bản, thanh vị và năng động.',
                'description'       => 'Americano được pha từ espresso pha loãng với nước nóng, giữ lại tinh chất và hương thơm đặc trưng của hạt cà phê mà không quá đậm đặc. Lựa chọn tối ưu cho phong cách sống năng động và ưu tiên sức khỏe.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720347/americano_xzgyjl.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Latte',
                'short_description' => 'Espresso và sữa nóng hòa quyện mềm mại, sang trọng.',
                'description'       => 'Latte được tạo từ espresso và sữa hấp nóng theo tỉ lệ 1:3, bề mặt phủ lớp foam mỏng tạo hình latte art tinh tế. Vị cà phê dịu nhẹ hòa cùng sữa mang đến trải nghiệm hương vị mềm mại, sang trọng đúng chuẩn cà phê Ý.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720446/latte_jcyxhy.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Cappuccino',
                'short_description' => 'Hương vị cà phê Ý chuẩn mực với lớp bọt sữa dày mịn.',
                'description'       => 'Cappuccino là sự hòa quyện hoàn hảo giữa espresso đậm đặc, sữa hấp nóng và lớp foam sữa dày mịn đặc trưng. Thức uống tinh tế mang phong cách cà phê châu Âu, làm nên nét sang trọng cho mọi thực đơn.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720447/cappuccino_zxocdy.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 1,
                'brand_id'          => 1,
                'product_name'      => 'Cà Phê Kem Trứng',
                'short_description' => 'Đặc sản trứ danh — kem trứng béo ngậy đánh thức mọi giác quan.',
                'description'       => 'Lớp kem trứng mịn màng đánh bông tan chảy trên nền cà phê đen sóng sánh — món thức uống đặc sản nổi tiếng gốc Hà Nội. Hương vị béo ngậy, ngọt thanh, thơm lừng, cực kỳ thu hút du khách và giới trẻ.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720441/ca_phe_kem_trung_w37ojw.png',
                'is_active'         => 'Đang bán',
            ],

            // ============================================================
            // CATEGORY 2: Trà & Trà Trái Cây (category_id = 2) — 10 món
            // ============================================================
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Đào Cam Sả',
                'short_description' => 'Tượng đài trà trái cây — tươi mát, thơm dịu và thư giãn.',
                'description'       => 'Trà đào cam sả kết hợp hương thơm của đào chín, vị chua ngọt của cam tươi và mùi sả thanh mát. Thức uống giải nhiệt hàng đầu, tốt cho tiêu hóa và luôn nằm trong top lựa chọn yêu thích của khách.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720640/tra_dao_cam_sa_fujzqj.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Vải',
                'short_description' => 'Vị ngọt thanh thoát, tinh tế từ vải thiều tươi.',
                'description'       => 'Trà vải thiều với vị ngọt tự nhiên của vải chín, hương thơm hoa quả thanh mát pha trên nền trà xanh nhẹ nhàng. Luôn nằm trong top lựa chọn ưu tiên của thực khách nhờ vị ngọt dịu và hương thơm dễ chịu.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720684/tra_vai_nwwaan.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Chanh',
                'short_description' => 'Món giải khát kinh điển, chi phí tối ưu, doanh thu ổn định.',
                'description'       => 'Trà chanh là thức uống kinh điển với vị chua thanh của chanh tươi hòa cùng trà xanh nhẹ, thêm đường và đá — đơn giản mà không bao giờ lỗi thời. Giải khát tức thì, phù hợp mọi lứa tuổi.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720638/tra_chanh_qm6hhc.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Tắc',
                'short_description' => 'Vị chua tươi mát bùng nổ, đánh tan cái nóng ngày hè.',
                'description'       => 'Trà tắc kết hợp trà xanh với tắc (quất) tươi vắt lấy nước chua thơm, thêm đường và đá lạnh. Một ngụm là giải ngay cái nóng bức, vị chua tươi mát kích thích vị giác, sảng khoái tức thì.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720696/tra_tac_snmjbl.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Sữa Truyền Thống',
                'short_description' => 'Vị trà đậm đà kết hợp sữa béo, hương vị vượt thời gian.',
                'description'       => 'Trà sữa truyền thống với nền hồng trà Assam đậm chất kết hợp sữa đặc béo ngậy theo tỉ lệ chuẩn, tạo nên thức uống hương vị vượt thời gian không thể thiếu trong bất kỳ thực đơn nào.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720695/tra_sua_truyen_thong_ikc7jf.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Sữa Trân Châu',
                'short_description' => 'Combo thương hiệu tạo niềm vui cho mọi khách hàng mọi lứa tuổi.',
                'description'       => 'Trà sữa trân châu với nền trà ô long thơm nồng, sữa tươi béo ngậy và trân châu đen dai mềm nấu từ bột năng. Thức uống bình dân yêu thích của mọi lứa tuổi, mang lại niềm vui mỗi ngụm uống.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720681/tra_sua_tran_chau_j7ztxf.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Matcha Latte',
                'short_description' => 'Sắc xanh thanh lịch, vị trà tinh tế và giàu dưỡng chất.',
                'description'       => 'Matcha latte sử dụng bột matcha Nhật Bản cao cấp kết hợp với sữa tươi hấp nóng, tạo nên thức uống màu xanh ngọc bắt mắt, vị đắng nhẹ hòa quyện béo thơm. Đại diện cho dòng đồ uống giàu dưỡng chất và gu thẩm mỹ.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720641/matcha_latte_bzfehs.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Dâu',
                'short_description' => 'Màu đỏ rực đầy bắt mắt, dẫn đầu xu hướng mạng xã hội.',
                'description'       => 'Trà dâu với vị chua ngọt đặc trưng của dâu tây tươi hòa cùng trà xanh nhẹ nhàng, màu đỏ hồng rực rỡ cực kỳ hút mắt trên mạng xã hội. Thức uống dẫn đầu xu hướng, đáp ứng cả khẩu vị lẫn nhu cầu "sống ảo".',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720716/tra_dau_gfdsa6.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Vải Hạt Chia',
                'short_description' => 'Phiên bản cải tiến thời thượng — ngon miệng và tốt cho sức khỏe.',
                'description'       => 'Trà vải hạt chia là phiên bản nâng cấp của trà vải truyền thống, bổ sung thêm hạt chia giàu omega-3 và chất xơ. Vừa thỏa mãn vị giác vừa đáp ứng nhu cầu sống lành mạnh của khách hàng hiện đại.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720655/tra_vai_hat_chia_rt0dla.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 2,
                'brand_id'          => 1,
                'product_name'      => 'Trà Tắc Xí Muội',
                'short_description' => 'Chua — mặn — ngọt hài hòa, kích thích vị giác, dễ gây nghiện.',
                'description'       => 'Trà tắc xí muội kết hợp vị chua của tắc tươi, vị mặn ngọt đặc trưng của xí muội trên nền trà xanh mát lạnh. Sự hài hòa ba vị kích thích vị giác mạnh mẽ, tạo nên thức uống khó cưỡng và dễ "gây nghiện".',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720727/tra_tac_xi_muoi_wzaeor.png',
                'is_active'         => 'Đang bán',
            ],

            // ============================================================
            // CATEGORY 3: Nước Ép & Sinh Tố (category_id = 3) — 6 món
            // ============================================================
            [
                'category_id'       => 3,
                'brand_id'          => 1,
                'product_name'      => 'Nước Cam Ép',
                'short_description' => 'Nguồn vitamin C tự nhiên, thanh lọc cơ thể mỗi ngày.',
                'description'       => 'Nước cam được ép từ cam sành tươi nguyên trái ngay tại quán, không pha loãng, không đường thêm. Vị ngọt tự nhiên, chua dịu, giàu vitamin C — món nước ép "quốc dân" giúp tăng sức đề kháng và thanh lọc cơ thể hiệu quả.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720911/nuoc_cam_ep_vogjhm.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 3,
                'brand_id'          => 1,
                'product_name'      => 'Nước Ép Dưa Hấu',
                'short_description' => 'Màu sắc bắt mắt, vị ngọt tự nhiên, giải nhiệt tức thì.',
                'description'       => 'Dưa hấu tươi ép lấy nước ngọt mát, màu đỏ hồng rực rỡ tự nhiên đẹp mắt. Thức uống ít calo, giàu lycopene và nước, là lựa chọn hàng đầu để giải nhiệt tức thì vào những ngày nắng nóng.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720920/nuoc_ep_dua_hau_pfldla.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 3,
                'brand_id'          => 1,
                'product_name'      => 'Nước Ép Thơm (Dứa)',
                'short_description' => 'Vị chua ngọt đậm đà, giàu lợi ích tiêu hóa.',
                'description'       => 'Nước ép thơm (dứa) tươi với vị chua ngọt đặc trưng, giàu enzyme bromelain hỗ trợ tiêu hóa và giảm viêm tự nhiên. Luôn là món ưa thích của phái đẹp nhờ lợi ích làm đẹp da và dáng.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721028/nuoc_ep_thom_dfu1ga.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 3,
                'brand_id'          => 1,
                'product_name'      => 'Nước Ép Ổi',
                'short_description' => 'Thức uống bình dân, giàu dinh dưỡng, ổn định doanh số hằng ngày.',
                'description'       => 'Nước ép ổi tươi với màu hồng nhạt tự nhiên, vị ngọt nhẹ và thơm đặc trưng của ổi chín. Giàu vitamin C, chất xơ và chất chống oxy hóa — người bạn đồng hành dinh dưỡng ổn định trong doanh số mỗi ngày.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720908/nuoc_ep_oi_jmeyco.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 3,
                'brand_id'          => 1,
                'product_name'      => 'Sinh Tố Bơ',
                'short_description' => 'Vua sinh tố — béo ngậy, sánh mịn, nguồn năng lượng tuyệt vời.',
                'description'       => 'Sinh tố bơ sử dụng bơ chín mềm xay nhuyễn với sữa tươi và đường cát, cho ra thức uống sánh mịn, màu xanh ngọc quyến rũ. Giàu chất béo lành mạnh, vitamin E và năng lượng bền vững — xứng danh vua của các loại sinh tố.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721035/sinh_to_bo_xslsdj.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 3,
                'brand_id'          => 1,
                'product_name'      => 'Sinh Tố Xoài',
                'short_description' => 'Hương vị nhiệt đới đặc trưng, vị ngọt thơm khó cưỡng.',
                'description'       => 'Sinh tố xoài dùng xoài Cát Hòa Lộc chín cây — loại xoài nổi tiếng miền Tây thơm ngọt — xay cùng sữa tươi và đá viên. Màu vàng rực rỡ, hương thơm nhiệt đới đặc trưng, làm hài lòng mọi khẩu vị.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721040/sinh_to_xoai_yq4gxo.png',
                'is_active'         => 'Đang bán',
            ],

            // ============================================================
            // CATEGORY 4: Đồ Uống Khác (category_id = 4) — 4 món
            // ============================================================
            [
                'category_id'       => 4,
                'brand_id'          => 1,
                'product_name'      => 'Sữa Chua Đánh Đá',
                'short_description' => 'Món giải nhiệt kinh điển — chua dịu, mát lạnh, bừng tỉnh vị giác.',
                'description'       => 'Sữa chua đánh đá kết hợp sữa chua nguyên chất với đá bào mịn, đánh bông nhẹ tạo nên thức uống sánh mịn, chua dịu và mát lạnh sảng khoái. Món giải nhiệt kinh điển được yêu thích mọi mùa, hỗ trợ tiêu hóa tốt.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720615/sua_chua_danh_da_tetaos.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 4,
                'brand_id'          => 1,
                'product_name'      => 'Cacao Đá (Nóng)',
                'short_description' => 'Thay thế hoàn hảo cho cà phê — đậm đà, ấm áp và đầy năng lượng.',
                'description'       => 'Cacao đá làm từ bột cacao nguyên chất, sữa tươi và syrup chocolate cao cấp, uống lạnh với đá. Đậm vị ngọt ngào và béo thơm, là lựa chọn hoàn hảo cho người không uống cà phê nhưng vẫn muốn thức uống đậm đà đầy năng lượng.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720714/cacao_da_jcjswd.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 4,
                'brand_id'          => 1,
                'product_name'      => 'Chanh Dây Đá',
                'short_description' => 'Vị chua đặc trưng đầy kích thích, làm sạch dư vị sau bữa ăn.',
                'description'       => 'Chanh dây đá kết hợp chanh dây tươi chua thơm với nước lọc, đường và đá lạnh. Vị chua đặc trưng đầy kích thích, giải nhiệt hiệu quả và làm sạch dư vị sau bữa ăn, rất thích hợp để uống kèm đồ ăn.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720670/chanh_day_da_oaoxo9.png',
                'is_active'         => 'Đang bán',
            ],
            [
                'category_id'       => 4,
                'brand_id'          => 1,
                'product_name'      => 'Soda Chanh Đường',
                'short_description' => 'Sảng khoái với sủi bọt vui tươi — đơn giản mà không bao giờ lỗi thời.',
                'description'       => 'Soda chanh đường kết hợp nước soda lạnh sủi bọt cùng chanh tươi vắt và đường, tạo ra thức uống sảng khoái, vui tươi. Đơn giản nhưng không bao giờ lỗi thời, không chứa cafein, phù hợp cho cả trẻ em và người lớn.',
                'image_url'         => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720689/soda_chanh_duong_jgmctm.png',
                'is_active'         => 'Đang bán',
            ],
        ];

        // Chạy vòng lặp để thực thi việc chèn 30 món sạch vào DB
        foreach ($products as $product) {
            Product::create([
                'category_id'       => $product['category_id'],
                'brand_id'          => $product['brand_id'],
                'product_name'      => $product['product_name'],
                'slug'              => Str::slug($product['product_name']), 
                'short_description' => $product['short_description'],
                'description'       => $product['description'],
                'image_url'         => $product['image_url'],
                'is_active'         => $product['is_active'],
            ]);
        }
    }
}