<script setup>
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useCart } from '@/Composables/useCart'
import CartEmpty from './Cart/Partials/CartEmpty.vue'
import CartItem from './Cart/Partials/CartItem.vue'
import CartSummary from './Cart/Partials/CartSummary.vue'
import VoucherInput from './Cart/Partials/VoucherInput.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MainLayout })

const { props } = usePage()
const initialCart = props.cart || null
const initialItems = props.cartItems || []
const initialVoucherDiscount = props.voucherDiscount || 0
const initialAppliedVoucher = props.appliedVoucher || null

const {
  items, loading, errors, voucherCode, voucherDiscount, appliedVoucher,
  subtotal, taxAmount, total, totalItems,
  updateItem, removeItem, applyVoucher, removeVoucher, clearCart, formatPrice
} = useCart(initialCart, initialItems, initialVoucherDiscount, initialAppliedVoucher)
</script>
<template>
  <div class="w-full">
    <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="mb-12">
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-2">Giỏ hàng</h1>
          <p class="text-body-md text-on-surface-variant">
            {{ items.length > 0 ? `${totalItems} sản phẩm trong giỏ hàng` : 'Giỏ hàng của bạn đang trống' }}
          </p>
        </div>
      </AnimateOnScroll>
      <CartEmpty v-if="items.length === 0" />
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-4">
          <AnimateOnScroll
            v-for="(item, index) in items"
            :key="item.id"
            animation="fade-right"
            :duration="500"
            :delay="index * 80"
          >
            <CartItem
              :item="item"
              :format-price="formatPrice"
              :total-items="totalItems"
              :loading="loading"
              @update-quantity="(id, qty) => updateItem(id, qty)"
              @remove="(id) => removeItem(id)"
            />
          </AnimateOnScroll>
          <AnimateOnScroll animation="fade-up" :duration="500" :delay="300">
            <button
              @click="clearCart"
              :disabled="loading"
              class="flex items-center gap-2 px-4 py-2 text-error hover:bg-error-container/20 rounded-lg transition-colors font-sans text-label-sm disabled:opacity-50"
            >
              <span class="material-symbols-outlined text-lg">delete</span>
              Xóa tất cả sản phẩm
            </button>
          </AnimateOnScroll>
        </div>
        <div class="lg:col-span-1">
          <div class="sticky top-24 space-y-4">
            <AnimateOnScroll animation="fade-left" :duration="700">
              <VoucherInput
                v-model="voucherCode"
                :applied-voucher="appliedVoucher"
                :discount="voucherDiscount"
                :error="errors.voucher"
                :format-price="formatPrice"
                :loading="loading"
                @apply="applyVoucher"
                @remove="removeVoucher"
              />
            </AnimateOnScroll>
            <AnimateOnScroll animation="fade-left" :duration="700" :delay="200">
              <CartSummary
                :subtotal="subtotal"
                :discount="voucherDiscount"
                :total="total"
                :format-price="formatPrice"
                :item-count="totalItems"
                :loading="loading"
              />
            </AnimateOnScroll>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
