<script setup>
const props = defineProps({
  modelValue: {
    type: Number,
    default: 0
  },
  maxRating: {
    type: Number,
    default: 5
  }
})

const emit = defineEmits(['update:modelValue'])

const selectRating = (rating) => {
  emit('update:modelValue', props.modelValue === rating ? 0 : rating)
}
</script>

<template>
  <div class="space-y-3">
    <h4 class="font-sans text-label-md text-on-surface">Đánh giá</h4>
    
    <div class="flex items-center gap-3 flex-wrap">
      <button
        v-for="rating in maxRating"
        :key="rating"
        @click="selectRating(rating)"
        :class="[
          'flex items-center gap-1 px-3 py-2 rounded-full transition-all duration-200 font-sans text-label-sm',
          modelValue === rating 
            ? 'bg-primary-container/50 text-on-primary-container' 
            : 'hover:bg-surface-container-low text-on-surface-variant'
        ]"
      >
        <span class="material-symbols-outlined text-sm fill-icon text-yellow-500">star</span>
        <span>{{ rating }} sao trở lên</span>
      </button>
    </div>
  </div>
</template>