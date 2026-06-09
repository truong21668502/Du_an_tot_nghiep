<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductImage;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // CATEGORY 1: Cà Phê (product_id từ 1 đến 10)
        // ============================================================
        
        // --- product_id = 1 | Cà Phê Đen Đá ---
        ProductImage::create(['product_id' => 1, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780814444/ca_phe_den_da_1_olgc7b.jpg']);
        ProductImage::create(['product_id' => 1, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720424/ca_phe_den_da_2_tpuogw.webp']);
        ProductImage::create(['product_id' => 1, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720423/ca_phe_den_da_3_bikwpl.webp']);

        // --- product_id = 2 | Cà Phê Sữa Đá ---
        ProductImage::create(['product_id' => 2, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720430/ca_phe_sua_da_1_icgo7p.webp']);
        ProductImage::create(['product_id' => 2, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720431/ca_phe_sua_da_2_soch4l.webp']);
        ProductImage::create(['product_id' => 2, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720432/ca_phe_sua_da_3_ijmy2z.webp']);

        // --- product_id = 3 | Bạc Xỉu ---
        ProductImage::create(['product_id' => 3, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720387/bac_xiu_1_gtkqxy.webp']);
        ProductImage::create(['product_id' => 3, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780814337/bac_xiu_2_tvl8tv.jpg']);
        ProductImage::create(['product_id' => 3, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720387/bac_xiu_3_w6sjxb.webp']);

        // --- product_id = 4 | Cà Phê Muối ---
        ProductImage::create(['product_id' => 4, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720427/ca_phe_muoi_1_qqooyp.webp']);
        ProductImage::create(['product_id' => 4, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720428/ca_phe_muoi_2_w67c5d.webp']);
        ProductImage::create(['product_id' => 4, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720429/ca_phe_muoi_3_lzejmp.webp']);

        // --- product_id = 5 | Cà Phê Cốt Dừa ---
        ProductImage::create(['product_id' => 5, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720402/ca_phe_cot_dua_1_efn9sr.webp']);
        ProductImage::create(['product_id' => 5, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720401/ca_phe_cot_dua_2_m4p1xu.webp']);
        ProductImage::create(['product_id' => 5, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720401/ca_phe_cot_dua_3_olcjht.webp']);

        // --- product_id = 6 | Cà Phê Sữa Tươi ---
        ProductImage::create(['product_id' => 6, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720433/ca_phe_sua_tuoi_1_ywagyn.webp']);
        ProductImage::create(['product_id' => 6, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720433/ca_phe_sua_tuoi_2_tlayad.webp']);
        ProductImage::create(['product_id' => 6, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720434/ca_phe_sua_tuoi_3_nkcyvo.webp']);

        // --- product_id = 7 | Americano ---
        ProductImage::create(['product_id' => 7, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720346/americano_1_tfl758.webp']);
        ProductImage::create(['product_id' => 7, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720346/americano_2_uuoi0x.webp']);
        ProductImage::create(['product_id' => 7, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720347/americano_3_ormze9.webp']);

        // --- product_id = 8 | Latte ---
        ProductImage::create(['product_id' => 8, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720439/latte_1_pljbtw.webp']);
        ProductImage::create(['product_id' => 8, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720438/latte_2_xiccoo.webp']);
        ProductImage::create(['product_id' => 8, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720440/latte_3_z1zwt5.webp']);

        // --- product_id = 9 | Cappuccino ---
        ProductImage::create(['product_id' => 9, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720435/cappuccino_1_q3o0oz.webp']);
        ProductImage::create(['product_id' => 9, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720437/cappuccino_2_a97am0.webp']);
        ProductImage::create(['product_id' => 9, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720436/cappuccino_3_ixlsef.webp']);

        // --- product_id = 10 | Cà Phê Kem Trứng ---
        ProductImage::create(['product_id' => 10, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780757564/ca_phe_kem_trung_1_rzhghb.jpg']);
        ProductImage::create(['product_id' => 10, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780757565/ca_phe_kem_trung_2_bpzjhf.jpg']);
        ProductImage::create(['product_id' => 10, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780757565/ca_phe_kem_trung_3_tijeom.jpg']);


        // ============================================================
        // CATEGORY 2: Trà & Trà Trái Cây (product_id từ 11 đến 20)
        // ============================================================
        
        // --- product_id = 11 | Trà Đào Cam Sả ---
        ProductImage::create(['product_id' => 11, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720679/tra_dao_cam_sa_1_snwrut.png']);
        ProductImage::create(['product_id' => 11, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720691/tra_dao_cam_sa_2_f7davu.png']);
        ProductImage::create(['product_id' => 11, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720730/tra_dao_cam_sa_3_ihwulh.png']);

        // --- product_id = 12 | Trà Vải ---
        ProductImage::create(['product_id' => 12, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720720/tra_vai_1_y1fa3d.png']);
        ProductImage::create(['product_id' => 12, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720698/tra_vai_2_ccxdve.png']);
        ProductImage::create(['product_id' => 12, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720707/tra_vai_3_vr4jrb.png']);

        // --- product_id = 13 | Trà Chanh ---
        ProductImage::create(['product_id' => 13, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720638/tra_chanh_1_jylxog.png']);
        ProductImage::create(['product_id' => 13, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720704/tra_chanh_2_dzo38f.png']);
        ProductImage::create(['product_id' => 13, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720634/tra_chanh_3_lxi3z8.png']);

        // --- product_id = 14 | Trà Tắc ---
        ProductImage::create(['product_id' => 14, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720684/tra_tac_1_vbdut6.png']);
        ProductImage::create(['product_id' => 14, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720686/tra_tac_2_k71iqc.png']);
        ProductImage::create(['product_id' => 14, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720724/tra_tac_3_kbnswy.png']);

        // --- product_id = 15 | Trà Sữa Truyền Thống ---
        ProductImage::create(['product_id' => 15, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720707/tra_sua_truyen_thong_1_egmxjl.png']);
        ProductImage::create(['product_id' => 15, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720661/tra_sua_truyen_thong_2_zabrup.png']);
        ProductImage::create(['product_id' => 15, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720668/tra_sua_truyen_thong_3_vraunx.png']);

        // --- product_id = 16 | Trà Sữa Trân Châu ---
        ProductImage::create(['product_id' => 16, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720671/tra_sua_tran_chau_1_wowcmz.png']);
        ProductImage::create(['product_id' => 16, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720644/tra_sua_tran_chau_2_enzd9s.png']);
        ProductImage::create(['product_id' => 16, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720666/tra_sua_tran_chau_3_cfpils.png']);

        // --- product_id = 17 | Matcha Latte ---
        ProductImage::create(['product_id' => 17, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720618/matcha_latte_1_avfudb.png']);
        ProductImage::create(['product_id' => 17, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720633/matcha_latte_2_rwjd0j.png']);
        ProductImage::create(['product_id' => 17, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720633/matcha_latte_3_wbeycy.png']);

        // --- product_id = 18 | Trà dâu ---
        ProductImage::create(['product_id' => 18, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720689/tra_dau_1_bl7fdd.png']);
        ProductImage::create(['product_id' => 18, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720677/tra_dau_2_zlet7d.png']);
        ProductImage::create(['product_id' => 18, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720701/tra_dau_3_csdltm.png']);

        // --- product_id = 19 | Trà vải hạt chia ---
        ProductImage::create(['product_id' => 19, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720674/tra_vai_hat_chia_1_pney8l.png']);
        ProductImage::create(['product_id' => 19, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720712/tra_vai_hat_chia_2_cfv75b.png']);
        ProductImage::create(['product_id' => 19, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720719/tra_vai_hat_chia_3_bbkj3c.png']);

        // --- product_id = 20 | Trà tắc xí muội ---
        ProductImage::create(['product_id' => 20, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720710/tra_tac_xi_muoi_1_tbv0wm.png']);
        ProductImage::create(['product_id' => 20, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720726/tra_tac_xi_muoi_2_wng6xi.png']);
        ProductImage::create(['product_id' => 20, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720692/tra_tac_xi_muoi_3_jls3lt.png']);


        // ============================================================
        // CATEGORY 3: Nước Ép & Sinh Tố (product_id từ 21 đến 26)
        // ============================================================
        
        // --- product_id = 21 | Nước Cam Ép ---
        ProductImage::create(['product_id' => 21, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720913/nuoc_cam_ep_1_gu2vyi.png']);
        ProductImage::create(['product_id' => 21, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720916/nuoc_cam_ep_2_z40ds9.png']);
        ProductImage::create(['product_id' => 21, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720918/nuoc_cam_ep_3_sjedwp.png']);

        // --- product_id = 22 | Nước Ép Dưa Hấu ---
        ProductImage::create(['product_id' => 22, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720922/nuoc_ep_dua_hau_1_w2gsaf.png']);
        ProductImage::create(['product_id' => 22, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720924/nuoc_ep_dua_hau_2_kajjdz.png']);
        ProductImage::create(['product_id' => 22, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720927/nuoc_ep_dua_hau_3_gquw3a.png']);

        // --- product_id = 23 | Nước Ép Thơm (Dứa) ---
        ProductImage::create(['product_id' => 23, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721030/nuoc_ep_thom_1_xlsdin.png']);
        ProductImage::create(['product_id' => 23, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721033/nuoc_ep_thom_2_e8qa46.png']);
        ProductImage::create(['product_id' => 23, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721026/nuoc_ep_thom_3_muzxd0.png']);

        // --- product_id = 24 | Nước Ép Ổi ---
        ProductImage::create(['product_id' => 24, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720929/nuoc_ep_oi_1_ziy1zc.png']);
        ProductImage::create(['product_id' => 24, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780756847/nuoc_ep_oi_2_avxbex.jpg']);
        ProductImage::create(['product_id' => 24, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720934/nuoc_ep_oi_3_jgih7k.png']);

        // --- product_id = 25 | Sinh Tố Bơ ---
        ProductImage::create(['product_id' => 25, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721038/sinh_to_bo_1_nbm704.png']);
        ProductImage::create(['product_id' => 25, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721042/sinh_to_bo_2_m9q675.png']);
        ProductImage::create(['product_id' => 25, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721048/sinh_to_bo_3_tj46c1.png']);

        // --- product_id = 26 | Sinh Tố Xoài ---
        ProductImage::create(['product_id' => 26, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721045/sinh_to_xoai_1_jmrwz9.png']);
        ProductImage::create(['product_id' => 26, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721051/sinh_to_xoai_2_kqtrje.png']);
        ProductImage::create(['product_id' => 26, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780721053/sinh_to_xoai_3_vmoxaj.png']);


        // ============================================================
        // CATEGORY 4: Đồ Uống Khác (product_id từ 27 đến 30)
        // ============================================================
        
        // --- product_id = 27 | Sữa Chua Đánh Đá ---
        ProductImage::create(['product_id' => 27, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720612/sua_chua_danh_da_1_df2x5r.webp']);
        ProductImage::create(['product_id' => 27, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720611/sua_chua_danh_da_2_eoauxw.webp']);
        ProductImage::create(['product_id' => 27, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720616/sua_chua_danh_da_3_dru7lw.webp']);

        // --- product_id = 28 | CaCao Đá/Nóng ---
        ProductImage::create(['product_id' => 28, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720601/cacao_da_1_s5xdxi.webp']);
        ProductImage::create(['product_id' => 28, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720603/cacao_da_2_rhyev3.webp']);
        ProductImage::create(['product_id' => 28, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720605/cacao_da_3_p0o6uj.webp']);

        // --- product_id = 29 | Chanh Dây Đá ---
        ProductImage::create(['product_id' => 29, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720606/chanh_day_da_1_i00b57.webp']);
        ProductImage::create(['product_id' => 29, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720619/chanh_day_da_2_qhjv2s.webp']);
        ProductImage::create(['product_id' => 29, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720622/chanh_day_da_3_gq3bdt.webp']);

        // --- product_id = 30 | Soda Chanh Đường ---
        ProductImage::create(['product_id' => 30, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720608/soda_chanh_duong_1_grslen.webp']);
        ProductImage::create(['product_id' => 30, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720621/soda_chanh_duong_2_mkdfod.webp']);
        ProductImage::create(['product_id' => 30, 'image_url' => 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780720609/soda_chanh_duong_3_ks9aeo.webp']);
    }
}