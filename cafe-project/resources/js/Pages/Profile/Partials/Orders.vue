<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import ProfileLayout from '@/Layouts/ProfileLayout.vue'
import { useProfile } from '@/Composables/useProfile'
import Pagination from '@/Components/Main/Pagination.vue'
import { router } from '@inertiajs/vue3'

const onPageChange = (page) => {
    router.visit(route('profile.orders'), {
        data: { page },
        preserveScroll: true,
        preserveState: true,
    })
}
const getVisibleItems = (items) => items.slice(0, 2)

defineOptions({ layout: ProfileLayout })

defineProps({
  orders: Object,
})

const { formatPrice, formatDate, orderStatusMap, paymentStatusMap, orderTypeMap, cancelOrder } = useProfile()

// Modal state
const showDetailModal = ref(false)
const selectedOrder = ref(null)
const loadingDetail = ref(false)

const openOrderDetail = async (orderId) => {
  showDetailModal.value = true
  loadingDetail.value = true
  selectedOrder.value = null
  
  try {
    const response = await axios.get(route('profile.orders.detail', orderId))
    selectedOrder.value = response.data.order
  } catch (error) {
    console.error('Error loading order detail:', error)
  } finally {
    loadingDetail.value = false
  }
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedOrder.value = null
}

const retryPayment = (orderId) => {
    router.post(route('customer.orders.retry-payment', orderId))
}
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
      <Link href="/thuc-don" class="inline-block mt-3 text-secondary font-sans text-label-md hover:underline">Đặt món ngay</Link>
    </div>

    <div v-else class="space-y-4">
      <div v-for="order in orders.data" :key="order.id" class="border border-outline-variant/20 rounded-xl p-5 space-y-3">
        <div class="flex flex-wrap justify-between items-start gap-3">
          <div>
            <span class="font-sans text-label-md text-on-surface font-semibold">Đơn #{{ order.id }}</span>
            <span class="mx-2 text-outline-variant">|</span>
            <span class="font-sans text-label-sm text-on-surface-variant">{{ formatDate(order.created_at) }}</span>
            <span class="mx-2 text-outline-variant">|</span>
            <span class="font-sans text-label-sm text-on-surface-variant">{{ orderTypeMap[order.order_type] || order.order_type }}</span>
          </div>
          <div class="flex gap-2">
            <span :class="['px-3 py-1 rounded-full font-sans text-label-xs', orderStatusMap[order.status]?.color || 'bg-gray-100 text-gray-700']">
              {{ orderStatusMap[order.status]?.label || order.status }}
            </span>
            <span v-if="order.payment" :class="['px-3 py-1 rounded-full font-sans text-label-xs', paymentStatusMap[order.payment.status]?.color || 'bg-gray-100 text-gray-700']">
              {{ paymentStatusMap[order.payment.status]?.label || order.payment.status }}
            </span>
          </div>
        </div>

        <div class="space-y-2">
            <!-- Hiển thị giới hạn 2 món -->
            <div v-for="item in getVisibleItems(order.items)" :key="item.id" class="flex justify-between text-sm">
                <span class="font-sans text-body-md text-on-surface">
                    {{ item.product_name }} x{{ item.quantity }}
                </span>
                <span class="font-sans text-body-md text-on-surface">{{ formatPrice(item.subtotal) }}</span>
            </div>
            <!-- Hiển thị thông báo nếu còn món ẩn -->
            <div v-if="order.items.length > 2" class="text-xs text-outline italic">
                và {{ order.items.length - 2 }} món khác...
            </div>
        </div>

        <div class="flex justify-between items-center pt-3 border-t border-outline-variant/10">
          <div class="flex items-center gap-3">
            <span v-if="order.discount_amount > 0" class="font-sans text-label-sm text-secondary">
              Đã giảm {{ formatPrice(order.discount_amount) }}
            </span>
            <span class="font-serif text-headline-sm text-primary">{{ formatPrice(order.final_amount) }}</span>
          </div>
          <div class="flex gap-2">
            <button 
              @click="openOrderDetail(order.id)" 
              class="px-4 py-1.5 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors"
            >
              Chi tiết
            </button>
            <button 
              v-if="order.status === 'PENDING'" 
              @click="cancelOrder(order.id)" 
              class="px-4 py-1.5 bg-red-50 text-red-700 rounded-full font-sans text-label-sm hover:bg-red-100 transition-colors"
            >
              Hủy đơn
            </button> 
                  <button
        v-if="
            order.status === 'PENDING' &&
            order.payment?.method === 'BANK_TRANSFER' &&
            ['PENDING', 'FAILED'].includes(order.payment?.status)
        "
          @click="retryPayment(order.id)"
          class="px-4 py-1.5 bg-primary text-white rounded-full font-sans text-label-sm hover:opacity-90 transition"
      >
          Bổ sung thanh toán
      </button>
          </div>
        </div>
      </div>
      <Pagination 
    v-if="orders.last_page > 1"
    :current-page="orders.current_page"
    :total-pages="orders.last_page"
    :total-items="orders.total"
    @page-change="onPageChange"
/>
    </div>

    <!-- Order Detail Modal -->
    <Teleport to="body">
      <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDetailModal"></div>
        
        <!-- Modal -->
        <div class="relative bg-surface rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
          <!-- Loading -->
          <div v-if="loadingDetail" class="flex items-center justify-center py-20">
            <div class="w-10 h-10 border-4 border-primary-container border-t-primary rounded-full animate-spin"></div>
          </div>

          <!-- Content -->
          <template v-else-if="selectedOrder">
            <!-- Header -->
            <div class="sticky top-0 bg-surface border-b border-outline-variant/20 p-6 flex items-center justify-between">
              <h3 class="font-serif text-headline-sm text-primary">Chi tiết đơn #{{ selectedOrder.id }}</h3>
              <button @click="closeDetailModal" class="p-2 hover:bg-surface-container-low rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>

            <div class="p-6 space-y-6">
              <!-- Trạng thái -->
              <div class="flex flex-wrap gap-3">
                <div>
                  <span class="text-xs text-on-surface-variant block mb-1">Trạng thái đơn hàng</span>
                  <span :class="['px-3 py-1 rounded-full font-sans text-label-sm inline-block', orderStatusMap[selectedOrder.status]?.color]">
                    {{ orderStatusMap[selectedOrder.status]?.label }}
                  </span>
                </div>
                <div v-if="selectedOrder.payment">
                  <span class="text-xs text-on-surface-variant block mb-1">Thanh toán</span>
                  <span :class="['px-3 py-1 rounded-full font-sans text-label-sm inline-block', paymentStatusMap[selectedOrder.payment.status]?.color]">
                    {{ paymentStatusMap[selectedOrder.payment.status]?.label }}
                  </span>
                </div>
                <div>
                  <span class="text-xs text-on-surface-variant block mb-1">Hình thức</span>
                  <span class="px-3 py-1 rounded-full font-sans text-label-sm bg-surface-container-low text-on-surface">
                    {{ orderTypeMap[selectedOrder.order_type] }}
                  </span>
                </div>
              </div>

              <!-- Thông tin giao hàng (nếu có) -->
              <div v-if="selectedOrder.order_type === 'DELIVERY'" class="bg-surface-container-low rounded-xl p-4">
                <h4 class="font-sans text-label-md font-semibold text-on-surface mb-2">Thông tin nhận hàng</h4>
                <p class="font-sans text-body-md text-on-surface">{{ selectedOrder.receiver_name }} - {{ selectedOrder.receiver_phone }}</p>
                <p class="font-sans text-body-sm text-on-surface-variant mt-1">{{ selectedOrder.address_detail }}, {{ selectedOrder.ward }}, {{ selectedOrder.city }}</p>
              </div>

              <!-- Danh sách món -->
              <div>
                <h4 class="font-sans text-label-md font-semibold text-on-surface mb-3">Món đã đặt</h4>
                <div class="space-y-3">
                  <Link :href="route('product.show', { slug: item.product_slug })"  v-for="item in selectedOrder.items" :key="item.id" class="flex gap-4">
                    <img v-if="item.product_image" :src="item.product_image" :alt="item.product_name" class="w-16 h-16 rounded-lg object-cover" />
                    <div class="flex-1">
                      <p class="font-sans text-body-md text-on-surface font-medium">{{ item.product_name }}</p>
                      <p class="font-sans text-body-sm text-on-surface-variant">
                        <span v-if="item.size">Size {{ item.size }} · </span>
                        SL: {{ item.quantity }}
                      </p>
                      <p v-if="item.note" class="font-sans text-label-sm text-outline italic mt-1">Ghi chú: {{ item.note }}</p>
                    </div>
                    <div class="text-right">
                      <p class="font-sans text-body-md text-on-surface">{{ formatPrice(item.unit_price) }}</p>
                      <p class="font-sans text-label-sm text-on-surface-variant">= {{ formatPrice(item.subtotal) }}</p>
                    </div>
                  </Link>
                </div>
              </div>

              <!-- Tổng kết -->
              <div class="border-t border-outline-variant/20 pt-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-on-surface-variant">Tạm tính</span>
                  <span class="text-on-surface">{{ formatPrice(selectedOrder.total_amount) }}</span>
                </div>

                <div class="flex justify-between text-sm">
                  <span class="text-on-surface-variant">Phí ship</span>
                  <span class="text-on-surface-variant">{{ selectedOrder.shipping_fee ?? 0 }}</span>
                </div>
                <div v-if="selectedOrder.discount_amount > 0" class="flex justify-between text-sm">
                  <span class="text-secondary">Giảm giá</span>
                  <span class="text-secondary">-{{ formatPrice(selectedOrder.discount_amount) }}</span>
                </div>
                
                <div v-if="selectedOrder.coupon" class="flex justify-between text-sm">
                  <span class="text-on-surface-variant">Mã giảm giá</span>
                  <span class="text-on-surface-variant">{{ selectedOrder.coupon.code }}</span>
                </div>
    
                <div class="flex justify-between font-semibold pt-2 border-t border-outline-variant/20">
                  <span class="font-serif text-headline-sm text-primary">Tổng thanh toán</span>
                  <span class="font-serif text-headline-sm text-primary">{{ formatPrice(selectedOrder.final_amount) }}</span>
                </div>
              </div>

              <!-- Ghi chú -->
              <div v-if="selectedOrder.note" class="bg-surface-container-low rounded-xl p-4">
                <span class="text-xs text-on-surface-variant block mb-1">Ghi chú đơn hàng</span>
                <p class="font-sans text-body-md text-on-surface">{{ selectedOrder.note }}</p>
              </div>
            </div>
          </template>
        </div>
      </div>
    </Teleport>
  </div>
</template>