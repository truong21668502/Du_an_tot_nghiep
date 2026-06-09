<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
const props = defineProps({
  align: {
    type: String,
    default: 'right',
    validator: (val) => ['left', 'right'].includes(val)
  },
  width: {
    type: String,
    default: '48'
  },
  contentClasses: {
    type: String,
    default: 'py-1 bg-white'
  }
})
const open = ref(false)
const dropdownRef = ref(null)
const closeOnEscape = (e) => {
  if (open.value && e.key === 'Escape') {
    open.value = false
  }
}
const closeOnClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    open.value = false
  }
}
onMounted(() => {
  document.addEventListener('keydown', closeOnEscape)
  document.addEventListener('click', closeOnClickOutside)
})
onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape)
  document.removeEventListener('click', closeOnClickOutside)
})
const toggleDropdown = () => {
  open.value = !open.value
}
const alignmentClasses = props.align === 'left' 
  ? 'origin-top-left left-0' 
  : 'origin-top-right right-0'
const widthClass = `w-${props.width}`
</script>
<template>
  <div class="relative" ref="dropdownRef">
    <div @click.stop="toggleDropdown">
      <slot name="trigger" />
    </div>
    <div
      v-show="open"
      class="absolute z-50 mt-2 rounded-md shadow-lg"
      :class="[widthClass, alignmentClasses]"
      @click="open = false"
    >
      <div :class="contentClasses" class="rounded-md ring-1 ring-black ring-opacity-5">
        <slot />
      </div>
    </div>
  </div>
</template>
