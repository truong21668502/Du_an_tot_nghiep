<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'inverted', 'outline'].includes(value)
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const variantClasses = {
  primary: 'bg-primary text-on-primary hover:bg-primary/90 shadow-sm hover:shadow-md',
  secondary: 'bg-transparent border-[1.5px] border-secondary text-secondary hover:bg-secondary/5',
  inverted: 'bg-on-primary-container text-primary-container hover:bg-on-primary-container/90 shadow-sm',
  outline: 'bg-transparent border-[1.5px] border-outline-variant text-on-surface-variant hover:border-primary hover:text-primary'
}

const sizeClasses = {
  sm: 'px-4 py-2 text-label-sm',
  md: 'px-6 py-3 text-label-md',
  lg: 'px-8 py-4 text-label-md'
}
</script>

<template>
  <button 
    :disabled="disabled"
    :class="[
      'font-sans rounded-full transition-all duration-300 active:scale-95 cursor-pointer inline-flex items-center justify-center gap-2',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100',
      sizeClasses[size],
      variantClasses[variant]
    ]"
  >
    <slot />
  </button>
</template>