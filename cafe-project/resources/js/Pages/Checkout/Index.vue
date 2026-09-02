<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import MainLayout from '@/Layouts/MainLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import AddressFormModal from '@/Pages/Profile/Partials/Components/AddressFormModal.vue'
import { useShipping } from '@/Composables/useShipping'

defineOptions({ layout: MainLayout })

const { shopPos, maxDeliveryDistanceMeters, calculateShippingFee } = useShipping();

console.log('max delivery distance (meters):', maxDeliveryDistanceMeters.value)
const page = usePage()
const subtotal = computed(() => page.props.subtotal || 0)
const voucher = computed(() => page.props.voucher || null)
const addresses = computed(() => page.props.addresses || [])

const discountAmount = computed(() => voucher.value?.discount_amount || voucher.value?.discount || 0)

const loading = ref(false)
const errors = ref({})

const selectedAddressId = ref(null)
const selectedPaymentMethod = ref('CASH')
const note = ref('')

// Trạng thái cho Modal Thêm/Sửa địa chỉ
const showAddressModal = ref(false)
const editingAddress = ref(null)
const wards = ref([])

// ====== PHÍ GIAO HÀNG (còn thiếu ở bản trước) ======
const GOONG_API_KEY = import.meta.env.VITE_GOONG_API_KEY
const shippingFee = ref(0)
const shippingDistanceText = ref('')
const shippingDurationText = ref('')
const shippingDistance = ref(null) // km
const shippingDuration = ref(null) // phút
const isAddressOutOfRange = ref(false)
const calculatingShipping = ref(false)

const calculateShippingForAddress = async (address) => {
    if (!address?.latitude || !address?.longitude) {
        shippingFee.value = 0
        isAddressOutOfRange.value = false
        shippingDistanceText.value = ''
        shippingDurationText.value = ''
        return
    }

    calculatingShipping.value = true
    isAddressOutOfRange.value = false

    try {
      const url = `https://rsapi.goong.io/Direction?api_key=${GOONG_API_KEY}&origin=${shopPos.value.lat},${shopPos.value.lng}&destination=${address.latitude},${address.longitude}&vehicle=bike`
        const res = await fetch(url)
        const data = await res.json()

        if (!data.routes || data.routes.length === 0) {
            isAddressOutOfRange.value = true
            shippingFee.value = 0
            return
        }

        const leg = data.routes[0].legs[0]
        const distanceMeters = leg.distance.value

shippingDistance.value = Number((distanceMeters / 1000).toFixed(2))
shippingDuration.value = Math.ceil(leg.duration.value / 60)

        const fee = calculateShippingFee(distanceMeters)

        if (distanceMeters > maxDeliveryDistanceMeters.value || fee === null) {
            isAddressOutOfRange.value = true
            shippingFee.value = 0
        } else {
            shippingFee.value = fee
            shippingDistanceText.value = leg.distance.text
            shippingDurationText.value = leg.duration.text
        }
    } catch (e) {
        console.error('Shipping calculation error:', e)
        isAddressOutOfRange.value = true
        shippingFee.value = 0
    } finally {
        calculatingShipping.value = false
    }
}

// Tính lại phí ship mỗi khi đổi địa chỉ đã chọn (kể cả lúc auto-chọn địa chỉ mặc định)
watch(selectedAddressId, (newId) => {
    const address = addresses.value.find(a => a.id === newId)
    if (address) {
        calculateShippingForAddress(address)
    } else {
        shippingFee.value = 0
        isAddressOutOfRange.value = false
    }
})

// finalAmount giờ phải cộng thêm shippingFee
const finalAmount = computed(() =>
    Math.max(0, subtotal.value - discountAmount.value + shippingFee.value)
)

// Tự động chọn địa chỉ mặc định ban đầu
watch(addresses, (newVal) => {
    if (newVal.length > 0 && !selectedAddressId.value) {
        const defaultAddr = newVal.find(a => a.is_default) || newVal[0]
        selectedAddressId.value = defaultAddr.id
    }
}, { immediate: true })

// Lấy danh sách phường xã Đà Nẵng khi load trang
onMounted(async () => {
    try {
        const res = await fetch("https://provinces.open-api.vn/api/v2/p/48?depth=2")
        const data = await res.json()
        wards.value = data.wards || []
    } catch (e) {
        console.error(e)
    }
})

const openAddAddress = () => {
    editingAddress.value = null
    errors.value = {}
    showAddressModal.value = true
}

const openEditAddress = (addr) => {
    editingAddress.value = addr
    errors.value = {}
    showAddressModal.value = true
}

const handleAddressSubmit = async (formData) => {
    loading.value = true
    errors.value = {}

    try {
        let response
        if (editingAddress.value) {
            response = await axios.put(`/profile/user-addresses/${editingAddress.value.id}`, formData)
        } else {
            response = await axios.post('/profile/user-addresses', formData)
        }

        if (response.data.success) {
            showAddressModal.value = false
            router.reload({
                preserveScroll: true,
                preserveState: true,
                only: ['addresses'],
                onSuccess: () => {
                    if (!editingAddress.value && response.data.data?.id) {
                        selectedAddressId.value = response.data.data.id
                    }
                }
            })
        }
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {}
        }
    } finally {
        loading.value = false
    }
}

const formatPrice = (price) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)

const canSubmit = computed(() => {
    if (loading.value) return false
    if (!selectedAddressId.value) return false
    if (isAddressOutOfRange.value) return false
    return true
})

const submitOrder = () => {
    if (!canSubmit.value) return

    loading.value = true
    errors.value = {}
    console.log('Submitting order with data:', {
        order_type: 'DELIVERY',
        address_id: selectedAddressId.value,
        payment_method: selectedPaymentMethod.value,
        distance: shippingDistance.value,
        duration: shippingDuration.value,
        note: note.value,
    })
    console.log('distance:', shippingDistance.value, 'duration:', shippingDuration.value)

    router.post(route('customer.orders.store'), {
        order_type: 'DELIVERY',
        address_id: selectedAddressId.value,
        payment_method: selectedPaymentMethod.value,
        distance: shippingDistance.value,
        duration: shippingDuration.value,
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
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-4">
              <div class="flex justify-between items-center">
                <h2 class="font-serif text-headline-sm text-primary">Địa chỉ giao hàng</h2>
                <button
                    @click="openAddAddress"
                    type="button"
                    class="text-primary font-sans text-label-md hover:underline font-semibold flex items-center gap-1"
                >
                  <span class="material-symbols-outlined text-sm">add</span> Thêm địa chỉ mới
                </button>
              </div>

              <div v-if="addresses.length === 0" class="text-center py-6">
                <p class="font-sans text-body-md text-on-surface-variant">Bạn chưa có địa chỉ nhận hàng nào</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="address in addresses" :key="address.id"
                  :class="['flex justify-between items-start p-4 rounded-xl border-2 cursor-pointer transition-all',
                    selectedAddressId === address.id ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']"
                  @click="selectedAddressId = address.id"
                >
                  <div class="space-y-1 flex-1">
                    <div class="flex items-center gap-2">
                      <input v-model="selectedAddressId" :value="address.id" type="radio" name="address" class="sr-only" />
                      <p class="font-sans text-label-md text-on-surface font-bold">
                        {{ address.receiver_name }}
                      </p>
                      <span v-if="address.is_default" class="px-2 py-0.5 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-xs">
                        Mặc định
                      </span>
                    </div>
                    <p class="font-sans text-label-sm text-on-surface-variant">{{ address.receiver_phone }}</p>
                    <p class="font-sans text-label-sm text-on-surface-variant">
                      {{ address.address_detail }}{{ address.ward ? ', ' + address.ward : '' }}{{ address.city ? ', ' + address.city : '' }}
                    </p>
                  </div>

                  <!-- Nút sửa địa chỉ ngay tại checkout -->
                  <button
                    @click.stop="openEditAddress(address)"
                    type="button"
                    class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-low rounded-full transition-colors"
                    title="Sửa địa chỉ"
                  >
                    <span class="material-symbols-outlined text-lg">edit</span>
                  </button>
                </div>
              </div>

              <!-- Modal thêm / sửa địa chỉ tích hợp -->
              <AddressFormModal
                v-if="showAddressModal"
                :show="showAddressModal"
                :editing-address="editingAddress"
                :wards="wards"
                :loading="loading"
                :errors="errors"
                @close="showAddressModal = false"
                @submit="handleAddressSubmit"
              />

              <p v-if="errors.address_id" class="text-error text-label-sm mt-2">{{ errors.address_id }}</p>
            </div>
          </AnimateOnScroll>

          <!-- Phương thức thanh toán -->
          <AnimateOnScroll animation="fade-right" :duration="700" :delay="100">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8">
              <h2 class="font-serif text-headline-sm text-primary mb-4">Phương thức thanh toán</h2>
              <div class="space-y-3">
                <label :class="['flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                  selectedPaymentMethod === 'CASH' ? 'border-primary bg-primary-container/10' : 'border-outline-variant/20 hover:border-outline-variant']">
                  <input v-model="selectedPaymentMethod" value="CASH" type="radio" name="payment" class="accent-primary" />
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
          <!-- Tạm tính -->
          <div class="flex justify-between">
            <span class="font-sans text-body-md text-on-surface-variant">Tạm tính</span>
            <span class="font-sans text-body-md text-on-surface">{{ formatPrice(subtotal) }}</span>
          </div>
          
          <!-- Giảm giá -->
          <div v-if="voucher" class="flex justify-between text-secondary">
            <span class="font-sans text-body-md">Giảm giá ({{ voucher.code }})</span>
            <span class="font-sans text-body-md">-{{ formatPrice(discountAmount) }}</span>
          </div>
          
          <!-- Phí giao hàng -->
          <div class="flex justify-between">
            <span class="font-sans text-body-md text-on-surface-variant">Phí giao hàng</span>
            <span v-if="calculatingShipping" class="font-sans text-body-md text-on-surface-variant flex items-center gap-1">
              <span class="material-symbols-outlined text-sm animate-spin">refresh</span>
              Đang tính...
            </span>
            <span v-else-if="!selectedAddressId" class="font-sans text-body-md text-on-surface-variant">--</span>
            <span v-else-if="isAddressOutOfRange" class="font-sans text-body-md text-error">Không hỗ trợ</span>
            <span v-else class="font-sans text-body-md text-on-surface">{{ formatPrice(shippingFee) }}</span>
          </div>
          
          <!-- Thông báo ngoài phạm vi -->
          <p v-if="isAddressOutOfRange && selectedAddressId" class="text-error text-label-sm flex items-start gap-1">
            <span class="material-symbols-outlined text-sm">error</span>
            <span>Địa chỉ này vượt quá bán kính giao hàng (tối đa {{ maxDeliveryDistanceMeters / 1000 }}km), vui lòng chọn địa chỉ khác.</span>
          </p>
          
          <!-- Khoảng cách và thời gian (hiển thị khi có) -->
          <div v-if="!isAddressOutOfRange && shippingDistanceText && selectedAddressId" class="space-y-1">
            <div class="flex justify-between text-label-sm text-on-surface-variant">
              <span>Khoảng cách</span>
              <span>{{ shippingDistanceText }}</span>
            </div>
            <div v-if="shippingDurationText" class="flex justify-between text-label-sm text-on-surface-variant">
              <span>Thời gian vận chuyển ước tính</span>
              <span>{{ shippingDurationText }}</span>
            </div>
          </div>
          
          <hr class="border-outline-variant/20" />
          
          <!-- Tổng cộng -->
          <div class="flex justify-between">
            <span class="font-serif text-headline-sm text-primary">Tổng cộng</span>
            <span class="font-serif text-headline-sm text-primary">{{ formatPrice(finalAmount) }}</span>
          </div>
        </div>
        
        <!-- Nút đặt hàng -->
        <BaseButton 
          variant="primary" 
          class="w-full justify-center" 
          :disabled="!canSubmit" 
          @click="submitOrder"
        >
          <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
          {{ loading ? 'Đang xử lý...' : selectedPaymentMethod === 'BANK_TRANSFER' ? 'Thanh toán VNPay' : 'Đặt món' }}
        </BaseButton>
        <div v-if="errors && Object.keys(errors).length" class="mt-4 p-3 bg-error-container/10 border border-error/30 rounded-xl text-error space-y-1">
          <p v-for="(msg, key) in errors" :key="key" class="font-sans text-label-sm">
            <span class="material-symbols-outlined text-sm align-middle mr-1">error</span>
            {{ msg }}
          </p>
        </div>
        
        <!-- Thông báo lỗi -->
        <div v-if="!canSubmit && selectedAddressId && isAddressOutOfRange" class="mt-3 text-error text-label-sm text-center">
          Vui lòng chọn địa chỉ khác trong phạm vi giao hàng
        </div>
        <div v-if="!canSubmit && !selectedAddressId" class="mt-3 text-on-surface-variant text-label-sm text-center">
          Vui lòng chọn địa chỉ giao hàng
        </div>
      </div>
    </AnimateOnScroll>
  </div>
</div>
      </div>
    </div>
  </div>
</template>