<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'
const props = defineProps({
  product: { type: Object, default: null }, variants: { type: Array, default: () => [] },
  selectedVariant: { type: Object, default: null }, isOutOfStock: { type: Boolean, default: false },
  availableVariants: { type: Array, default: () => [] }, currentPrice: { type: Number, default: null },
  originalPrice: { type: Number, default: null }, canAddToCart: { type: Boolean, default: false },
  loading: { type: Boolean, default: false }, formatPrice: { type: Function, required: true },
  selectedQuantity: { type: Number, default: 1 }, note: { type: String, default: '' }
})
const emit = defineEmits(['select-variant', 'add-to-cart', 'update:selected-quantity', 'update:note'])
const getVariantStatus = (variant) => {
  if (variant.quantity <= 0) return 'out'
  if (props.selectedVariant?.id === variant.id) return 'selected'
  return 'available'
}
</script>
<template>
  <div class="space-y-6">
    <div class="flex items-start gap-2">
      <span v-if="product?.is_active === 'Ngừng kinh doanh'" class="px-3 py-1 bg-error-container/30 text-on-error-container rounded-full font-sans text-label-sm">Ngừng kinh doanh</span>
      <span v-else-if="isOutOfStock" class="px-3 py-1 bg-error-container/30 text-on-error-container rounded-full font-sans text-label-sm flex items-center gap-1"><span class="material-symbols-outlined text-sm">inventory_2</span> Tạm hết hàng</span>
      <span v-else-if="availableVariants.length > 0" class="px-3 py-1 bg-tertiary-container/30 text-on-tertiary-container rounded-full font-sans text-label-sm flex items-center gap-1"><span class="material-symbols-outlined text-sm">check_circle</span> Còn hàng</span>
    </div>
    <div>
      <h1 class="font-serif text-display-lg-mobile md:text-display-lg text-primary mb-2">{{ product?.product_name }}</h1>
      <p v-if="product?.short_description" class="font-sans text-body-lg text-on-surface-variant">{{ product.short_description }}</p>
    </div>
    <div class="flex items-baseline gap-3">
      <span v-if="currentPrice" class="font-serif text-display-lg-mobile text-primary">{{ formatPrice(currentPrice) }}</span>
      <span v-if="originalPrice" class="font-sans text-body-lg text-outline line-through">{{ formatPrice(originalPrice) }}</span>
      <span v-if="originalPrice" class="px-2 py-0.5 bg-secondary-container/50 text-on-secondary-container rounded-full font-sans text-label-sm">{{ formatPrice(currentPrice - originalPrice) }} OFF</span>
    </div>
    <div class="space-y-3">
      <label class="block font-sans text-label-sm text-on-surface">Kích cỡ</label>
      <div class="flex flex-wrap gap-2">
        <button v-for="variant in variants" :key="variant.id" @click="emit('select-variant', variant)" :disabled="variant.quantity <= 0" :class="['px-5 py-3 rounded-xl border-2 font-sans text-label-md transition-all duration-200 min-w-[80px]', getVariantStatus(variant) === 'selected' ? 'border-primary bg-primary-container/30 text-on-primary-container font-bold' : getVariantStatus(variant) === 'out' ? 'border-outline-variant/30 bg-surface-container-low text-outline opacity-50 cursor-not-allowed' : 'border-outline-variant/30 bg-surface-container-low text-on-surface hover:border-primary/50 hover:bg-primary-container/10']">
          <div class="text-center"><span>{{ variant.size }}</span><span v-if="variant.discount_price" class="block text-xs mt-0.5 text-secondary">Giảm giá</span></div>
        </button>
      </div>
      <p v-if="selectedVariant" class="font-sans text-label-sm text-on-surface-variant">Còn lại: <span :class="selectedVariant.quantity <= 5 ? 'text-error font-bold' : ''">{{ selectedVariant.quantity }}</span> sản phẩm</p>
    </div>
    <div v-if="selectedVariant" class="flex items-center gap-3">
      <label class="font-sans text-label-sm text-on-surface">Số lượng:</label>
      <div class="flex items-center gap-1 bg-surface-container-low rounded-full">
        <button @click="emit('update:selected-quantity', Math.max(1, selectedQuantity - 1))" :disabled="selectedQuantity <= 1" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-high transition-colors disabled:opacity-40"><span class="material-symbols-outlined text-sm">remove</span></button>
        <span class="w-12 text-center font-sans text-label-md text-on-surface">{{ selectedQuantity }}</span>
        <button @click="emit('update:selected-quantity', Math.min(selectedVariant.quantity, selectedQuantity + 1))" :disabled="selectedQuantity >= selectedVariant.quantity" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-high transition-colors disabled:opacity-40"><span class="material-symbols-outlined text-sm">add</span></button>
      </div>
    </div>
    <div class="space-y-2">
      <label class="block font-sans text-label-sm text-on-surface">Ghi chú</label>
      <input :value="note" @input="emit('update:note', $event.target.value)" type="text" placeholder="Ít đá, nhiều đường..." class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all" />
    </div>
    <BaseButton variant="primary" class="w-full justify-center" :disabled="!canAddToCart || loading" @click="emit('add-to-cart')">
      <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
      <span v-else class="material-symbols-outlined text-lg">shopping_cart</span>
      {{ isOutOfStock ? 'Tạm hết hàng' : loading ? 'Đang thêm...' : 'Thêm vào giỏ hàng' }}
    </BaseButton>
  </div>
</template>
