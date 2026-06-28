<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const status = computed(() => page.props.status)
const message = computed(() => page.props.message)
const order = computed(() => page.props.order)
const formatPrice = (price) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
</script>
<template>
  <div class="w-full">
    <div class="max-w-[640px] mx-auto px-margin-mobile py-24 text-center">
      <div class="w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center" :class="status === 'success' ? 'bg-tertiary-container/30' : 'bg-error-container/30'">
        <span class="material-symbols-outlined text-5xl fill-icon" :class="status === 'success' ? 'text-tertiary' : 'text-error'">{{ status === 'success' ? 'check_circle' : 'error' }}</span>
      </div>
      <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-2">{{ status === 'success' ? 'Thanh toán thành công!' : 'Thanh toán thất bại' }}</h1>
      <p class="text-body-md text-on-surface-variant mb-4">{{ message }}</p>
      <p v-if="order" class="text-body-md text-on-surface-variant mb-8">Đơn hàng #{{ order.id }} - {{ formatPrice(order.final_amount) }}</p>
      <div class="flex flex-wrap justify-center gap-4">
        <Link href="/"><BaseButton variant="secondary">Về trang chủ</BaseButton></Link>
        <Link href="/thuc-don"><BaseButton variant="primary">Tiếp tục gọi món</BaseButton></Link>
      </div>
    </div>
  </div>
</template>
