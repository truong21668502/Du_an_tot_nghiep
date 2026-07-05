<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import BaseBadge from '@/Components/Base/BaseBadge.vue'
import axios from 'axios'

const props = defineProps({
  item: {
    type: Object,
    required: true,
    validator: (obj) => {
      return obj.id && obj.name && obj.price && obj.image && obj.category
    }
  }
})

const addingToCart = ref(false)
const selectedVariant = ref(null)
const showVariantDropdown = ref(false)

const variants = computed(() => {
  return props.item.variants || []
})

const hasVariants = computed(() => {
  return variants.value.length > 0
})

const initSelectedVariant = () => {
  if (variants.value.length === 0) return null
  
  const discountedVariant = variants.value.find(v => v.discount_price)
  if (discountedVariant) return discountedVariant
  
  return variants.value.reduce((min, v) => 
    (v.current_price || v.price) < (min.current_price || min.price) ? v : min
  )
}

// GỘP LẠI 1 onMounted duy nhất
onMounted(() => {
  selectedVariant.value = initSelectedVariant()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const currentPrice = computed(() => {
  if (selectedVariant.value) {
    return selectedVariant.value.current_price || selectedVariant.value.price
  }
  return props.item.min_price || props.item.price
})

const originalPrice = computed(() => {
  if (selectedVariant.value?.discount_price) {
    return selectedVariant.value.price
  }
  return null
})

const hasDiscount = computed(() => {
  return selectedVariant.value?.discount_price != null || props.item.has_discount
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
  }).format(price)
}

const selectVariant = (variant) => {
  selectedVariant.value = variant
  showVariantDropdown.value = false
}

const goToDetail = () => {
  router.get(route('product.show', props.item.slug || props.item.id), {}, {
    preserveScroll: true,
  })
}

const addToCart = () => {
  addingToCart.value = true
  const payload = {
    product_id: props.item.id,
    quantity: 1,
  }
  
  if (selectedVariant.value) {
    payload.variant_id = selectedVariant.value.id
  } else if (hasVariants.value) {
    payload.variant_id = variants.value[0].id
  }

  axios.post(route('customer.cart.add'), payload)
    .then(response => {
      addingToCart.value = false
      toast.success('Đã thêm sản phẩm vào giỏ hàng')
    })
    .catch(error => {
      addingToCart.value = false
      if (error.response?.data?.errors?.product_id) {
        toast.error(error.response.data.errors.product_id)
      } else {
        toast.error('Có lỗi xảy ra')
      }
    })
}

const toggleVariantDropdown = () => {
  if (hasVariants.value) {
    showVariantDropdown.value = !showVariantDropdown.value
  }
}

const handleClickOutside = (event) => {
  const dropdown = event.target.closest('.variant-dropdown')
  if (!dropdown) {
    showVariantDropdown.value = false
  }
}
</script>

<template>
  <div 
    :data-category="item.category"
    @click.stop="goToDetail"
    class="menu-item bg-surface rounded-xl border border-outline-variant/20 overflow-hidden group hover:shadow-[0_8px_30px_rgba(74,55,40,0.08)] transition-all duration-500 flex flex-col h-full"
  >
    <!-- Image Container -->
    <div 
      class="aspect-[4/3] w-full relative overflow-hidden bg-surface-container-low cursor-pointer"
    >
      <img 
        :alt="item.name" 
        :src="item.image"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
        loading="lazy"
      />
      
      <!-- Discount Badge -->
      <div v-if="hasDiscount" class="absolute top-4 left-4">
        <BaseBadge variant="secondary">
          Giảm giá
        </BaseBadge>
      </div>
      
      <!-- Custom Badge -->
      <div v-else-if="item.badge" class="absolute top-4 left-4">
        <BaseBadge :variant="item.badgeVariant || 'secondary'">
          {{ item.badge }}
        </BaseBadge>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6 flex flex-col flex-grow">
      <!-- Title & Price -->
      <div 
        class="flex justify-between items-start mb-2 gap-4 cursor-pointer"
      >
        <h3 class="font-serif text-headline-sm text-primary group-hover:text-secondary transition-colors line-clamp-1">
          {{ item.name }}
        </h3>
        <div class="flex flex-col items-end">
          <span v-if="hasDiscount && originalPrice" class="text-xs text-on-surface-variant line-through">
            {{ formatPrice(originalPrice) }}
          </span>
          <span class="font-serif text-headline-sm whitespace-nowrap text-on-surface">
            {{ formatPrice(currentPrice) }}
          </span>
        </div>
      </div>

      <!-- Description -->
      <p 
        class="font-sans text-body-md text-on-surface-variant line-clamp-2 mb-4 flex-grow cursor-pointer"
      >
        {{ item.description }}
      </p>

      <!-- Variant Selector + Add to Cart -->
      <div class="flex gap-2">
        <!-- Variant Dropdown -->
        <div class="variant-dropdown relative" v-if="hasVariants">
          <button
            type="button"
            @click.stop="toggleVariantDropdown"
            class="py-3 px-3 rounded-full border border-outline-variant/50 text-on-surface-variant hover:border-secondary hover:text-secondary font-sans text-label-sm transition-colors duration-300 flex items-center gap-1 whitespace-nowrap"
          >
            <span class="text-xs truncate max-w-[60px]">
              {{ selectedVariant?.size || variants[0]?.size || 'Size' }}
            </span>
            <span class="material-symbols-outlined text-base transition-transform duration-200" :class="{ 'rotate-180': showVariantDropdown }">
              expand_more
            </span>
          </button>

          <!-- Dropdown Menu -->
          <Transition name="dropdown">
            <div
              v-if="showVariantDropdown"
              class="absolute bottom-full left-0 mb-2 w-44 bg-surface rounded-xl shadow-lg border border-outline-variant/20 py-1 z-50"
            >
              <button
                v-for="variant in variants"
                :key="variant.id"
                @click.stop="selectVariant(variant)"
                class="w-full px-4 py-2.5 text-left text-sm hover:bg-surface-container-low transition-colors flex items-center justify-between"
                :class="{ 'text-secondary font-medium bg-surface-container-low': selectedVariant?.id === variant.id }"
              >
                <span>{{ variant.size || 'Mặc định' }}</span>
                <div class="flex items-center gap-1">
                  <span v-if="variant.discount_price" class="text-xs text-on-surface-variant line-through">
                    {{ formatPrice(variant.price) }}
                  </span>
                  <span class="text-on-surface-variant">
                    {{ formatPrice(variant.current_price || variant.price) }}
                  </span>
                </div>
                <span
                  v-if="selectedVariant?.id === variant.id"
                  class="material-symbols-outlined text-secondary text-sm"
                >
                  check
                </span>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Add to Cart Button -->
        <button 
          class="flex-1 py-3 rounded-full border border-secondary text-secondary hover:bg-secondary hover:text-on-secondary font-sans text-label-md transition-colors duration-300 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="addingToCart"
          @click.stop="addToCart"
        >
          <span v-if="!addingToCart" class="material-symbols-outlined text-lg">add</span>
          <span v-else class="w-4 h-4 border-2 border-secondary border-t-transparent rounded-full animate-spin"></span>
          {{ addingToCart ? 'Đang thêm...' : 'Thêm vào giỏ' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.menu-item {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.menu-item.hidden-item {
  opacity: 0;
  transform: scale(0.95);
  pointer-events: none;
  position: absolute;
  visibility: hidden;
}

.dropdown-enter-active {
  transition: all 0.2s ease-out;
}

.dropdown-leave-active {
  transition: all 0.15s ease-in;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>