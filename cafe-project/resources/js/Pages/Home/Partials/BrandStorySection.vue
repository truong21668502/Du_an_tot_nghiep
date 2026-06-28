<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
</script>

<template>
  <section class="py-24 px-margin-mobile md:px-gutter bg-background relative overflow-hidden">
    <!-- Vòng tròn vàng trừu tượng decor sang trọng -->
    <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full border border-secondary-container/20 -z-0"></div>

    <div class="max-w-7xl mx-auto relative z-10">
      <div class="bg-primary-container rounded-2xl overflow-hidden shadow-soft flex flex-col lg:flex-row items-stretch border border-outline-variant/30">
        
        <!-- Cột Nội Dung Trái -->
        <div class="w-full lg:w-1/2 p-8 md:p-16 flex flex-col justify-center items-start">
          <AnimateOnScroll animation="fade-right" :delay="150">
            <span class="text-secondary font-sans text-label-md uppercase tracking-wider block mb-3 font-semibold">
              Giao cà phê tươi tận nơi
            </span>
          </AnimateOnScroll>

          <AnimateOnScroll animation="fade-right" :delay="300">
            <h2 class="text-headline-md text-on-primary-container font-bold mb-4 font-serif leading-tight">
              Khởi Đầu Ngày Mới Với Cà Phê Mộc Rang Mới Mỗi Ngày
            </h2>
          </AnimateOnScroll>

          <AnimateOnScroll animation="fade-right" :delay="450">
            <p class="text-body-md text-on-primary-container/80 mb-8 max-w-lg leading-relaxed">
              Bạn say mê hương vị của Cà Phê Mới? Giờ đây bạn có thể đặt mua hạt cà phê rang nguyên bản hoặc bột xay mịn để tự pha chế chuẩn vị ngay tại tổ ấm hay văn phòng của mình. Đóng gói chất lượng, bảo lưu trọn vẹn lớp dầu cà phê thơm lừng.
            </p>
          </AnimateOnScroll>

          <AnimateOnScroll animation="fade-right" :delay="600">
            <div class="flex flex-wrap gap-4">
              <BaseButton variant="primary" class="!bg-secondary !text-on-secondary hover:!bg-secondary/90 transition-all">
                Mua Hạt Rang Ngay
              </BaseButton>
              <BaseButton variant="secondary" class="border border-on-primary-container/20 text-on-primary-container hover:bg-surface/10 transition-all">
                Tìm hiểu Quy trình Rang
              </BaseButton>
            </div>
          </AnimateOnScroll>
        </div>
        
        <!-- Cột Hình Ảnh Phải -->
        <div class="w-full lg:w-1/2 h-80 lg:h-auto min-h-[400px] relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-t lg:bg-gradient-to-r from-primary-container/60 to-transparent z-10 pointer-events-none"></div>
          <img 
            alt="Nghệ thuật rang và bảo quản hạt cà phê mộc" 
            class="w-full h-full object-cover transform hover:scale-105 transition-transform duration-[4000ms]" 
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBc5gGat6KGg5Frk9AyGVuzWHh2fhv6s0dv1okXFbq-CByXlSt76QtYK47ULBFwhM1yPj0i0k017UTcPuwrZVk1ygrUJqzcTjfKBo2kS5rtT2_DaiU_M2kMOTPLcedIZGGAew5Sd8klWtj37OKU89hF_Y38pNfSxxY9vTT2E6b6Rt_CCaWSSUhdNav2pgAyoY3-sxo5VkHVFwdD_5B7r92N68Kq2mhj6u6eW3NVUla0LFZPgSCNMW862p8DqJrdbNSPYkRwxJLJjhQ"
          />
        </div>

      </div>
    </div>
  </section>
</template>