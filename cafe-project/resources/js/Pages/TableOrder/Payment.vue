<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTableOrder } from '@/Composables/useTableOrder'
import BaseButton from '@/Components/Base/BaseButton.vue'
const page = usePage()
const order = computed(() => page.props.order)
const qrUrl = computed(() => page.props.qrUrl)
const bankInfo = computed(() => page.props.bankInfo)
const { loading, confirmPayment, formatPrice } = useTableOrder()
</script>
<template>
  <div class="min-h-screen bg-surface-container-low flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full">
      <div class="text-center mb-8"><h1 class="text-display-lg-mobile text-primary mb-2">Quét mã QR</h1><p class="text-body-md text-on-surface-variant">Quét để thanh toán đơn {{ order?.order_code }}</p></div>
      <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 text-center space-y-4">
        <img :src="qrUrl" alt="QR" class="w-56 h-56 mx-auto rounded-xl" />
        <div class="bg-surface-container-low rounded-xl p-3 text-left space-y-1 font-sans text-label-sm">
          <div class="flex justify-between"><span class="text-on-surface-variant">Ngân hàng</span><span class="text-on-surface font-bold">{{ bankInfo?.short_name }}</span></div>
          <div class="flex justify-between"><span class="text-on-surface-variant">Số TK</span><span class="text-on-surface font-bold">{{ bankInfo?.account_number }}</span></div>
          <div class="flex justify-between"><span class="text-on-surface-variant">Chủ TK</span><span class="text-on-surface">{{ bankInfo?.account_name }}</span></div>
          <div class="flex justify-between"><span class="text-on-surface-variant">Số tiền</span><span class="text-primary font-bold">{{ formatPrice(order?.final_amount) }}</span></div>
          <div class="flex justify-between"><span class="text-on-surface-variant">Nội dung</span><span class="text-on-surface font-bold">{{ order?.order_code }}</span></div>
        </div>
        <p class="font-sans text-label-sm text-on-surface-variant">Sau khi chuyển khoản, nhấn nút bên dưới</p>
        <BaseButton variant="primary" class="w-full justify-center" :disabled="loading" @click="confirmPayment(order?.id)">{{ loading ? 'Đang xác nhận...' : 'Tôi đã thanh toán' }}</BaseButton>
      </div>
    </div>
  </div>
</template>
