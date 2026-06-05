<script setup>
import ProductCard from '@/Components/Main/ProductCard.vue'

const drinks = [
  { 
    id: 1, 
    name: 'Velvet Latte', 
    price: '65.000đ', 
    tag: 'Best Seller', 
    tagVariant: 'tertiary',
    description: 'Sự kết hợp hoàn hảo giữa espresso đậm đà và sữa mịn màng.', 
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBnYIVuagvWZwGm_-cS5-PkD8P_e29W3Ku0yDMYDo_gCGUxQiFiG_YOjSY763srXCb2xdWAF_V9LOccPOFzz8AcmxfaMYEx3p6d9OQEZyHx09p4rM2xClVApijoLarn-D5dzD3Dg38WdOcB0Ci1qTdZ8mF2iGQZ5uW4RsJV-uZRHl8C6yuyX_GLc2fkloTZ4oT5Ue-7YSIs0F8v_vFHm3-J06SyptkfMI1HCpRp-GKB993nw48ltECIjFvFl15_xb275UVLLsMkBhs' 
  },
  { 
    id: 2, 
    name: 'Classic Espresso', 
    price: '45.000đ', 
    tag: null,
    description: 'Chiết xuất tinh túy từ hạt Arabica Cầu Đất nguyên chất.', 
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC0ROgDxflemzx77EG_feRH9j54-YWVCrCrn8vH6v7ZFSBC5cY1qiUnvc4rGb1-9weim9CG8gQUxV9ER25fmp4kFXJp5TYYU-U6xJSCN9YI7uiMgw8NQHtOy8seV-0y_mAc9X2GemnquX1L0rrRpdgcDQ9H-QCdo0mpT1YKpFYkkztkYTWRUaNCMJuYTIEkswsx7xS47jVbmONY3k8QeX2hrXgUIQKA4_Fgi20US5KWi_RauMBM-H1L-G07cghzZCq5yeaQ99EVJ5k' 
  },
  { 
    id: 3, 
    name: 'Cold Brew Cam Sả', 
    price: '55.000đ', 
    tag: 'Mới',
    tagVariant: 'primary',
    description: 'Cà phê ủ lạnh 24h kết hợp cùng vị thanh mát của cam tươi.', 
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDwp3Yuwh1x5ufA3swqMLYNKhuB6Q4qtKo08gLWDe_bxwfh9aGagWp5ARF7KPfHjUm0rJH1-DDH9kXGEAKWZ2w6befKghAmcQTPv0bnnYPLgj31brxVmJ1Wkq-hTgylwH4SXB7I5ShhKbi68i1Ap7L-JNXl8OiV-3wMeFiUWxqOXt1kohOiakwb1joksNENk1aVBtYomgj6y0Gshl9zMfoA7jmeQSHrjKHz-PJjaT_UV2zjqO9D-kIMUUObR5lCuF_VVMMoH8npCQc' 
  }
]
</script>

<template>
  <section class="py-24 px-margin-mobile md:px-gutter">
    <div class="max-w-[1280px] mx-auto">
      <!-- Section Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-12 gap-4">
        <div>
          <h2 class="text-headline-md text-primary mb-2">Thức Uống Nổi Bật</h2>
          <p class="text-body-md text-on-surface-variant">
            Những hương vị làm nên tên tuổi Cà Phê Mới.
          </p>
        </div>
        
        <!-- Navigation Arrows (Desktop) -->
        <div class="hidden md:flex gap-4">
          <button class="w-12 h-12 rounded-full border border-outline-variant flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
          </button>
          <button class="w-12 h-12 rounded-full bg-primary text-on-primary flex items-center justify-center hover:bg-primary/90 transition-colors shadow-sm">
            <span class="material-symbols-outlined">arrow_forward</span>
          </button>
        </div>
      </div>

      <!-- Products Slider -->
      <div class="flex overflow-x-auto gap-6 pb-8 hide-scrollbar -mx-margin-mobile px-margin-mobile md:mx-0 md:px-0">
        <ProductCard 
          v-for="drink in drinks" 
          :key="drink.id" 
          :product="drink" 
        />
      </div>
    </div>
  </section>
</template>