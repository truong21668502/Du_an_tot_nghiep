<script setup>
import { computed } from 'vue'
const props = defineProps({
  currentPage: { type: Number, required: true },
  totalPages: { type: Number, required: true },
  maxVisible: { type: Number, default: 5 }
})
const emit = defineEmits(['page-change'])
const visiblePages = computed(() => {
  const pages = []
  const half = Math.floor(props.maxVisible / 2)
  let start = Math.max(1, props.currentPage - half)
  let end = Math.min(props.totalPages, start + props.maxVisible - 1)
  if (end - start + 1 < props.maxVisible) start = Math.max(1, end - props.maxVisible + 1)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})
</script>
<template>
  <div class="flex justify-center items-center gap-2 mt-16">
    <button @click="emit('page-change', currentPage - 1)" :disabled="currentPage === 1" class="w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-sm transition-all disabled:opacity-30 disabled:cursor-not-allowed hover:bg-surface-container-low text-on-surface-variant">
      <span class="material-symbols-outlined text-sm">chevron_left</span>
    </button>
    <button
      v-for="page in visiblePages"
      :key="page"
      @click="emit('page-change', page)"
      :class="[
        'w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-sm transition-all',
        currentPage === page ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-low'
      ]"
    >
      {{ page }}
    </button>
    <button @click="emit('page-change', currentPage + 1)" :disabled="currentPage === totalPages" class="w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-sm transition-all disabled:opacity-30 disabled:cursor-not-allowed hover:bg-surface-container-low text-on-surface-variant">
      <span class="material-symbols-outlined text-sm">chevron_right</span>
    </button>
  </div>
</template>
