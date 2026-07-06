<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import axios from 'axios'
import MenuHeader from '@/Pages/Menu/Partials/MenuHeader.vue'
import FilterSidebar from '@/Components/Main/FilterSidebar.vue'
import Pagination from '@/Components/Main/Pagination.vue'
import MenuItemCard from '@/Components/Main/MenuItemCard.vue'

const page = usePage()
const table = computed(() => page.props.table)
const categories = computed(() => page.props.categories || [])
const products = computed(() => page.props.products)
const propsFilters = computed(() => page.props.filters || {})

const showFilterSidebar = ref(false)
const showCart = ref(false)
const submitting = ref(false)

// Sort options
const sortOptions = [
  { value: 'newest', label: 'Mới nhất' },
  { value: 'oldest', label: 'Cũ nhất' },
  { value: 'name_asc', label: 'Tên A-Z' },
  { value: 'name_desc', label: 'Tên Z-A' },
  { value: 'price_asc', label: 'Giá thấp đến cao' },
  { value: 'price_desc', label: 'Giá cao đến thấp' },
]

const paginatedItems = computed(() => products.value?.data || [])
const totalPages = computed(() => products.value?.last_page || 1)

// URL filters
const currentUrl = computed(() => window.location.pathname)
const buildQuery = (overrides = {}) => {
  const current = { ...propsFilters.value, ...overrides }
  const params = new URLSearchParams()
  if (current.category && current.category !== 'all') params.set('category', current.category)
  if (current.search) params.set('search', current.search)
  if (current.min_price) params.set('min_price', current.min_price)
  if (current.max_price) params.set('max_price', current.max_price)
  if (current.sort_by && current.sort_by !== 'newest') params.set('sort_by', current.sort_by)
  if (current.page && current.page > 1) params.set('page', current.page)
  const qs = params.toString()
  return qs ? `${currentUrl.value}?${qs}` : currentUrl.value
}

const setCategory = (cat) => router.visit(buildQuery({ category: cat, page: 1 }), { preserveScroll: true, preserveState: true })
const setSearch = (s) => router.visit(buildQuery({ search: s, page: 1 }), { preserveScroll: true, preserveState: true })
const setPriceRange = (min, max) => router.visit(buildQuery({ min_price: min || null, max_price: max || null, page: 1 }), { preserveScroll: true, preserveState: true })
const setSortBy = (s) => router.visit(buildQuery({ sort_by: s, page: 1 }), { preserveScroll: true, preserveState: true })
const setPage = (p) => { router.visit(buildQuery({ page: p }), { preserveScroll: true, preserveState: true }); window.scrollTo({ top: 0, behavior: 'smooth' }) }
const toggleFilter = () => showFilterSidebar.value = !showFilterSidebar.value

// Format price
const formatPrice = (price) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price || 0)

// ========== GIỎ HÀNG (dùng ref reactive) ==========
const cartItems = ref([])
const voucherDiscount = ref(0)

// Đồng bộ từ page.props khi load trang
watch(() => page.props.cartItems, (newVal) => {
  if (newVal) cartItems.value = [...newVal]
}, { immediate: true, deep: true })

watch(() => page.props.voucherDiscount, (newVal) => {
  voucherDiscount.value = newVal || 0
}, { immediate: true })

const cartTotalItems = computed(() => cartItems.value.reduce((sum, item) => sum + item.quantity, 0))
const cartSubtotal = computed(() => cartItems.value.reduce((sum, item) => sum + (item.subtotal || 0), 0))
const cartTotalAmount = computed(() => Math.max(0, cartSubtotal.value - voucherDiscount.value))

// Thêm vào giỏ
const handleAddToCart = (item, variantId) => {
  axios.post(route('customer.cart.add'), {
    product_id: item.id,
    variant_id: variantId,
    quantity: 1,
  })
  .then(response => {
    if (response.data.cartItems) {
      cartItems.value = response.data.cartItems
    }
    toast.success(response.data.message || 'Đã thêm vào giỏ hàng')
  })
  .catch(error => {
    toast.error(error.response?.data?.message || 'Có lỗi xảy ra')
  })
}

// Debounce update số lượng
let updateTimer = null
const updateCartQuantity = (itemId, quantity) => {
  if (quantity < 1) return
  
  // Cập nhật local ngay lập tức
  const item = cartItems.value.find(i => i.id === itemId)
  if (item) {
    item.quantity = quantity
    item.subtotal = (item.variant?.price || item.product?.price || 0) * quantity
  }
  
  // Debounce gọi API
  clearTimeout(updateTimer)
  updateTimer = setTimeout(() => {
    axios.patch(route('customer.cart.update', itemId), { quantity })
      .then(response => {
        if (response.data.cartItems) {
          cartItems.value = response.data.cartItems
        }
      })
      .catch(() => toast.error('Không thể cập nhật số lượng'))
  }, 300)
}

// Xóa món
const removeFromCart = (itemId) => {
  axios.delete(route('customer.cart.remove', itemId))
    .then(response => {
      if (response.data.cartItems) {
        cartItems.value = response.data.cartItems
      }
      // toast.success(response.data.message || 'Đã xóa món')
    })
    .catch(() => toast.error('Không thể xóa món'))
}

// ========== THANH TOÁN ==========
const paymentMethod = ref('CASH')
const orderNote = ref('')

const handleSubmitOrder = () => {
  if (cartItems.value.length === 0) return
  
  submitting.value = true
  
  router.post(route('customer.orders.store'), {
    order_type: 'DINE_IN',
    table_id: table.value?.id,
    payment_method: paymentMethod.value,
    note: orderNote.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      orderNote.value = ''
      showCart.value = false
      submitting.value = false
      cartItems.value = []
      toast.success('Đặt hàng thành công!')
    },
    onError: (errors) => {
      submitting.value = false
      toast.error('Đặt hàng thất bại, vui lòng thử lại')
    }
  })
}
</script>
<!-- template giữ nguyên -->
<template>
  <div class="min-h-screen bg-surface-container-low">
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-outline-variant/20 shadow-sm">
      <div class="flex items-center justify-between h-16 px-4 max-w-[1440px] mx-auto">
        <div>
          <h1 class="font-serif text-headline-sm text-primary">{{ table?.table_name }}</h1>
          <p class="font-sans text-label-sm text-on-surface-variant">{{ table?.area }} · {{ table?.capacity }} người</p>
        </div>
        <button @click="showCart = true" class="relative p-2 rounded-full hover:bg-surface-container-high transition-colors">
          <span class="material-symbols-outlined text-2xl text-primary">shopping_bag</span>
          <span v-if="cartTotalItems > 0" class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-error text-on-error rounded-full text-xs flex items-center justify-center font-sans font-bold">
            {{ cartTotalItems }}
          </span>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="w-full max-w-[1440px] mx-auto px-4 py-6 pb-24">
      <div class="flex gap-8">
        <FilterSidebar
          :filters="propsFilters"
          :show="showFilterSidebar"
          @update:price-range="setPriceRange($event.min, $event.max)"
          @close="showFilterSidebar = false"
        />

        <div class="flex-1 min-w-0">
          <MenuHeader
            :categories="categories"
            :sort-options="sortOptions"
            :filters="propsFilters"
            @update:category="setCategory"
            @update:search="setSearch"
            @update:sort-by="setSortBy"
            @toggle-filter="toggleFilter"
          />

          <!-- Products Grid - DÙNG MenuItemCard -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <MenuItemCard
              v-for="item in paginatedItems"
              :key="item.id"
              :item="item"
            />
          </div>

          <div v-if="paginatedItems.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
            <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">search_off</span>
            <h3 class="font-serif text-headline-sm text-on-surface-variant mb-2">Không tìm thấy món nào</h3>
          </div>

          <Pagination
            v-if="totalPages > 1"
            :current-page="propsFilters.page || 1"
            :total-pages="totalPages"
            :total-items="products?.total || 0"
            @page-change="setPage"
          />
        </div>
      </div>
    </main>

    <!-- Floating Cart Button -->
    <div v-if="cartTotalItems > 0" class="fixed bottom-4 left-4 right-4 z-30 max-w-[500px] mx-auto">
      <button @click="showCart = true" class="w-full py-4 bg-primary text-on-primary rounded-2xl shadow-lg font-sans text-label-md hover:bg-primary/90 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined">shopping_bag</span>
        {{ cartTotalItems }} món · {{ formatPrice(cartTotalAmount) }} - Xem giỏ hàng
      </button>
    </div>

    <!-- Cart Drawer -->
    <Teleport to="body">
      <div v-if="showCart" class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showCart = false"></div>
        <div class="relative w-full max-w-md bg-surface h-full shadow-2xl flex flex-col animate-slide-left">
          <div class="flex items-center justify-between p-4 border-b border-outline-variant/20">
            <h2 class="font-serif text-headline-sm text-primary">Giỏ hàng</h2>
            <button @click="showCart = false" class="p-1 hover:bg-surface-container-low rounded-full">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>

          <div v-if="cartItems.length === 0" class="flex-1 flex flex-col items-center justify-center text-center p-8">
            <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">shopping_bag</span>
            <p class="font-sans text-body-md text-on-surface-variant">Chọn món để bắt đầu đặt</p>
          </div>

          <div v-else class="flex-1 overflow-y-auto p-4 space-y-3">
            <div v-for="item in cartItems" :key="item.id" class="bg-surface-container-low rounded-xl p-3 space-y-2">
              <div class="flex justify-between items-start">
                <div class="flex-1 min-w-0">
                  <p class="font-sans text-label-md text-on-surface truncate">{{ item.product?.name || 'Sản phẩm' }}</p>
                  <p v-if="item.variant?.size" class="font-sans text-label-sm text-on-surface-variant">Size: {{ item.variant.size }}</p>
                </div>
                <button @click="removeFromCart(item.id)" class="p-1 text-on-surface-variant hover:text-error">
                  <span class="material-symbols-outlined text-sm">delete</span>
                </button>
              </div>
              <div class="flex items-center gap-2">
                <div class="flex items-center gap-0.5 bg-surface rounded-full">
                  <button @click="updateCartQuantity(item.id, item.quantity - 1)" :disabled="item.quantity <= 1" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high disabled:opacity-30">
                    <span class="material-symbols-outlined text-sm">remove</span>
                  </button>
                  <span class="w-8 text-center font-sans text-label-sm">{{ item.quantity }}</span>
                  <button @click="updateCartQuantity(item.id, item.quantity + 1)" :disabled="item.quantity >= 99" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high disabled:opacity-30">
                    <span class="material-symbols-outlined text-sm">add</span>
                  </button>
                </div>
                <span class="font-sans text-label-md text-primary font-bold ml-auto">{{ formatPrice(item.subtotal) }}</span>
              </div>
            </div>
          </div>

          <div v-if="cartItems.length > 0" class="border-t border-outline-variant/20 p-4 space-y-3">
            <div class="space-y-2">
              <p class="font-sans text-label-sm text-on-surface-variant">Phương thức thanh toán</p>
              <label :class="['flex items-center gap-2 p-3 rounded-xl cursor-pointer', paymentMethod === 'CASH' ? 'bg-primary-container/20 border border-primary' : 'bg-surface-container-low border border-outline-variant/20']">
                <input type="radio" value="CASH" v-model="paymentMethod" class="accent-primary" />
                <span class="material-symbols-outlined text-on-surface-variant">payments</span>
                <span class="font-sans text-label-sm text-on-surface">Tiền mặt</span>
              </label>
              <label :class="['flex items-center gap-2 p-3 rounded-xl cursor-pointer', paymentMethod === 'BANK_TRANSFER' ? 'bg-primary-container/20 border border-primary' : 'bg-surface-container-low border border-outline-variant/20']">
                <input type="radio" value="BANK_TRANSFER" v-model="paymentMethod" class="accent-primary" />
                <span class="material-symbols-outlined text-on-surface-variant">account_balance</span>
                <span class="font-sans text-label-sm text-on-surface">Chuyển khoản</span>
              </label>
            </div>

            <textarea v-model="orderNote" rows="2" placeholder="Ghi chú cho đơn hàng..." class="w-full px-3 py-2 bg-surface border border-outline-variant/20 rounded-xl font-sans text-label-sm resize-none"></textarea>

            <div class="flex justify-between items-center">
              <span class="font-sans text-body-md text-on-surface-variant">Tổng cộng</span>
              <span class="font-serif text-headline-sm text-primary">{{ formatPrice(cartTotalAmount) }}</span>
            </div>

            <button @click="handleSubmitOrder" :disabled="submitting" class="w-full py-3 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
              <span v-if="submitting" class="material-symbols-outlined animate-spin text-lg">refresh</span>
              {{ submitting ? 'Đang gửi...' : paymentMethod === 'BANK_TRANSFER' ? 'Thanh toán VNPay' : 'Đặt món ngay' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.animate-slide-left { animation: slideLeft 0.3s ease-out; }
@keyframes slideLeft { from { transform: translateX(100%); } to { transform: translateX(0); } }
</style>