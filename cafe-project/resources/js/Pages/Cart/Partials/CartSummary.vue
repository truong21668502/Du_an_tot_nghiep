<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

defineProps({
  subtotal: { type: Number, required: true },
  discount: { type: Number, default: 0 },
  total: { type: Number, required: true },
  formatPrice: { type: Function, required: true },
  itemCount: { type: Number, required: true },
  loading: { type: Boolean, default: false }
})

const shippingFees = computed(() => {
  const raw = page.props.settings?.delivery?.shipping_fees
  if (!raw) return []

  try {
    const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
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

      <!-- Phí ship (từ settings) -->
      <div
        v-if="shippingFees.length"
        class="rounded-lg bg-primary/5 border border-primary/10 p-3 space-y-1"
      >
        <div class="font-medium text-primary">
          Phí giao hàng (ước tính)
        </div>

        <div
          v-for="(fee, index) in shippingFees"
          :key="index"
          class="text-sm text-on-surface-variant"
        >
          • {{ fee.from_km }} – {{ fee.to_km }} km: {{ formatPrice(fee.fee) }}
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