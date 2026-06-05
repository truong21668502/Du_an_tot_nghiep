<script setup>
import BaseBadge from '@/Components/Base/BaseBadge.vue'

defineProps({
  product: {
    type: Object,
    required: true,
    validator: (obj) => {
      return obj.id && obj.name && obj.price && obj.image
    }
  }
})
</script>

<template>
  <div class="min-w-[280px] md:min-w-[320px] bg-surface rounded-xl overflow-hidden border border-outline-variant/30 shadow-[0_4px_20px_rgba(74,55,40,0.03)] group flex flex-col">
    <!-- Product Image -->
    <div class="h-64 overflow-hidden relative">
      <img 
        :src="product.image" 
        :alt="product.name" 
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        loading="lazy"
      />
      <div v-if="product.tag" class="absolute top-4 left-4">
        <BaseBadge :variant="product.tagVariant || 'tertiary'">
          {{ product.tag }}
        </BaseBadge>
      </div>
    </div>

    <!-- Product Info -->
    <div class="p-6 flex flex-col flex-grow">
      <div class="flex justify-between items-start mb-2 gap-4">
        <h3 class="font-serif text-headline-sm text-on-surface text-[20px] line-clamp-1">
          {{ product.name }}
        </h3>
        <span class="font-sans text-label-md text-primary font-bold whitespace-nowrap">
          {{ product.price }}
        </span>
      </div>
      
      <p v-if="product.description" class="font-sans text-body-md text-on-surface-variant text-sm mb-6 line-clamp-2 flex-grow">
        {{ product.description }}
      </p>
      
      <!-- CTA Button -->
      <button class="w-full py-3 bg-secondary-container/50 text-on-secondary-container rounded-full font-sans text-label-md hover:bg-secondary-container transition-colors flex items-center justify-center gap-2 mt-auto">
        <span class="material-symbols-outlined text-lg">add</span>
        Thêm vào giỏ
      </button>
    </div>
  </div>
</template>