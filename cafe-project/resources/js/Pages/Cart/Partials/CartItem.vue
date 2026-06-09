<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  item: { type: Object, required: true },
  formatPrice: { type: Function, required: true },
  loading: { type: Boolean, default: false }
})
const emit = defineEmits(['update-quantity', 'remove'])
const quantity = ref(props.item.quantity)
watch(() => props.item.quantity, (val) => { quantity.value = val })
</script>
<template>
  <div class="bg-surface rounded-xl border border-outline-variant/20 p-4 md:p-6 flex gap-4">
    <div class="w-24 h-24 md:w-28 md:h-28 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container-low">
      <img :src="item.product?.image" :alt="item.product?.name" class="w-full h-full object-cover" loading="lazy" />
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex justify-between items-start gap-4">
        <div class="min-w-0">
          <h3 class="font-serif text-headline-sm text-primary truncate">{{ item.product?.name }}</h3>
          <p v-if="item.variant" class="font-sans text-label-sm text-on-surface-variant mt-1">
            {{ item.variant.size }} / {{ item.variant.sugar }} / {{ item.variant.ice }}
          </p>
          <p v-if="item.note" class="font-sans text-label-sm text-secondary mt-1 italic">Ghi chú: {{ item.note }}</p>
        </div>
        <button
          @click="emit('remove', item.id)"
          :disabled="loading"
          class="p-2 text-on-surface-variant/60 hover:text-error hover:bg-error-container/20 rounded-full transition-colors flex-shrink-0 disabled:opacity-50"
        >
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>
      <div class="flex justify-between items-end mt-4">
        <div class="flex items-center gap-1 bg-surface-container-low rounded-full">
          <button
            @click="quantity > 1 && (quantity--, emit('update-quantity', item.id, quantity))"
            :disabled="loading || quantity <= 1"
            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-surface-container-high transition-colors disabled:opacity-40"
          >
            <span class="material-symbols-outlined text-sm">remove</span>
          </button>
          <span class="w-10 text-center font-sans text-label-md text-on-surface">{{ quantity }}</span>
          <button
            @click="quantity < 99 && (quantity++, emit('update-quantity', item.id, quantity))"
            :disabled="loading || quantity >= 99"
            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-surface-container-high transition-colors disabled:opacity-40"
          >
            <span class="material-symbols-outlined text-sm">add</span>
          </button>
        </div>
        <p class="font-serif text-headline-sm text-primary">
          {{ formatPrice((item.variant?.price || item.product?.price || 0) * quantity) }}
        </p>
      </div>
    </div>
  </div>
</template>
