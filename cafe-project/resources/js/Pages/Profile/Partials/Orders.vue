<script setup>
import { Link } from '@inertiajs/vue3'
import ProfileLayout from '@/Layouts/ProfileLayout.vue'
import { useProfile } from '@/Composables/useProfile'
defineOptions({ layout: ProfileLayout })
defineProps({
  orders: Object,
})
const { formatPrice, formatDate, orderStatusMap, paymentStatusMap, orderTypeMap, cancelOrder } = useProfile()
</script>
<template>
  <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6">
    <div>
      <h2 class="font-serif text-headline-sm text-primary mb-1">Đơn hàng của tôi</h2>
      <p class="font-sans text-body-md text-on-surface-variant">Theo dõi trạng thái các đơn hàng của bạn</p>
    </div>
    <div v-if="!orders?.data?.length" class="text-center py-12">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">receipt_long</span>
      <p class="font-sans text-body-md text-on-surface-variant">Bạn chưa có đơn hàng nào</p>
      <Link href="/menu" class="inline-block mt-3 text-secondary font-sans text-label-md hover:underline">Đặt món ngay</Link>
    </div>
    <div v-else class="space-y-4">
      <div v-for="order in orders.data" :key="order.id" class="border border-outline-variant/20 rounded-xl p-5 space-y-3">
        <div class="flex flex-wrap justify-between items-start gap-3">
          <div>
            <span class="font-sans text-label-md text-on-surface font-semibold">Đơn #{{ order.id }}</span>
            <span class="mx-2 text-outline-variant">|</span>
            <span class="font-sans text-label-sm text-on-surface-variant">{{ formatDate(order.created_at) }}</span>
            <span class="mx-2 text-outline-variant">|</span>
            <span class="font-sans text-label-sm text-on-surface-variant">{{ orderTypeMap[order.order_type] }}</span>
          </div>
          <div class="flex gap-2">
            <span :class="['px-3 py-1 rounded-full font-sans text-label-xs', orderStatusMap[order.status]?.color]">
              {{ orderStatusMap[order.status]?.label }}
            </span>
            <span :class="['px-3 py-1 rounded-full font-sans text-label-xs', paymentStatusMap[order.payment_status]?.color]">
              {{ paymentStatusMap[order.payment_status]?.label }}
            </span>
          </div>
        </div>
        <div class="space-y-2">
          <div v-for="item in order.items" :key="item.id" class="flex justify-between text-sm">
            <span class="font-sans text-body-md text-on-surface">
              {{ item.product_name }}
              <span v-if="item.size" class="text-on-surface-variant">({{ item.size }})</span>
              <span class="text-on-surface-variant"> x{{ item.quantity }}</span>
            </span>
            <span class="font-sans text-body-md text-on-surface">{{ formatPrice(item.subtotal) }}</span>
          </div>
        </div>
        <div class="flex justify-between items-center pt-3 border-t border-outline-variant/10">
          <div class="flex items-center gap-3">
            <span v-if="order.discount_amount > 0" class="font-sans text-label-sm text-secondary">Đã giảm {{ formatPrice(order.discount_amount) }}</span>
            <span class="font-serif text-headline-sm text-primary">{{ formatPrice(order.final_amount) }}</span>
          </div>
          <div class="flex gap-2">
            <Link :href="route('profile.orders.detail', order.id)" class="px-4 py-1.5 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors">
              Chi tiết
            </Link>
            <button v-if="order.status === 'PENDING'" @click="cancelOrder(order.id)" class="px-4 py-1.5 bg-red-50 text-red-700 rounded-full font-sans text-label-sm hover:bg-red-100 transition-colors">
              Hủy đơn
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>