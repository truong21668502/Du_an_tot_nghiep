<script setup>
import { computed, onMounted } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useCheckout } from '@/Composables/useCheckout'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const cart = computed(() => page.props.cart)
const addresses = computed(() => page.props.addresses || [])
const subtotal = computed(() => page.props.subtotal || 0)
const voucherDiscount = computed(() => page.props.voucherDiscount || 0)
const total = computed(() => page.props.total || 0)
const { loading, errors, selectedAddressId, selectedPaymentMethod, note, paymentMethods, isOnlinePayment, submitCheckout, formatPrice } = useCheckout()
onMounted(() => {
  const defaultAddr = addresses.value.find(a => a.is_default)
  if (defaultAddr) selectedAddressId.value = defaultAddr.id
  else if (addresses.value.length > 0) selectedAddressId.value = addresses.value[0].id
})
</script>
<template>
  <div class="w-full">
    <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="mb-10">
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-2">Thanh toán</h1>
          <p class="text-body-md text-on-surface-variant">Xác nhận đơn hàng và chọn phương thức thanh toán</p>
        </div>
      </AnimateOnScroll>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="100">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Địa chỉ nhận hàng</h2>
              <div v-if="addresses.length === 0" class="text-center py-6">
                <p class="font-sans text-body-md text-on-surface-variant mb-4">Bạn chưa có địa chỉ nào</p>
                <Link href="/ho-so/dia-chi" class="text-secondary font-sans text-label-md hover:underline">Thêm địa chỉ mới</Link>
              </div>
              <div v-else class="space-y-3">
                <label v-for="addr in addresses" :key="addr.id" :class="['flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all', selectedAddressId === addr.id ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedAddressId" :value="addr.id" type="radio" name="address" class="mt-1 accent-primary" />
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="font-sans text-label-md text-on-surface">{{ addr.receiver_name }}</span>
                      <span class="text-outline-variant">|</span>
                      <span class="font-sans text-label-sm text-on-surface-variant">{{ addr.receiver_phone }}</span>
                      <span v-if="addr.is_default" class="px-2 py-0.5 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-sm">Mặc định</span>
                    </div>
                    <p class="font-sans text-body-md text-on-surface-variant">{{ addr.full_address }}</p>
                  </div>
                </label>
              </div>
              <p v-if="errors.address_id" class="text-error text-label-sm mt-2">{{ errors.address_id }}</p>
            </div>
          </AnimateOnScroll>
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="200">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Phương thức thanh toán</h2>
              <div class="space-y-3">
                <label v-for="method in paymentMethods" :key="method.id" :class="['flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all', selectedPaymentMethod === method.id ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedPaymentMethod" :value="method.id" type="radio" name="payment" class="accent-primary" />
                  <span class="material-symbols-outlined text-2xl text-on-surface-variant">{{ method.icon }}</span>
                  <span class="font-sans text-label-md text-on-surface">{{ method.label }}</span>
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
                <div v-if="cart" class="space-y-3 mb-4">
                  <div v-for="item in cart.items" :key="item.id" class="flex justify-between items-start gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                      <img :src="item.image" :alt="item.product_name" class="w-10 h-10 rounded-lg object-cover flex-shrink-0" />
                      <div class="min-w-0">
                        <p class="font-sans text-label-sm text-on-surface truncate">{{ item.product_name }}</p>
                        <p class="font-sans text-label-sm text-on-surface-variant">{{ item.size }} x{{ item.quantity }}</p>
                      </div>
                    </div>
                    <span class="font-sans text-label-sm text-on-surface whitespace-nowrap">{{ formatPrice(item.subtotal) }}</span>
                  </div>
                </div>
                <hr class="border-outline-variant/20 my-4" />
                <div class="space-y-2">
                  <div class="flex justify-between"><span class="font-sans text-body-md text-on-surface-variant">Tạm tính</span><span class="font-sans text-body-md text-on-surface">{{ formatPrice(subtotal) }}</span></div>
                  <div v-if="voucherDiscount > 0" class="flex justify-between text-secondary"><span class="font-sans text-body-md">Giảm giá</span><span class="font-sans text-body-md">-{{ formatPrice(voucherDiscount) }}</span></div>
                  <div class="flex justify-between pt-2 border-t border-outline-variant/20"><span class="font-serif text-headline-sm text-primary">Tổng cộng</span><span class="font-serif text-headline-sm text-primary">{{ formatPrice(total) }}</span></div>
                </div>
                <BaseButton variant="primary" class="w-full justify-center mt-6" :disabled="loading || !selectedAddressId" @click="submitCheckout">
                  <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
                  {{ loading ? 'Đang xử lý...' : isOnlinePayment ? 'Thanh toán ngay' : 'Đặt hàng' }}
                </BaseButton>
              </div>
            </AnimateOnScroll>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
