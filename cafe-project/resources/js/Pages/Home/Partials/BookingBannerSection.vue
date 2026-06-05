<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'
</script>

<template>
  <section class="py-16 px-margin-mobile md:px-gutter">
    <div class="max-w-7xl mx-auto">
      <div class="bg-primary-container rounded-xl overflow-hidden relative shadow-soft flex flex-col md:flex-row items-center">
        <!-- Left Content -->
        <div class="w-full md:w-1/2 p-8 md:p-16 relative z-10">
          <h2 class="text-headline-md text-on-primary-container mb-4">
            Góc Nhỏ Của Riêng Bạn
          </h2>
          <p class="text-body-md text-on-primary-container/80 mb-8 max-w-md">
            Đặt trước một chỗ ngồi ưng ý để làm việc, đọc sách hay đơn giản là tận hưởng không gian yên tĩnh của Cà Phê Mới.
          </p>
          <BaseButton variant="inverted">Đặt Bàn Ngay</BaseButton>
        </div>
        
        <!-- Right Image -->
        <div class="w-full md:w-1/2 h-64 md:h-96 absolute md:relative right-0 top-0 opacity-40 md:opacity-100">
          <img 
            alt="Cafe Interior" 
            class="w-full h-full object-cover" 
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBc5gGat6KGg5Frk9AyGVuzWHh2fhv6s0dv1okXFbq-CByXlSt76QtYK47ULBFwhM1yPj0i0k017UTcPuwrZVk1ygrUJqzcTjfKBo2kS5rtT2_DaiU_M2kMOTPLcedIZGGAew5Sd8klWtj37OKU89hF_Y38pNfSxxY9vTT2E6b6Rt_CCaWSSUhdNav2pgAyoY3-sxo5VkHVFwdD_5B7r92N68Kq2mhj6u6eW3NVUla0LFZPgSCNMW862p8DqJrdbNSPYkRwxJLJjhQ"
          />
        </div>
      </div>
    </div>
  </section>
</template>