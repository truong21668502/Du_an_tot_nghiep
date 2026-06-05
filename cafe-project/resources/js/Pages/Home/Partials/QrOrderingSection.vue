<script setup>
const qrSteps = [
  { 
    icon: 'qr_code_scanner', 
    title: '1. Quét Mã Bàn', 
    desc: 'Mở camera và quét mã QR được đặt sẵn trên bàn của bạn.' 
  },
  { 
    icon: 'restaurant_menu', 
    title: '2. Chọn Món', 
    desc: 'Duyệt qua thực đơn hình ảnh trực quan và tùy chỉnh thức uống.' 
  },
  { 
    icon: 'coffee', 
    title: '3. Thưởng Thức', 
    desc: 'Thanh toán trực tuyến và thức uống sẽ được mang đến tận bàn.' 
  }
]
</script>

<template>
  <section class="py-24 px-margin-mobile md:px-gutter bg-surface-container-low">
    <div class="max-w-[1280px] mx-auto">
      <!-- Section Header -->
      <div class="text-center mb-16">
        <h2 class="text-headline-md text-primary mb-4">
          Trải Nghiệm Đặt Món Tiện Lợi
        </h2>
        <p class="text-body-md text-on-surface-variant max-w-2xl mx-auto">
          Không cần xếp hàng. Chỉ cần quét mã tại bàn và tận hưởng không gian.
        </p>
      </div>

      <!-- Steps Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
        <!-- Connecting Line (Desktop) -->
        <div class="hidden md:block absolute top-1/2 left-0 w-full h-[1px] bg-outline-variant/50 -z-10"></div>
        
        <!-- Step Cards -->
        <div 
          v-for="step in qrSteps" 
          :key="step.title" 
          class="glass-panel p-8 rounded-xl flex flex-col items-center text-center shadow-soft"
        >
          <div class="w-16 h-16 bg-surface rounded-full flex items-center justify-center mb-6 shadow-sm border border-outline-variant/30 text-secondary">
            <span class="material-symbols-outlined text-3xl font-normal">{{ step.icon }}</span>
          </div>
          <h3 class="text-headline-sm text-on-surface mb-2">{{ step.title }}</h3>
          <p class="text-body-md text-on-surface-variant">{{ step.desc }}</p>
        </div>
      </div>
    </div>
  </section>
</template>