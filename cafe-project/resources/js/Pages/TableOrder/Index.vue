<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTableOrder } from '@/Composables/useTableOrder'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import CartDrawer from './Partials/CartDrawer.vue'
const page = usePage()
const table = computed(() => page.props.table)
const products = computed(() => page.props.products || [])
const categories = computed(() => page.props.categories || [])
const {
  loading, expandedProduct, selectedList, totalItems, totalAmount, canOrder,
  paymentMethod,
  toggleProduct, addToOrder, removeFromOrder, updateQuantity, updateNote,
  submitOrder, formatPrice
} = useTableOrder()
const activeCategory = ref('all')
const showCart = ref(false)
const filteredProducts = computed(() => {
  if (activeCategory.value === 'all') return products.value
  return products.value.filter(p => p.category?.name === activeCategory.value)
})
const handleAdd = (product, variant) => {
  addToOrder(product.id, variant.id, variant.discount_price || variant.price)
}
const handleSubmitOrder = () => {
  console.log('Submitting order for table:', page.props.table)

  submitOrder(page.props.table.qr_code)
}
</script>
<template>
  <div class="min-h-screen bg-surface-container-low">
    <header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-outline-variant/20 shadow-sm">
      <div class="flex items-center justify-between h-16 px-4 max-w-[1280px] mx-auto">
        <div>
          <h1 class="font-serif text-headline-sm text-primary">{{ table?.table_name }}</h1>
          <p class="font-sans text-label-sm text-on-surface-variant">{{ table?.area }} · {{ table?.capacity }} người</p>
        </div>
        <button @click="showCart = true" class="relative p-2 rounded-full hover:bg-surface-container-high transition-colors">
          <span class="material-symbols-outlined text-2xl text-primary">shopping_bag</span>
          <span v-if="totalItems > 0" class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-error text-on-error rounded-full text-xs flex items-center justify-center font-sans font-bold">{{ totalItems }}</span>
        </button>
      </div>
      <div class="flex gap-1 px-4 pb-3 overflow-x-auto hide-scrollbar max-w-[1280px] mx-auto">
        <button @click="activeCategory = 'all'" :class="['px-4 py-1.5 rounded-full font-sans text-label-sm whitespace-nowrap transition-all', activeCategory === 'all' ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high']">Tất cả</button>
        <button v-for="cat in categories" :key="cat.id" @click="activeCategory = cat.name" :class="['px-4 py-1.5 rounded-full font-sans text-label-sm whitespace-nowrap transition-all', activeCategory === cat.name ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high']">{{ cat.name }}</button>
      </div>
    </header>
    <main class="max-w-[1280px] mx-auto px-4 py-6 pb-24">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <AnimateOnScroll v-for="product in filteredProducts" :key="product.id" animation="scale-in" :duration="400" :delay="50">
          <div class="bg-surface rounded-2xl overflow-hidden border border-outline-variant/10 hover:shadow-soft transition-all group cursor-pointer" @click="toggleProduct(product.id)">
            <div class="aspect-square overflow-hidden bg-surface-container-low relative">
              <img :src="product.image_url" :alt="product.product_name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
              <div v-if="product.variants[0]?.discount_price" class="absolute top-2 left-2 px-2 py-0.5 bg-error text-on-error rounded-full font-sans text-label-sm">Giảm</div>
            </div>
            <div class="p-3">
              <h3 class="font-sans text-label-md text-on-surface line-clamp-1">{{ product.product_name }}</h3>
              <p class="font-sans text-label-sm text-on-surface-variant line-clamp-1 mt-0.5">{{ product.short_description }}</p>
              <div class="flex items-center gap-2 mt-2">
                <span v-if="product.variants[0]" class="font-sans text-label-md text-primary font-bold">{{ formatPrice(product.variants[0].discount_price || product.variants[0].price) }}</span>
                <span v-if="product.variants[0]?.discount_price" class="font-sans text-label-sm text-outline line-through">{{ formatPrice(product.variants[0].price) }}</span>
              </div>
            </div>
          </div>
          <Teleport to="body">
            <div v-if="expandedProduct === product.id" class="fixed inset-0 z-50 flex items-end md:items-center justify-center" @click.self="expandedProduct = null">
              <div class="bg-surface rounded-t-3xl md:rounded-3xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto p-6 relative animate-slide-up">
                <button @click="expandedProduct = null" class="absolute top-4 right-4 p-1 hover:bg-surface-container-low rounded-full"><span class="material-symbols-outlined">close</span></button>
                <img :src="product.image_url" :alt="product.product_name" class="w-full aspect-square object-cover rounded-2xl mb-4" />
                <h2 class="font-serif text-headline-sm text-primary mb-1">{{ product.product_name }}</h2>
                <p class="font-sans text-body-md text-on-surface-variant mb-4">{{ product.short_description }}</p>
                <div class="space-y-2">
                  <button v-for="variant in product.variants" :key="variant.id" @click="handleAdd(product, variant); expandedProduct = null" class="w-full flex justify-between items-center p-3 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-colors">
                    <span class="font-sans text-label-md text-on-surface">{{ variant.size }}</span>
                    <span class="font-sans text-label-md text-primary font-bold">{{ formatPrice(variant.discount_price || variant.price) }}</span>
                  </button>
                </div>
              </div>
            </div>
          </Teleport>
        </AnimateOnScroll>
      </div>
    </main>
    <div v-if="canOrder" class="fixed bottom-4 left-4 right-4 z-30 max-w-[500px] mx-auto">
      <button @click="showCart = true" class="w-full py-4 bg-primary text-on-primary rounded-2xl shadow-lg font-sans text-label-md hover:bg-primary/90 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined">shopping_bag</span>
        {{ totalItems }} món · {{ formatPrice(totalAmount) }} - Xem giỏ hàng
      </button>
    </div>
    <CartDrawer
      :show="showCart"
      :selected-list="selectedList"
      :total-amount="totalAmount"
      :loading="loading"
      :format-price="formatPrice"
      :payment-method="paymentMethod"
      @close="showCart = false"
      @update-quantity="updateQuantity"
      @update-note="updateNote"
      @remove="removeFromOrder"
      @update:payment-method="paymentMethod = $event"
      @submit="handleSubmitOrder"
    />
  </div>
</template>
<style scoped>
.animate-slide-up { animation: slideUp 0.25s ease-out; }
@keyframes slideUp { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
