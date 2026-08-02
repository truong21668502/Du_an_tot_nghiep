<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import BaseButton from '@/Components/Base/BaseButton.vue'

const props = defineProps({
  voucher: {
    type: Object,
    required: true,
  },
  mode: {
    type: String,
    default: 'wallet', // wallet | list
  },
  saved: {
    type: Boolean,
    default: true,
  },
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  }).format(value || 0)
}

const formatDate = (date) => {
  if (!date) return ''

  return new Date(date).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const progressPercent = computed(() => {
  if (!props.voucher.usage_limit) return 0

  return Math.min(
    100,
    ((props.voucher.used_count ?? 0) / props.voucher.usage_limit) * 100,
  )
})

const discountBig = computed(() => {
  if (props.voucher.discount_type === 'PERCENTAGE') {
    return `${props.voucher.discount_value}%`
  }

  return formatCurrency(props.voucher.discount_value)
})

const discountDescription = computed(() => {
  if (props.voucher.discount_type === 'PERCENTAGE') {
    let text = `Giảm ${props.voucher.discount_value}%`

    if (props.voucher.max_discount_amount) {
      text += ` · Tối đa ${formatCurrency(props.voucher.max_discount_amount)}`
    }

    return text
  }

  return `Giảm ${formatCurrency(props.voucher.discount_value)}`
})

const canUse = computed(() => {
  if (props.voucher.is_used) return false

  if (props.voucher.expiration_date) {
    return new Date(props.voucher.expiration_date) > new Date()
  }

  return true
})

const applyVoucher = () => {
  router.get('/gio-hang', { code: props.voucher.code }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Đã áp dụng voucher')
    },
    onError: () => {
      toast.error('Không thể áp dụng voucher')
    }
  })
}
</script>

<template>
  <div class="voucher-card">
    <!-- LEFT -->
    <div class="voucher-left">
      <div>
        <div class="discount-label">GIẢM</div>
        <div class="discount-value">
          {{ discountBig }}
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div class="voucher-divider"></div>

    <!-- RIGHT -->
    <div class="voucher-right">
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h3 class="voucher-title">
            {{ discountDescription }}
          </h3>

          <span
            v-if="voucher.is_used"
            class="rounded-full bg-error-container px-2 py-0.5 text-xs text-error"
          >
            Đã dùng
          </span>
        </div>

        <p class="voucher-condition">
          Đơn tối thiểu
          <strong>{{ formatCurrency(voucher.min_order_value) }}</strong>
        </p>

        <p class="voucher-expire">
          HSD: {{ formatDate(voucher.expiration_date) }}
        </p>

        <div
          v-if="voucher.usage_limit"
          class="mt-3"
        >
          <div class="mb-1 flex justify-between text-xs text-on-surface-variant">
            <span>
              Đã dùng {{ voucher.used_count ?? 0 }}/{{ voucher.usage_limit }}
              lượt
            </span>

            <span>{{ Math.round(progressPercent) }}%</span>
          </div>

          <div class="progress">
            <div
              class="progress-bar"
              :style="{ width: progressPercent + '%' }"
            ></div>
          </div>
        </div>

        <p
          v-else
          class="mt-3 text-xs text-on-surface-variant"
        >
          Không giới hạn lượt dùng
        </p>
      </div>

      <div class="voucher-action">
        <template v-if="mode === 'wallet'">
          <BaseButton
            v-if="canUse"
            size="md"
            @click="applyVoucher"
          >
            Dùng ngay
          </BaseButton>

          <BaseButton
            v-else
            disabled
            size="sm"
          >
            Đã dùng
          </BaseButton>
        </template>

        <template v-if="mode === 'list'">
          <BaseButton
            v-if="!saved"
            size="sm"
          >
            Lưu
          </BaseButton>

          <div
            v-else
            class="saved-text"
          >
            <span class="material-symbols-outlined text-base">
              check_circle
            </span>

            Đã lưu
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "../../../css/app.css";

.voucher-card {
  @apply flex overflow-hidden rounded-xl border border-outline-variant/15 bg-surface shadow-sm transition hover:shadow-md;
}

.voucher-left {
  @apply flex w-28 shrink-0 items-center justify-center bg-primary p-4 text-center;
}

.discount-label {
  @apply text-xs font-medium tracking-widest text-white/80;
}

.discount-value {
  @apply mt-1 text-2xl font-bold text-white;
}

.voucher-divider {
  width: 14px;
  flex-shrink: 0;
  background:
    radial-gradient(circle at left, transparent 7px, white 7px) left center /
      100% 18px repeat-y,
    linear-gradient(#e7e3dd, #e7e3dd) center/1px 100% no-repeat;
}

.voucher-right {
  @apply flex flex-1 flex-col justify-between p-4;
}

.voucher-title {
  @apply text-base font-semibold text-on-surface;
}

.voucher-condition,
.voucher-expire {
  @apply mt-1 text-sm text-on-surface-variant;
}

.progress {
  @apply h-2 overflow-hidden rounded-full bg-surface-container-high;
}

.progress-bar {
  @apply h-full rounded-full bg-primary transition-all duration-500;
}

.voucher-action {
  @apply mt-4 flex justify-end;
}

.saved-text {
  @apply flex items-center gap-1 text-sm font-medium text-green-600;
}

.voucher-card {
    @apply flex rounded-xl overflow-hidden;
    min-height: 230px;
}

@media (max-width: 640px) {
  .voucher-left {
    @apply w-24;
  }

  .discount-value {
    @apply text-xl;
  }

  .voucher-title {
    @apply text-sm;
  }
}

</style>