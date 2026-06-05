<script setup>
import { ref } from 'vue'

const props = defineProps({
  categories: {
    type: Array,
    required: true,
    validator: (arr) => arr.every(cat => cat.id && cat.label)
  },
  modelValue: {
    type: String,
    default: 'all'
  }
})

const emit = defineEmits(['update:modelValue'])

const activeFilter = ref(props.modelValue)

const setFilter = (categoryId) => {
  activeFilter.value = categoryId
  emit('update:modelValue', categoryId)
}
</script>

<template>
  <div class="w-full md:w-auto flex gap-2 overflow-x-auto pb-2 md:pb-0 hide-scrollbar">
    <button 
      v-for="category in categories" 
      :key="category.id"
      @click="setFilter(category.id)"
      :class="[
        'filter-btn whitespace-nowrap px-6 py-3 rounded-full font-sans text-label-md border transition-all duration-300',
        activeFilter === category.id 
          ? 'active bg-primary-fixed text-on-primary-fixed border-transparent' 
          : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container border-outline-variant/30'
      ]"
    >
      {{ category.label }}
    </button>
  </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>