<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import MenuItemCard from './MenuItemCard.vue'

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  activeFilter: {
    type: String,
    default: 'all'
  }
})

const gridRef = ref(null)
const filteredItems = ref([...props.items])

// Filter items based on active category
const updateFilteredItems = () => {
  if (props.activeFilter === 'all') {
    filteredItems.value = [...props.items]
  } else {
    filteredItems.value = props.items.filter(item => item.category === props.activeFilter)
  }
}

// Watch for filter changes
watch(() => props.activeFilter, () => {
  updateFilteredItems()
})

// Initialize
onMounted(() => {
  updateFilteredItems()
})
</script>

<template>
  <div 
    ref="gridRef"
    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 relative min-h-[600px]"
  >
    <MenuItemCard 
      v-for="item in filteredItems" 
      :key="item.id" 
      :item="item"
    />
    
    <!-- Empty State -->
    <div 
      v-if="filteredItems.length === 0" 
      class="col-span-full flex flex-col items-center justify-center py-20 text-center"
    >
      <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">search_off</span>
      <h3 class="font-serif text-headline-sm text-on-surface-variant mb-2">Không tìm thấy món nào</h3>
      <p class="font-sans text-body-md text-outline">Vui lòng thử danh mục khác</p>
    </div>
  </div>
</template>