<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'

defineOptions({ layout: MainLayout })

const page = usePage()
const subtotal = computed(() => page.props.subtotal || 0)
const voucher = computed(() => page.props.voucher || null)
const addresses = computed(() => page.props.addresses || [])

const discountAmount = computed(() => voucher.value?.discount_amount || voucher.value?.discount || 0)
const finalAmount = computed(() => Math.max(0, subtotal.value - discountAmount.value))

const loading = ref(false)
const errors = ref({})

const selectedAddressId = ref(addresses.value.find(a => a.is_default)?.id || addresses.value[0]?.id || null)
const selectedPaymentMethod = ref('CASH')
const note = ref('')

const formatPrice = (price) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)

const canSubmit = computed(() => {
  if (loading.value) return false
  return !!selectedAddressId.value
})


const submitOrder = () => {
  if (!canSubmit.value) return
  
  loading.value = true
  errors.value = {}
  
  router.post(route('customer.orders.store'), {
    order_type: 'DELIVERY',
    address_id: selectedAddressId.value,
    payment_method: selectedPaymentMethod.value,
    note: note.value,
  }, {
    preserveScroll: true,
    onError: (err) => { 
      errors.value = err
      loading.value = false
    },
    onFinish: () => { 
      loading.value = false 
    }
  })
}
</script>

<template>
  <div class="w-full">
    <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="mb-10">
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-2">Xác nhận đặt món</h1>
          <p class="text-body-md text-on-surface-variant">Chọn địa chỉ và phương thức thanh toán</p>
        </div>
      </AnimateOnScroll>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">

          <!-- Chọn địa chỉ giao hàng -->
          <AnimateOnScroll animation="fade-right" :duration="700">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Địa chỉ giao hàng</h2>
              <div v-if="addresses.length === 0" class="text-center py-6">
                <p class="font-sans text-body-md text-on-surface-variant">Bạn chưa có địa chỉ nào</p>
                <Link :href="route('profile.addresses')" class="text-primary font-sans text-label-md hover:underline mt-2 inline-block">
                  + Thêm địa chỉ mới
                </Link>
              </div>
              <div v-else class="space-y-3">
                <label v-for="address in addresses" :key="address.id"
                  :class="['block p-4 rounded-xl border-2 cursor-pointer transition-all',
                    selectedAddressId === address.id ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedAddressId" :value="address.id" type="radio" name="address" class="sr-only" />
                  <p class="font-sans text-label-md text-on-surface font-bold">
                    {{ address.receiver_name }}
                    <span v-if="address.is_default" class="text-label-sm text-primary font-normal">(Mặc định)</span>
                  </p>
                  <p class="font-sans text-label-sm text-on-surface-variant">{{ address.receiver_phone }}</p>
                  <p class="font-sans text-label-sm text-on-surface-variant">
                    {{ address.address_detail }}{{ address.ward ? ', ' + address.ward : '' }}{{ address.city ? ', ' + address.city : '' }}
                  </p>
                </label>
              </div>
              <p v-if="errors.address_id" class="text-error text-label-sm mt-2">{{ errors.address_id }}</p>
            </div>
          </AnimateOnScroll>

          <!-- Phương thức thanh toán -->
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="100">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Phương thức thanh toán</h2>
              <div class="space-y-3">
<label :class="[
    'flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
    selectedPaymentMethod === 'CASH'
      ? 'border-primary bg-primary-container/10'
      : 'border-outline-variant/20 hover:border-outline-variant'
  ]">
                                    <input
                    v-model="selectedPaymentMethod"
                    value="CASH"
                    type="radio"
                    name="payment"
                    class="accent-primary"
                  />
                  <span class="material-symbols-outlined text-2xl text-on-surface-variant">payments</span>
                  <span class="font-sans text-label-md text-on-surface">Tiền mặt - COD</span>
                </label>
                <label :class="['flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                  selectedPaymentMethod === 'BANK_TRANSFER' ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedPaymentMethod" value="BANK_TRANSFER" type="radio" name="payment" class="accent-primary" />
                  <span class="material-symbols-outlined text-2xl text-on-surface-variant">account_balance</span>
                  <span class="font-sans text-label-md text-on-surface">Chuyển khoản ngân hàng</span>
                </label>
              </div>
              <p v-if="errors.payment_method" class="text-error text-label-sm mt-2">{{ errors.payment_method }}</p>
            </div>
          </AnimateOnScroll>

          <!-- Ghi chú -->
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="200">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Ghi chú</h2>
              <textarea v-model="note" rows="3" placeholder="Ghi chú cho đơn hàng..."
                class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary resize-none"></textarea>
            </div>
          </AnimateOnScroll>

        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="sticky top-24 space-y-4">
            <AnimateOnScroll animation="fade-left" :duration="700">
              <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6">
                <h3 class="font-serif text-headline-sm text-primary mb-4">Đơn hàng</h3>
                <div class="space-y-2 mb-4">
                  <div class="flex justify-between">
                    <span class="font-sans text-body-md text-on-surface-variant">Tạm tính</span>
                    <span class="font-sans text-body-md text-on-surface">{{ formatPrice(subtotal) }}</span>
                  </div>
                  <div v-if="voucher" class="flex justify-between text-secondary">
                    <span class="font-sans text-body-md">Giảm giá ({{ voucher.code }})</span>
                    <span class="font-sans text-body-md">-{{ formatPrice(discountAmount) }}</span>
                  </div>
                  <hr class="border-outline-variant/20" />
                  <div class="flex justify-between">
                    <span class="font-serif text-headline-sm text-primary">Tổng cộng</span>
                    <span class="font-serif text-headline-sm text-primary">{{ formatPrice(finalAmount) }}</span>
                  </div>
                </div>
                <BaseButton 
                  variant="primary" 
                  class="w-full justify-center" 
                  :disabled="!canSubmit" 
                  @click="submitOrder"
                >
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