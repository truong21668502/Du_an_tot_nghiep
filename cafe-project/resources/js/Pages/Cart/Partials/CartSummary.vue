<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  subtotal: { type: Number, required: true },
  discount: { type: Number, default: 0 },
  total: { type: Number, required: true },
  formatPrice: { type: Function, required: true },
  itemCount: { type: Number, required: true },
  loading: { type: Boolean, default: false }
})
</script>

<template>
  <div class="bg-surface rounded-xl border border-outline-variant/20 p-6 space-y-4">
    <h3 class="font-serif text-headline-sm text-primary">
      Tạm tính
    </h3>

    <div class="space-y-3">
      <div class="flex justify-between">
        <span class="font-sans text-body-md text-on-surface-variant">
          Tạm tính ({{ itemCount }} món)
        </span>
        <span class="font-sans text-body-md text-on-surface">
          {{ formatPrice(subtotal) }}
        </span>
      </div>

      <div
        v-if="discount > 0"
        class="flex justify-between text-secondary"
      >
        <span>Giảm giá</span>
        <span>-{{ formatPrice(discount) }}</span>
      </div>

      <!-- Phí ship -->
      <div class="rounded-lg bg-primary/5 border border-primary/10 p-3 space-y-1">
        <div class="font-medium text-primary">
          Phí giao hàng (ước tính)
        </div>

        <div class="text-sm text-on-surface-variant">
          • 0 – 2 km: 10.000đ
        </div>

        <div class="text-sm text-on-surface-variant">
          • Trên 2 – 3 km: 15.000đ
        </div>

        <div class="text-sm text-on-surface-variant">
          • Trên 3 – 4 km: 20.000đ
        </div>

        <div class="text-sm text-on-surface-variant">
          • Trên 4 – 5 km: 25.000đ
        </div>

        <div class="text-xs text-gray-500 mt-2 italic">
          Phí giao hàng chính xác sẽ được tính sau khi bạn chọn địa chỉ ở bước thanh toán.
        </div>
      </div>

      <hr class="border-outline-variant/20" />

      <div class="flex justify-between items-center">
        <span class="font-serif text-headline-sm text-primary">
          Tổng cộng
        </span>

        <span class="font-serif text-headline-sm text-primary">
          {{ formatPrice(total) }}
        </span>
      </div>
    </div>

    <Link :href="route('customer.checkout.index')">
      <BaseButton
        variant="primary"
        :disabled="loading"
        class="w-full justify-center"
      >
        <span class="material-symbols-outlined text-lg">
          shopping_bag
        </span>
        Đặt hàng
      </BaseButton>
    </Link>
  </div>
</template>