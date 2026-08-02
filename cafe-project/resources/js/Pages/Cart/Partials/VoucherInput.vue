<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import VoucherModal from './VoucherModal.vue'
import toast from 'vue3-toastify'

const props = defineProps({
  modelValue: { type: String, default: '' },
  appliedVoucher: { type: Object, default: null },
  discount: { type: Number, default: 0 },
  error: { type: String, default: null },
  formatPrice: { type: Function, required: true },
  loading: { type: Boolean, default: false },
  subtotal: { type: Number, default: 0 }
})
const emit = defineEmits([
  'update:modelValue',
  'apply',
  'remove',
  'apply-from-modal'
])

const isModalOpen = ref(false)
const isApplying = ref(false)
const handleApplyFromModal = (voucher) => {
  emit('apply-from-modal', voucher)
  isModalOpen.value = false
}
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

    <div v-else>
      <div class="flex gap-2">
        <input
          :value="modelValue"
          @input="emit('update:modelValue', $event.target.value)"
          @keyup.enter="emit('apply')"
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

      <button
        @click="isModalOpen = true"
        :disabled="isApplying"
        class="mt-3 w-full flex items-center justify-between px-4 py-3 border border-dashed border-secondary/30 rounded-xl hover:bg-secondary/5 transition-colors disabled:opacity-50"
      >
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-secondary">card_giftcard</span>
          <span class="font-sans text-body-sm text-secondary">Chọn từ voucher đã lưu</span>
        </div>
        <span class="material-symbols-outlined text-secondary text-sm">chevron_right</span>
      </button>
    </div>

    <p v-if="error" class="text-error text-label-sm">{{ error }}</p>

    <VoucherModal
      :is-open="isModalOpen"
      :current-voucher="appliedVoucher"
      :subtotal="subtotal"
      :loading="isApplying"
      @close="isModalOpen = false"
      @apply-voucher="handleApplyFromModal"
    />
  </div>
</template>