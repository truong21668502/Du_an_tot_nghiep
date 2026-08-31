<script setup>
import SortSelect from '@/Components/Main/SortSelect.vue'
import CategoryFilter from '@/Components/Main/CategoryFilter.vue'

defineProps({
  categories: {
    type: Array,
    required: true
  },
  sortOptions: {
    type: Array,
    required: true
  },
  filters: {
    type: Object,
    required: true
  }
})

defineEmits([
  'update:category',
  'update:sortBy',
  'toggle-filter'
])
</script>

<template>
  <div class="space-y-8 mb-12">
    <!-- Title & Description -->
    <div class="max-w-2xl">
      <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-4">
        Thực đơn tinh hoa
      </h1>
      <p class="text-body-lg text-on-surface-variant">
        Khám phá bộ sưu tập đồ uống và bánh ngọt thủ công được chế tác tỉ mỉ từ những nguyên liệu hảo hạng nhất.
      </p>
    </div>

    <!-- Category & Sort Row -->
    <div class="flex flex-wrap items-center gap-4">
      <!-- Category Filters (chiếm không gian còn lại) -->
      <CategoryFilter
        :categories="categories"
        :model-value="filters.category"
        @update:model-value="$emit('update:category', $event)"
        class="flex-1 min-w-0"
      />

      <!-- Sort & Mobile Toggle (nằm bên phải) -->
      <div class="flex items-center gap-3 ml-auto">
        <SortSelect
          :model-value="filters.sortBy"
          :options="sortOptions"
          @update:model-value="$emit('update:sortBy', $event)"
        />

        <button
          @click="$emit('toggle-filter')"
          class="md:hidden flex items-center gap-2 px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-full font-sans text-label-md text-on-surface-variant hover:bg-surface-container transition-all"
        >
          <span class="material-symbols-outlined">tune</span>
          Lọc
        </button>
      </div>
    </div>
  </div>
</template>