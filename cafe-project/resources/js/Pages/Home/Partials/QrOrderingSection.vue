<script setup>
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'

const qrSteps = [
  { 
    icon: 'qr_code_scanner', 
    title: '1. Quét Mã Bàn', 
    desc: 'Bật ứng dụng ảnh trên điện thoại và quét nhanh mã QR tại bàn ngồi của bạn.' 
  },
  { 
    icon: 'restaurant_menu', 
    title: '2. Đặt Món Trực Tuyến', 
    desc: 'Duyệt menu ảnh sống động, dễ dàng chọn độ ngọt, lượng đá hay toppings tùy ý.' 
  },
  { 
    icon: 'local_cafe', 
    title: '3. Phục Vụ Tận Nơi', 
    desc: 'Xác nhận món và thanh toán tiện lợi. Đồ uống của bạn sẽ được mang ra ngay.' 
  }
]
</script>

<template>
  <section class="py-24 px-margin-mobile md:px-gutter bg-surface-container-low/60 border-y border-outline-variant/20 relative">
    <!-- Nền chấm bi trang trí cổ điển -->
    <div class="absolute inset-0 bg-[radial-gradient(#665d50_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.03] pointer-events-none"></div>

    <div class="max-w-[1280px] mx-auto relative z-10">
      <!-- Section Header -->
      <div class="text-center mb-16 max-w-2xl mx-auto">
        <span class="text-secondary font-sans text-label-md uppercase tracking-widest block mb-3">Tốc độ & Tiện lợi</span>
        <h2 class="text-headline-md text-primary font-bold mb-4 font-serif">
          Đặt Món Tại Bàn Trong 3 Bước
        </h2>
        <p class="text-body-md text-on-surface-variant">
          Không cần xếp hàng chờ đợi, trải nghiệm gọi đồ uống chưa bao giờ thảnh thơi và hiện đại đến thế tại không gian Cà Phê Mới.
        </p>
      </div>

      <!-- Steps Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
        <!-- Đường nối ngang (chỉ hiển thị trên máy tính) -->
        <div class="hidden md:block absolute top-1/3 left-1/6 w-2/3 h-[1px] bg-gradient-to-r from-transparent via-outline-variant/60 to-transparent -z-10"></div>
        
        <!-- Step Cards -->
        <div 
          v-for="(step, idx) in qrSteps" 
          :key="step.title" 
          class="glass-panel p-8 rounded-xl flex flex-col items-center text-center shadow-soft hover:shadow-md transition-all duration-300 group hover:-translate-y-1"
        >
          <div class="w-16 h-16 bg-surface-container-highest/60 rounded-full flex items-center justify-center mb-6 shadow-sm border border-outline-variant/40 text-secondary transition-transform duration-500 group-hover:scale-110">
            <span class="material-symbols-outlined text-3xl font-light">{{ step.icon }}</span>
          </div>
          <h3 class="text-headline-sm text-primary mb-3 font-serif font-medium">{{ step.title }}</h3>
          <p class="text-body-md text-on-surface-variant leading-relaxed">{{ step.desc }}</p>
        </div>
      </div>
    </div>
  </section>
</template>