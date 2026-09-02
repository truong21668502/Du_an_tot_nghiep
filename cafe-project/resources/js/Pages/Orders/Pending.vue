<script setup>
import { computed, onMounted, ref } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const order = computed(() => page.props.order)
const formatPrice = (price) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
const currentStatus = ref(order.value?.status || 'PENDING')
const statusMap = {
  PENDING: { label: 'Chờ phục vụ', color: 'text-secondary', icon: 'hourglass_top', bg: 'bg-secondary-container/30' },
  PROCESSING: { label: 'Đang pha chế', color: 'text-primary', icon: 'coffee', bg: 'bg-primary-container/30' },
  READY: { label: 'Sẵn sàng giao hàng', color: 'text-emerald-600', icon: 'room_service', bg: 'bg-emerald-500/10' },
  DELIVERING: { label: 'Đang giao hàng', color: 'text-sky-600', icon: 'local_shipping', bg: 'bg-sky-500/10' },
  COMPLETED: { label: 'Hoàn thành', color: 'text-tertiary', icon: 'check_circle', bg: 'bg-tertiary-container/30' },
  CANCELLED: { label: 'Đã hủy', color: 'text-red-600', icon: 'cancel', bg: 'bg-red-500/10' }
}


onMounted(() => {
    if (typeof window.Echo !== 'undefined' && order.value) {
        window.Echo
            .channel('staff-orders')
            .listen('.order.status-updated', (event) => {
                if (event.order?.id === order.value?.id) {
                    currentStatus.value = event.order.status
                }
            })
    }
})
</script>
<template>
  <div class="w-full">
    <div class="max-w-[640px] mx-auto px-margin-mobile py-24 text-center">
      <AnimateOnScroll animation="scale-in" :duration="700">
        <div :class="['w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center', (statusMap[currentStatus] || statusMap.PENDING).bg]">
          <span :class="['material-symbols-outlined text-5xl fill-icon', (statusMap[currentStatus] || statusMap.PENDING).color]">{{ (statusMap[currentStatus] || statusMap.PENDING).icon }}</span>
        </div>
        <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-2">Đặt món thành công!</h1>
        <p class="text-body-lg text-on-surface-variant mb-1">Mã đơn: <span class="font-bold text-primary">DH{{ String(order?.id).padStart(8, '0') }}</span></p>
        <p class="text-body-md text-on-surface-variant mb-1">Bàn: {{ order?.table?.table_name }}</p>
        <p class="text-body-md text-on-surface-variant mb-1">Tổng tiền: <span class="font-bold text-primary">{{ formatPrice(order?.final_amount) }}</span></p>
        <p class="text-body-md text-on-surface-variant mb-1">Thanh toán: <span class="font-bold">{{ order?.payment?.payment_method === 'CASH' ? 'Tiền mặt' : 'Chuyển khoản' }}</span></p>
        <p :class="['font-sans text-label-md mt-4 px-4 py-2 rounded-full inline-block', (statusMap[currentStatus] || statusMap.PENDING).bg, (statusMap[currentStatus] || statusMap.PENDING).color]">{{ (statusMap[currentStatus] || statusMap.PENDING).label }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
          <Link href="/"><BaseButton variant="secondary">Về trang chủ</BaseButton></Link>
          <Link href="/thuc-don"><BaseButton variant="primary">Tiếp tục gọi món</BaseButton></Link>
        </div>
      </AnimateOnScroll>
    </div>
  </div>
</template>
