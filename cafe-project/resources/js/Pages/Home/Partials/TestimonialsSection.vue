<script setup>
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'

const testimonials = [
  {
    name: 'Khánh Linh',
    role: 'Nhà thiết kế đồ họa',
    content: 'Không gian tĩnh lặng, Velvet Latte cực ngon với lớp bọt sữa béo dịu vừa phải. Cách quét mã đặt món tại bàn cũng cực kỳ tiện lợi, mình làm việc không lo bị ngắt quãng.',
    rating: 5,
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150'
  },
  {
    name: 'Minh Hoàng',
    role: 'Lập trình viên tự do',
    content: 'Cold Brew Cam Sả ở đây có hương vị cực kì nguyên bản và sảng khoái, không hề bị đắng chát. Nhân viên pha chế rất nhiệt tình tư vấn mức độ ngọt phù hợp.',
    rating: 5,
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150'
  },
  {
    name: 'Phương Thảo',
    role: 'Nhà văn tự do',
    content: 'Mình thích nhất những buổi sáng ngồi bên hiên ngắm phố phường tại Cà Phê Mới. Một ly Espresso mộc kèm một cuốn sách là trọn vẹn cả ngày nghỉ ý nghĩa.',
    rating: 5,
    avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150'
  }
]
</script>

<template>
  
  <section class="py-24 px-margin-mobile md:px-gutter bg-surface-container-low/40 border-t border-outline-variant/10">
    <div class="max-w-[1280px] mx-auto">
      
      <!-- Header -->
      <div class="text-center mb-16 max-w-2xl mx-auto">
        <span class="text-secondary font-sans text-label-md uppercase tracking-widest block mb-2">Đồng điệu tâm hồn</span>
        <h2 class="text-headline-md text-primary font-bold font-serif">Khách Hàng Nói Gì Về Chúng Tôi</h2>
        <p class="text-body-md text-on-surface-variant mt-2">
          Hàng ngàn tách cà phê được trao đi mỗi ngày là bấy nhiêu tình cảm và sự gắn kết chân thành nhất.
        </p>
      </div>

      <!-- Testimonials Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div 
          v-for="(item, idx) in testimonials" 
          :key="item.name"
          class="glass-panel p-8 rounded-xl shadow-soft flex flex-col justify-between hover:scale-[1.02] transition-transform duration-300"
        >
          <div>
            <!-- Stars -->
            <div class="flex gap-1 mb-6 text-secondary">
              <span v-for="star in item.rating" :key="star"  class="material-symbols-outlined text-sm font-fill">star</span>
            </div>
            <!-- Feedback -->
            <p class="text-body-md text-on-surface/90 italic leading-relaxed mb-6">
              "{{ item.content }}"
            </p>
          </div>

          <!-- User Info -->
          <div class="flex items-center gap-4 pt-4 border-t border-outline-variant/30">
            <img 
              :src="item.avatar" 
              :alt="item.name" 
              class="w-12 h-12 rounded-full object-cover border border-secondary-container"
              loading="lazy"
            />
            <div>
              <h4 class="text-label-md text-primary font-bold">{{ item.name }}</h4>
              <p class="text-label-sm text-on-surface-variant">{{ item.role }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<style scoped>



.material-symbols-outlined {
  font-variation-settings:
  'FILL' 1,
  'wght' 400,
  'GRAD' 0,
  'opsz' 24
}

</style>