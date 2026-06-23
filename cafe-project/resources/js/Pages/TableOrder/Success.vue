<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
const page = usePage()
const order = computed(() => page.props.order)
const currentStatus = ref(order.value?.status || 'PENDING')
const formatPrice = (price) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
const statusMap = {
  PENDING: { label: 'Chờ phục vụ', color: 'text-secondary', icon: 'hourglass_top', bg: 'bg-secondary-container/30' },
  PROCESSING: { label: 'Đang pha chế', color: 'text-primary', icon: 'coffee', bg: 'bg-primary-container/30' },
  COMPLETED: { label: 'Hoàn thành', color: 'text-tertiary', icon: 'check_circle', bg: 'bg-tertiary-container/30' },
}
onMounted(() => {
  if (typeof window.Echo !== 'undefined' && order.value) {
    window.Echo.channel(`order.${order.value.id}`).listen('OrderStatusUpdated', (event) => { currentStatus.value = event.status })
  }
})
</script>
<template>
  <div class="min-h-screen bg-surface-container-low flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full text-center">
      <AnimateOnScroll animation="scale-in" :duration="700">
        <div :class="['w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center', (statusMap[currentStatus] || statusMap.PENDING).bg]">
          <span :class="['material-symbols-outlined text-5xl fill-icon', (statusMap[currentStatus] || statusMap.PENDING).color]">{{ (statusMap[currentStatus] || statusMap.PENDING).icon }}</span>
        </div>
        <h1 class="text-display-lg-mobile text-primary mb-2">Cảm ơn bạn!</h1>
        <p class="text-body-lg text-on-surface-variant mb-1">Mã đơn: <span class="font-bold text-primary">{{ order?.order_code }}</span></p>
        <p class="text-body-md text-on-surface-variant mb-1">Bàn: {{ order?.table_name }}</p>
        <p class="text-body-md text-on-surface-variant mb-1">Tổng tiền: <span class="font-bold text-primary">{{ formatPrice(order?.final_amount) }}</span></p>
        <p :class="['font-sans text-label-md mt-4 px-4 py-2 rounded-full inline-block', (statusMap[currentStatus] || statusMap.PENDING).bg, (statusMap[currentStatus] || statusMap.PENDING).color]">{{ (statusMap[currentStatus] || statusMap.PENDING).label }}</p>
        <div class="mt-8"><Link href="/"><BaseButton variant="secondary">Về trang chủ</BaseButton></Link></div>
      </AnimateOnScroll>
    </div>
  </div>
</template>
