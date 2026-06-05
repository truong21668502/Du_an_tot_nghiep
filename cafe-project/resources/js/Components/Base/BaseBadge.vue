<script setup>
defineProps({
  variant: {
    type: String,
    default: 'tertiary',
    validator: (value) => ['primary', 'secondary', 'tertiary'].includes(value)
  }
})

const variantClasses = {
  primary: 'bg-primary-container text-on-primary-container',
  secondary: 'bg-secondary-container text-on-secondary-container',
  tertiary: 'bg-tertiary-container text-on-tertiary-container'
}
</script>

<template>
  <span 
    :class="[
      'px-3 py-1 rounded-full font-sans text-label-sm inline-flex items-center',
      variantClasses[variant]
    ]"
  >
    <slot />
  </span>
</template>