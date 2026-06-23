<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'
import { router, usePage } from '@inertiajs/vue3'
defineProps({
  subtotal: { type: Number, required: true },
  taxAmount: { type: Number, required: true },
  discount: { type: Number, default: 0 },
  total: { type: Number, required: true },
  formatPrice: { type: Function, required: true },
  itemCount: { type: Number, required: true },
  loading: { type: Boolean, default: false }
})
</script>
<template>
  <div class="bg-surface rounded-xl border border-outline-variant/20 p-6 space-y-4">
    <h3 class="font-serif text-headline-sm text-primary">Tạm tính</h3>
    <div class="space-y-3">
      <div class="flex justify-between">
        <span class="font-sans text-body-md text-on-surface-variant">Tạm tính ({{ itemCount }} món)</span>
        <span class="font-sans text-body-md text-on-surface">{{ formatPrice(subtotal) }}</span>
      </div>
      <div class="flex justify-between">
        <span class="font-sans text-body-md text-on-surface-variant">Thuế VAT (8%)</span>
        <span class="font-sans text-body-md text-on-surface">{{ formatPrice(taxAmount) }}</span>
      </div>
      <div v-if="discount > 0" class="flex justify-between text-secondary">
        <span class="font-sans text-body-md">Giảm giá</span>
        <span class="font-sans text-body-md">-{{ formatPrice(discount) }}</span>
      </div>
      <hr class="border-outline-variant/20" />
      <div class="flex justify-between items-center">
        <span class="font-serif text-headline-sm text-primary">Tổng cộng</span>
        <span class="font-serif text-headline-sm text-primary">{{ formatPrice(total) }}</span>
      </div>
    </div>
    <BaseButton variant="primary" :disabled="loading" class="w-full justify-center" @click="router.get(route('checkout.index'))">
      <span class="material-symbols-outlined text-lg">shopping_bag</span>
      Đặt hàng
    </BaseButton>
  </div>
</template>
