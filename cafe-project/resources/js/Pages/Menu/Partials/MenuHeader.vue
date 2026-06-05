<script setup>
import CategoryFilter from '@/Components/Main/CategoryFilter.vue'

defineProps({
  categories: {
    type: Array,
    required: true
  },
  activeFilter: {
    type: String,
    default: 'all'
  }
})

defineEmits(['update:activeFilter'])
</script>

<template>
  <div class="flex flex-col md:flex-row justify-between items-end gap-8 mb-16">
    <!-- Title & Description -->
    <div class="max-w-2xl">
      <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-4">
        Thực đơn tinh hoa
      </h1>
      <p class="text-body-lg text-on-surface-variant">
        Khám phá bộ sưu tập đồ uống và bánh ngọt thủ công được chế tác tỉ mỉ từ những nguyên liệu hảo hạng nhất, mang đến trải nghiệm vị giác nguyên bản và tinh tế.
      </p>
    </div>

    <!-- Category Filters -->
    <CategoryFilter 
      :categories="categories"
      :model-value="activeFilter"
      @update:model-value="$emit('update:activeFilter', $event)"
    />
  </div>
</template>