<script setup>
import { computed, ref, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const cart = computed(() => page.props.cart)
const subtotal = computed(() => page.props.subtotal || 0)
const voucher = computed(() => page.props.voucher || null)
const tables = computed(() => page.props.tables || [])
const activeOrder = computed(() => page.props.activeOrder || null)
const discountAmount = computed(() => voucher.value?.discount_amount || voucher.value?.discount || 0)
const finalAmount = computed(() => Math.max(0, subtotal.value - discountAmount.value))
const loading = ref(false)
const errors = ref({})
const sessionTable = computed(() => page.props.sessionTable || null)
const sessionOrderType = computed(() => page.props.sessionOrderType || null)
const selectedTableId = ref(sessionTable.value?.id || null)
const selectedPaymentMethod = ref('CASH')
const note = ref('')
onMounted(() => {
  if (tables.value.length > 0) selectedTableId.value = tables.value[0].id
})



const formatPrice = (price) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
const submitOrder = () => {
  loading.value = true
  errors.value = {}
  router.post(route('customer.orders.store'), {
    table_id: selectedTableId.value,
    order_type: 'DINE_IN',
    payment_method: selectedPaymentMethod.value,
    note: note.value,
  }, {
    preserveScroll: true,
    onError: (err) => { errors.value = err },
    onFinish: () => { loading.value = false }
  })
}
</script>
<template>
  <div class="w-full">
    <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="mb-10">
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-2">Xác nhận đặt món</h1>
          <p class="text-body-md text-on-surface-variant">Chọn bàn và phương thức thanh toán</p>
        </div>
      </AnimateOnScroll>
      <div v-if="activeOrder" class="mb-8 p-4 bg-secondary-container/20 border border-secondary-container rounded-2xl">
        <p class="font-sans text-label-md text-on-secondary-container">Bạn có đơn hàng đang chờ xử lý. Hoàn tất đơn đó trước khi đặt món mới.</p>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="100">
            <div v-if="!sessionTable" class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Chọn bàn</h2>
              <div v-if="tables.length === 0" class="text-center py-6">
                <p class="font-sans text-body-md text-on-surface-variant">Hiện không có bàn trống</p>
              </div>
              <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <label v-for="table in tables" :key="table.id" :class="['flex flex-col items-center gap-1 p-4 rounded-xl border-2 cursor-pointer transition-all text-center', selectedTableId === table.id ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedTableId" :value="table.id" type="radio" name="table" class="sr-only" />
                  <span class="material-symbols-outlined text-2xl text-primary">table_bar</span>
                  <span class="font-sans text-label-sm text-on-surface font-bold">{{ table.table_name }}</span>
                  <span class="font-sans text-label-sm text-on-surface-variant">{{ table.area }} · {{ table.capacity }} người</span>
                </label>
              </div>
              <p v-if="errors.table_id" class="text-error text-label-sm mt-2">{{ errors.table_id }}</p>
            </div>
          </AnimateOnScroll>
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="200">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Phương thức thanh toán</h2>
              <div class="space-y-3">
                <label :class="['flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all', selectedPaymentMethod === 'CASH' ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedPaymentMethod" value="CASH" type="radio" name="payment" class="accent-primary" />
                  <span class="material-symbols-outlined text-2xl text-on-surface-variant">payments</span>
                  <span class="font-sans text-label-md text-on-surface">Tiền mặt</span>
                </label>
                <label :class="['flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all', selectedPaymentMethod === 'BANK_TRANSFER' ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedPaymentMethod" value="BANK_TRANSFER" type="radio" name="payment" class="accent-primary" />
                  <span class="material-symbols-outlined text-2xl text-on-surface-variant">account_balance</span>
                  <span class="font-sans text-label-md text-on-surface">Chuyển khoản ngân hàng</span>
                </label>
              </div>
              <p v-if="errors.payment_method" class="text-error text-label-sm mt-2">{{ errors.payment_method }}</p>
            </div>
          </AnimateOnScroll>
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="300">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Ghi chú</h2>
              <textarea v-model="note" rows="3" placeholder="Ghi chú cho đơn hàng..." class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary resize-none"></textarea>
            </div>
          </AnimateOnScroll>
        </div>
        <div class="lg:col-span-1">
          <div class="sticky top-24 space-y-4">
            <AnimateOnScroll animation="fade-left" :duration="700">
              <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6">
                <h3 class="font-serif text-headline-sm text-primary mb-4">Đơn hàng</h3>
                <div class="space-y-2 mb-4">
                  <div class="flex justify-between"><span class="font-sans text-body-md text-on-surface-variant">Tạm tính</span><span class="font-sans text-body-md text-on-surface">{{ formatPrice(subtotal) }}</span></div>
                  <div v-if="voucher" class="flex justify-between text-secondary"><span class="font-sans text-body-md">Giảm giá ({{ voucher.code }})</span><span class="font-sans text-body-md">-{{ formatPrice(discountAmount) }}</span></div>
                  <hr class="border-outline-variant/20" />
                  <div class="flex justify-between"><span class="font-serif text-headline-sm text-primary">Tổng cộng</span><span class="font-serif text-headline-sm text-primary">{{ formatPrice(finalAmount) }}</span></div>
                </div>
                <BaseButton variant="primary" class="w-full justify-center" :disabled="loading || (!sessionTable && !selectedTableId) || !!activeOrder" @click="submitOrder">
                  <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
                  {{ loading ? 'Đang xử lý...' : selectedPaymentMethod === 'BANK_TRANSFER' ? 'Thanh toán VNPay' : 'Đặt món' }}
                </BaseButton>
              </div>
            </AnimateOnScroll>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
