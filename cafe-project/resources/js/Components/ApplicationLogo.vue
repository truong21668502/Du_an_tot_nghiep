<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({
  url: {
    type: String,
    default: '/'
  },
  size: {
    type: String,
    default: 'md',
    validator: (val) => ['sm', 'md', 'lg'].includes(val)
  },
  logoSrc: {
    type: String,
    default: ''
  }
})
const sizeClasses = {
  sm: 'w-8 h-8',
  md: 'w-10 h-10',
  lg: 'w-12 h-12'
}
</script>
<template>
  <Link :href="url" class="inline-flex items-center gap-2 group">
    <div :class="[sizeClasses[size], 'rounded-full overflow-hidden bg-primary-container/30 border-2 border-primary/20 group-hover:border-primary/40 transition-all duration-300 flex-shrink-0']">
      <img 
        v-if="logoSrc" 
        :src="logoSrc" 
        alt="Logo" 
        class="w-full h-full object-cover"
      />
      <span v-else class="w-full h-full flex items-center justify-center text-primary font-serif font-bold text-sm">
        NC
      </span>
    </div>
    <slot />
  </Link>
</template>
