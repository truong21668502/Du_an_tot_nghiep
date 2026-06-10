<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import BaseBadge from '@/Components/Base/BaseBadge.vue'

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

const currentPrice = computed(() => {
  if (selectedVariant.value && selectedVariant.value.price) {
    return selectedVariant.value.price
  }
  return props.item.price
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
  router.get(route('customer.product.show', props.item.slug || props.item.id), {}, {
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

  router.post(route('customer.cart.add'), payload, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      addingToCart.value = false
      toast.success('Đã thêm vào giỏ hàng')
    },
    onError: (err) => {
      addingToCart.value = false
      if (err.product_id) {
        toast.error(err.product_id)
      } else {
        toast.error('Có lỗi xảy ra')
      }
    },
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

if (typeof document !== 'undefined') {
  document.addEventListener('click', handleClickOutside)
}
</script>

<template>
  <div 
    :data-category="item.category"
    class="menu-item bg-surface rounded-xl border border-outline-variant/20 overflow-hidden group hover:shadow-[0_8px_30px_rgba(74,55,40,0.08)] transition-all duration-500 flex flex-col h-full"
  >
    <!-- Image Container -->
    <div 
      class="aspect-[4/3] w-full relative overflow-hidden bg-surface-container-low cursor-pointer"
      @click="goToDetail"
    >
      <img 
        :alt="item.name" 
        :src="item.image"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
        loading="lazy"
      />
      
      <!-- Badge (if exists) -->
      <div v-if="item.badge" class="absolute top-4 left-4">
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
        @click="goToDetail"
      >
        <h3 class="font-serif text-headline-sm text-primary group-hover:text-secondary transition-colors line-clamp-1">
          {{ item.name }}
        </h3>
        <span class="font-serif text-headline-sm text-on-surface whitespace-nowrap">
          {{ formatPrice(currentPrice) }}
        </span>
      </div>

      <!-- Description -->
      <p 
        class="font-sans text-body-md text-on-surface-variant line-clamp-2 mb-4 flex-grow cursor-pointer"
        @click="goToDetail"
      >
        {{ item.description }}
      </p>

      <!-- Variant Selector + Add to Cart -->
      <div class="flex gap-2">
        <!-- Variant Dropdown -->
        <div class="variant-dropdown relative" v-if="hasVariants">
          <button
            type="button"
            @click="toggleVariantDropdown"
            class="h-[42px] px-3 rounded-full border border-outline-variant/50 text-on-surface-variant hover:border-secondary hover:text-secondary font-sans text-label-sm transition-colors duration-300 flex items-center gap-1 whitespace-nowrap"
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
              class="absolute bottom-full left-0 mb-2 w-40 bg-surface rounded-xl shadow-lg border border-outline-variant/20 py-1 z-50"
            >
              <button
                v-for="variant in variants"
                :key="variant.id"
                @click="selectVariant(variant)"
                class="w-full px-4 py-2.5 text-left text-sm hover:bg-surface-container-low transition-colors flex items-center justify-between"
                :class="{ 'text-secondary font-medium': selectedVariant?.id === variant.id || (!selectedVariant && variant.id === variants[0].id) }"
              >
                <span>{{ variant.size || 'Mặc định' }}</span>
                <span class="text-on-surface-variant text-xs">{{ formatPrice(variant.price) }}</span>
                <span
                  v-if="selectedVariant?.id === variant.id || (!selectedVariant && variant.id === variants[0].id)"
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
          @click="addToCart"
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