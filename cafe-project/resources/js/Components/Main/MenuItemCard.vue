<script setup>
import BaseBadge from '@/Components/Base/BaseBadge.vue'

defineProps({
  item: {
    type: Object,
    required: true,
    validator: (obj) => {
      return obj.id && obj.name && obj.price && obj.image && obj.category
    }
  }
})
</script>

<template>
  <div 
    :data-category="item.category"
    class="menu-item bg-surface rounded-xl border border-outline-variant/20 overflow-hidden group hover:shadow-[0_8px_30px_rgba(74,55,40,0.08)] transition-all duration-500 flex flex-col h-full"
  >
    <!-- Image Container -->
    <div class="aspect-[4/3] w-full relative overflow-hidden bg-surface-container-low">
      <img 
        :alt="item.name" 
        :src="item.image"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
        loading="lazy"
      />
      
      <!-- Badge (if exists) -->
      <div v-if="item.badge" class="absolute top-4 left-4">
        <BaseBadge :variant="item.badgeVariant || 'secondary'">
          {{ item.badge }}
        </BaseBadge>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6 flex flex-col flex-grow">
      <!-- Title & Price -->
      <div class="flex justify-between items-start mb-2 gap-4">
        <h3 class="font-serif text-headline-sm text-primary group-hover:text-secondary transition-colors line-clamp-1">
          {{ item.name }}
        </h3>
        <span class="font-serif text-headline-sm text-on-surface whitespace-nowrap">
          {{ item.price }}
        </span>
      </div>

      <!-- Description -->
      <p class="font-sans text-body-md text-on-surface-variant line-clamp-2 mb-6 flex-grow">
        {{ item.description }}
      </p>

      <!-- Add to Cart Button -->
      <button class="w-full py-3 rounded-full border border-secondary text-secondary hover:bg-secondary hover:text-on-secondary font-sans text-label-md transition-colors duration-300 flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-lg">add</span>
        Thêm vào giỏ
      </button>
    </div>
  </div>
</template>

<style scoped>
.menu-item {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.menu-item.hidden-item {
  opacity: 0;
  transform: scale(0.95);
  pointer-events: none;
  position: absolute;
  visibility: hidden;
}
</style>