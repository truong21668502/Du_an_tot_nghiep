<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useCheckout } from '@/Composables/useCheckout'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const order = computed(() => page.props.order)
const qrUrl = computed(() => page.props.qrUrl)
const bankInfo = computed(() => page.props.bankInfo)
const { loading, confirmPayment, formatPrice } = useCheckout()
const paymentMethodLabel = {
  BANK_TRANSFER: 'Chuyển khoản ngân hàng',
  MOMO: 'Ví MoMo',
  VNPAY: 'VNPay',
}
const handleConfirm = () => {
  if (order.value) confirmPayment(order.value.id)
}
</script>
<template>
  <div class="w-full">
    <div class="max-w-[640px] mx-auto px-margin-mobile py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="text-center mb-10">
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-2">Quét mã QR để thanh toán</h1>
          <p class="text-body-md text-on-surface-variant">{{ paymentMethodLabel[order?.payment_method] || order?.payment_method }}</p>
        </div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="scale-in" :duration="700" :delay="200">
        <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 text-center space-y-6">
          <div class="bg-white rounded-xl p-4 inline-block">
            <img :src="qrUrl" alt="QR Code" class="w-64 h-64 object-contain mx-auto" />
          </div>
          <div class="bg-surface-container-low rounded-xl p-4 text-left space-y-2">
            <div class="flex justify-between"><span class="font-sans text-label-sm text-on-surface-variant">Ngân hàng</span><span class="font-sans text-label-md text-on-surface">{{ bankInfo?.short_name }}</span></div>
            <div class="flex justify-between"><span class="font-sans text-label-sm text-on-surface-variant">Số tài khoản</span><span class="font-sans text-label-md text-on-surface font-bold">{{ bankInfo?.account_number }}</span></div>
            <div class="flex justify-between"><span class="font-sans text-label-sm text-on-surface-variant">Chủ tài khoản</span><span class="font-sans text-label-md text-on-surface">{{ bankInfo?.account_name }}</span></div>
            <div class="flex justify-between"><span class="font-sans text-label-sm text-on-surface-variant">Số tiền</span><span class="font-sans text-label-md text-primary font-bold">{{ formatPrice(order?.final_amount) }}</span></div>
            <div class="flex justify-between"><span class="font-sans text-label-sm text-on-surface-variant">Nội dung CK</span><span class="font-sans text-label-md text-on-surface font-bold">{{ order?.order_code }}</span></div>
          </div>
          <p class="font-sans text-label-sm text-on-surface-variant">Sau khi chuyển khoản, nhấn nút bên dưới để xác nhận</p>
          <BaseButton variant="primary" class="w-full justify-center" :disabled="loading" @click="handleConfirm">
            <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
            {{ loading ? 'Đang xác nhận...' : 'Tôi đã thanh toán' }}
          </BaseButton>
        </div>
      </AnimateOnScroll>
    </div>
  </div>
</template>
