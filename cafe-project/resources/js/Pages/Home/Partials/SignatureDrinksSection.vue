<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import MenuItemCard from '@/Components/Main/MenuItemCard.vue'
const props = defineProps({
  drinks: {
    type: Array,
    required: true,
    default: () => []
  }
})
const sliderRef = ref(null)
const currentIndex = ref(0)
const maxIndex = computed(() => Math.max(0, props.drinks.length - (isMobile.value ? 1 : 3)))
const isMobile = ref(false)
let touchStartX = 0
let touchEndX = 0
const checkViewport = () => {
  isMobile.value = window.innerWidth < 768
}
onMounted(() => {
  checkViewport()
  window.addEventListener('resize', checkViewport)
})
onUnmounted(() => {
  window.removeEventListener('resize', checkViewport)
})
const nextSlide = () => {
  if (currentIndex.value < maxIndex.value) {
    currentIndex.value++
  } else {
    currentIndex.value = 0
  }
}
const prevSlide = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  } else {
    currentIndex.value = maxIndex.value
  }
}
const handleTouchStart = (e) => {
  touchStartX = e.changedTouches[0].screenX
}
const handleTouchEnd = (e) => {
  touchEndX = e.changedTouches[0].screenX
  handleSwipe()
}
const handleSwipe = () => {
  const threshold = 50
  if (touchStartX - touchEndX > threshold) {
    nextSlide()
  } else if (touchEndX - touchStartX > threshold) {
    prevSlide()
  }
}
</script>
<template>
  <section class="py-24 px-margin-mobile md:px-gutter bg-surface overflow-hidden">
    <div class="max-w-[1280px] mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-12 gap-6">
        <div>
          <span class="text-secondary font-sans text-label-md uppercase tracking-wider block mb-2">Hương vị trứ danh</span>
          <h2 class="text-headline-md text-primary font-bold font-serif">Món Ngon Đặc Trưng</h2>
          <p class="text-body-md text-on-surface-variant max-w-xl mt-1">
            Những sáng tạo đồ uống độc quyền được pha chế chỉn chu để lại ấn tượng sâu đậm từ ngụm đầu tiên.
          </p>
        </div>
        <div class="flex gap-3">
          <button 
            @click="prevSlide"
            class="w-12 h-12 rounded-full border border-outline-variant flex items-center justify-center text-primary hover:bg-surface-container hover:border-primary transition-all duration-300"
            aria-label="Sản phẩm trước"
          >
            <span class="material-symbols-outlined text-xl">arrow_back</span>
          </button>
          <button 
            @click="nextSlide"
            class="w-12 h-12 rounded-full bg-primary text-on-primary flex items-center justify-center hover:bg-primary/90 hover:scale-105 active:scale-95 transition-all duration-300 shadow-soft"
            aria-label="Sản phẩm tiếp theo"
          >
            <span class="material-symbols-outlined text-xl">arrow_forward</span>
          </button>
        </div>
      </div>
      <div 
        ref="sliderRef"
        class="relative w-full cursor-grab active:cursor-grabbing"
        @touchstart="handleTouchStart"
        @touchend="handleTouchEnd"
      >
        <div class="overflow-hidden -mx-4 px-4 md:mx-0 md:px-0">
          <div 
            class="flex gap-6 transition-transform duration-500 ease-out"
            :style="{ transform: `translateX(-${currentIndex * (isMobile ? 100 : 34)}%)` }"
          >
            <div 
              v-for="drink in drinks" 
              :key="drink.id" 
              class="w-[85vw] md:w-[calc(33.333%-16px)] shrink-0 transition-opacity duration-300"
            >
              <div class="h-full transform hover:-translate-y-2 transition-transform duration-300">
                <MenuItemCard :item="drink" class="h-full shadow-soft hover:shadow-lg border border-outline-variant/20 bg-surface-container-lowest" />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="flex justify-center gap-2 mt-8 md:hidden">
        <span 
          v-for="(_, idx) in drinks.length" 
          :key="idx" 
          :class="[
            'h-1.5 rounded-full transition-all duration-300',
            currentIndex === idx ? 'w-6 bg-primary' : 'w-1.5 bg-outline-variant/50'
          ]"
        ></span>
      </div>
    </div>
  </section>
</template>
