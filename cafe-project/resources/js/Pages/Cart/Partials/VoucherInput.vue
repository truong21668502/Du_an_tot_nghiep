<script setup>
const props = defineProps({
  modelValue: { type: String, default: '' },
  appliedVoucher: { type: Object, default: null },
  discount: { type: Number, default: 0 },
  error: { type: String, default: null },
  formatPrice: { type: Function, required: true },
  loading: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue', 'apply', 'remove'])
</script>
<template>
  <div class="bg-surface rounded-xl border border-outline-variant/20 p-6 space-y-4">
    <h3 class="font-serif text-headline-sm text-primary">Mã voucher</h3>
    <div v-if="appliedVoucher" class="p-3 bg-tertiary-container/20 rounded-lg flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-tertiary">confirmation_number</span>
        <div>
          <p class="font-sans text-label-md text-on-surface">{{ appliedVoucher.code }}</p>
          <p class="font-sans text-label-sm text-on-surface-variant">Giảm {{ formatPrice(discount) }}</p>
        </div>
      </div>
      <button @click="emit('remove')" :disabled="loading" class="p-1 hover:bg-error-container/20 rounded-full transition-colors text-on-surface-variant hover:text-error disabled:opacity-50">
        <span class="material-symbols-outlined text-sm">close</span>
      </button>
    </div>
    <div v-else class="flex gap-2">
      <input
        :value="modelValue"
        @input="emit('update:modelValue', $event.target.value)"
        type="text"
        placeholder="Nhập mã voucher"
        class="flex-1 px-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all uppercase"
      />
      <button
        @click="emit('apply')"
        :disabled="loading || !modelValue.trim()"
        class="px-6 py-2 bg-primary text-on-primary rounded-full font-sans text-label-sm hover:bg-primary/90 transition-colors disabled:opacity-50"
      >
        {{ loading ? '...' : 'Áp dụng' }}
      </button>
    </div>
    <p v-if="error" class="text-error text-label-sm">{{ error }}</p>
  </div>
</template>
