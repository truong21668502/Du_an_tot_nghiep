<script setup>
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
defineProps({
  todayReservations: {
    type: Array,
    default: () => []
  }
})
</script>
<template>
  <section class="relative py-24 px-margin-mobile md:px-gutter bg-surface-container-low">
    <div class="max-w-[1280px] mx-auto">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="text-center mb-8">
          <span
            class="inline-block px-4 py-2 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-sm mb-6">
            Đặt bàn
          </span>
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-4">
            Chọn Bàn Của Bạn
          </h1>
          <p class="text-body-lg text-on-surface-variant max-w-2xl mx-auto">
            Đặt trước bàn yêu thích để tận hưởng không gian ấm cúng và những tách cà phê thủ công tinh tế.
          </p>
        </div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="200">
        <div class="flex flex-wrap justify-center gap-6 mb-4">
          <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-full bg-tertiary-container/30 border border-tertiary"></span>
            <span class="font-sans text-label-sm text-on-surface-variant">Trống</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-full bg-error-container/30 border border-error"></span>
            <span class="font-sans text-label-sm text-on-surface-variant">Đang sử dụng</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-full bg-secondary-container/30 border border-secondary"></span>
            <span class="font-sans text-label-sm text-on-surface-variant">Đã đặt trước</span>
          </div>
        </div>
        <!-- <p class="text-center font-sans text-label-sm text-on-surface-variant">
          Hôm nay có {{ todayReservations.length }} lượt đặt bàn
        </p> -->
      </AnimateOnScroll>
    </div>
  </section>
</template>
