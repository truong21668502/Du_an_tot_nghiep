<script setup>
import SearchBar from '@/Components/Main/SearchBar.vue'
defineProps({
  search: { type: String, default: '' },
  categories: { type: Array, default: () => [] },
  activeCategory: { type: String, default: 'all' }
})
defineEmits(['update:search', 'update:category'])
</script>
<template>
  <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between mb-12">
    <div class="w-full md:w-80">
      <SearchBar :model-value="search" placeholder="Tìm kiếm bài viết..." @update:model-value="$emit('update:search', $event)" />
    </div>
    <div class="flex flex-wrap gap-2">
      <button
        v-for="cat in categories"
        :key="cat.id"
        @click="$emit('update:category', cat.slug || cat.id)"
        :class="[
          'px-4 py-2 rounded-full font-sans text-label-sm border transition-all duration-300',
          activeCategory === (cat.slug || String(cat.id))
            ? 'bg-primary-fixed text-on-primary-fixed border-transparent'
            : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container border-outline-variant/30'
        ]"
      >
        {{ cat.name }}
      </button>
    </div>
  </div>
</template>
