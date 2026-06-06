<script setup>
import MenuItemCard from './MenuItemCard.vue'

defineProps({
  items: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <div class="relative min-h-[400px]">
    <!-- Loading State -->
    <div 
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center bg-surface/50 backdrop-blur-sm z-10 rounded-xl"
    >
      <div class="flex flex-col items-center gap-4">
        <div class="w-12 h-12 border-4 border-primary-container border-t-primary rounded-full animate-spin"></div>
        <p class="font-sans text-body-md text-on-surface-variant">Đang tải...</p>
      </div>
    </div>

    <!-- Grid Items -->
    <TransitionGroup 
      name="menu-list"
      tag="div"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8"
    >
      <MenuItemCard 
        v-for="item in items" 
        :key="item.id" 
        :item="item"
      />
    </TransitionGroup>

    <!-- Empty State -->
    <div 
      v-if="!loading && items.length === 0" 
      class="flex flex-col items-center justify-center py-20 text-center"
    >
      <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">search_off</span>
      <h3 class="font-serif text-headline-sm text-on-surface-variant mb-2">Không tìm thấy món nào</h3>
      <p class="font-sans text-body-md text-outline">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
    </div>
  </div>
</template>

<style scoped>
.menu-list-enter-active {
  transition: all 0.4s ease-out;
}

.menu-list-leave-active {
  transition: all 0.3s ease-in;
  position: absolute;
}

.menu-list-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.menu-list-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.menu-list-move {
  transition: transform 0.4s ease;
}
</style>