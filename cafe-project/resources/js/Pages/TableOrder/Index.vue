<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useCart } from '@/Composables/useCart'
import MenuHeader from '@/Pages/Menu/Partials/MenuHeader.vue'
import MenuGrid from '@/Components/Main/MenuGrid.vue'
import FilterSidebar from '@/Components/Main/FilterSidebar.vue'
import Pagination from '@/Components/Main/Pagination.vue'
import CartDrawer from './Partials/CartDrawer.vue'

const page = usePage()
const table = computed(() => page.props.table)
const categories = computed(() => page.props.categories || [])
const products = computed(() => page.props.products)
const propsFilters = computed(() => page.props.filters || {})

const showFilterSidebar = ref(false)
const loading = ref(false)

// Lấy base URL hiện tại
const currentUrl = computed(() => window.location.pathname)

// Sort options
const sortOptions = [
  { value: 'newest', label: 'Mới nhất' },
  { value: 'oldest', label: 'Cũ nhất' },
  { value: 'name_asc', label: 'Tên A-Z' },
  { value: 'name_desc', label: 'Tên Z-A' },
  { value: 'price_asc', label: 'Giá thấp đến cao' },
  { value: 'price_desc', label: 'Giá cao đến thấp' },
]

// Computed từ page props
const paginatedItems = computed(() => products.value?.data || [])
const totalPages = computed(() => products.value?.last_page || 1)
const totalItems = computed(() => products.value?.total || 0)

// Filter functions - DÙNG URL HIỆN TẠI thay vì route('menu')
const buildQuery = (overrides = {}) => {
  const current = { ...propsFilters.value, ...overrides }
  const params = new URLSearchParams()
  
  if (current.category && current.category !== 'all') params.set('category', current.category)
  if (current.search) params.set('search', current.search)
  if (current.min_price) params.set('min_price', current.min_price)
  if (current.max_price) params.set('max_price', current.max_price)
  if (current.rating) params.set('rating', current.rating)
  if (current.sort_by && current.sort_by !== 'newest') params.set('sort_by', current.sort_by)
  if (current.page && current.page > 1) params.set('page', current.page)
  
  const qs = params.toString()
  return qs ? `${currentUrl.value}?${qs}` : currentUrl.value
}

const setCategory = (category) => {
  router.visit(buildQuery({ category, page: 1 }), { preserveScroll: true, preserveState: true })
}

const setSearch = (search) => {
  router.visit(buildQuery({ search, page: 1 }), { preserveScroll: true, preserveState: true })
}

const setPriceRange = (min, max) => {
  router.visit(buildQuery({ min_price: min || null, max_price: max || null, page: 1 }), { preserveScroll: true, preserveState: true })
}

const setRating = (rating) => {
  router.visit(buildQuery({ rating, page: 1 }), { preserveScroll: true, preserveState: true })
}

const setSortBy = (sortBy) => {
  router.visit(buildQuery({ sort_by: sortBy, page: 1 }), { preserveScroll: true, preserveState: true })
}

const setPage = (page) => {
  router.visit(buildQuery({ page }), { preserveScroll: true, preserveState: true })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const toggleFilter = () => {
  showFilterSidebar.value = !showFilterSidebar.value
}

// Cart
const {
  items: cartItems,
  loading: cartLoading,
  total,
  totalItems: cartTotalItems,
  updateItem,
  removeItem,
  formatPrice,
} = useCart(
  page.props.cart || null,
  page.props.cartItems || [],
  page.props.voucherDiscount || 0,
  page.props.appliedVoucher || null
)

const showCart = ref(false)
const paymentMethod = ref('CASH')

// Submit order
// Trong TableOrder/Index.vue, sửa handleSubmitOrder:
const handleSubmitOrder = () => {
  if (cartItems.value.length === 0) return
  
  router.post(route('customer.orders.store'), {
    order_type: 'DINE_IN',
    table_id: table.value?.id,
    payment_method: paymentMethod.value,
  }, {
    preserveScroll: true,
    onSuccess: (page) => {
      cartItems.value = [] 
      showCart.value = false
    },
    onError: (errors) => {
      console.error('Order error:', errors)
    }
  })
}

const cartPage = () => {
  return router.get(route('customer.cart.index'));
}
</script>

<template>
  <div class="min-h-screen bg-surface-container-low">
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-outline-variant/20 shadow-sm">
      <div class="flex items-center justify-between h-16 px-4 max-w-[1440px] mx-auto">
        <div>
          <h1 class="font-serif text-headline-sm text-primary">{{ table?.table_name }}</h1>
          <p class="font-sans text-label-sm text-on-surface-variant">{{ table?.area }} · {{ table?.capacity }} người</p>
        </div>
        <button 
          @click="cartPage" 
          class="relative p-2 rounded-full hover:bg-surface-container-high transition-colors"
        >
          <span class="material-symbols-outlined text-2xl text-primary">shopping_bag</span>
          <span 
            v-if="cartTotalItems > 0" 
            class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-error text-on-error rounded-full text-xs flex items-center justify-center font-sans font-bold"
          >
            {{ cartTotalItems }}
          </span>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="w-full max-w-[1440px] mx-auto px-4 py-6 pb-24">
      <div class="flex gap-8">
        <!-- Filter Sidebar -->
        <FilterSidebar
          :filters="propsFilters"
          :show="showFilterSidebar"
          @update:price-range="setPriceRange($event.min, $event.max)"
          @update:rating="setRating"
          @close="showFilterSidebar = false"
        />

        <div class="flex-1 min-w-0">
          <!-- Menu Header -->
          <MenuHeader
            :categories="categories"
            :sort-options="sortOptions"
            :filters="propsFilters"
            @update:category="setCategory"
            @update:search="setSearch"
            @update:sort-by="setSortBy"
            @toggle-filter="toggleFilter"
          />

          <!-- Products Grid -->
          <MenuGrid
            :items="paginatedItems"
            :loading="loading"
          />

          <!-- Pagination -->
          <Pagination
            v-if="totalPages > 1"
            :current-page="propsFilters.page || 1"
            :total-pages="totalPages"
            :total-items="totalItems"
            @page-change="setPage"
          />
        </div>
      </div>
    </main>

    <!-- Floating Cart Button -->
    <div 
      v-if="cartTotalItems > 0" 
      class="fixed bottom-4 left-4 right-4 z-30 max-w-[500px] mx-auto"
    >
      <button 
        @click="cartPage" 
        class="w-full py-4 bg-primary text-on-primary rounded-2xl shadow-lg font-sans text-label-md hover:bg-primary/90 transition-all flex items-center justify-center gap-2"
      >
        <span class="material-symbols-outlined">shopping_bag</span>
        {{ cartTotalItems }} món · {{ formatPrice(total) }} - Xem giỏ hàng
      </button>
    </div>

    <!-- Cart Drawer -->
    <CartDrawer
      :show="showCart"
      :selected-list="cartItems"
      :total-amount="total"
      :loading="cartLoading"
      :format-price="formatPrice"
      :payment-method="paymentMethod"
      @close="showCart = false"
      @update-quantity="(itemId, qty) => updateItem(itemId, qty)"
      @remove="removeItem"
      @update:payment-method="paymentMethod = $event"
      @submit="handleSubmitOrder"
    />
  </div>
</template>