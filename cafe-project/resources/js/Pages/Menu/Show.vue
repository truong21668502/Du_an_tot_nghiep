<script setup>
import { onMounted, ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useProduct } from '@/Composables/useProduct'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import ProductGallery from './Partials/ProductGallery.vue'
import ProductInfo from './Partials/ProductInfo.vue'
import ProductReviews from './Partials/ProductReviews.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const { product, selectedVariant, selectedQuantity, loading, variants, isOutOfStock, availableVariants, currentPrice, originalPrice, selectVariant, canAddToCart, addToCart, submitReview, formatPrice, formatDate } = useProduct(page.props.product || null)
const activeImage = ref(null)
const note = ref('')
onMounted(() => {
  if (variants.value.length > 0) {
    const firstAvailable = variants.value.find(v => v.quantity > 0)
    if (firstAvailable) selectVariant(firstAvailable)
  }
  if (product.value?.image_url) activeImage.value = product.value.image_url
})
const handleAddToCart = () => {
  if (!selectedVariant.value) { toast.warning('Vui lòng chọn size'); return }
  addToCart(note.value)
}
const mainImage = computed(() => activeImage.value || product.value?.image_url)
const allImages = computed(() => {
  const imgs = product.value?.images || []
  if (product.value?.image_url) return [product.value.image_url, ...imgs.map(i => i.url)]
  return imgs.map(i => i.url)
})
</script>
<template>
  <div class="w-full">
    <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <nav class="flex items-center gap-2 mb-8 font-sans text-label-sm text-on-surface-variant">
          <Link href="/" class="hover:text-primary transition-colors">Trang chủ</Link>
          <span class="material-symbols-outlined text-sm">chevron_right</span>
          <Link href="/thuc-don" class="hover:text-primary transition-colors">Thực đơn</Link>
          <span class="material-symbols-outlined text-sm">chevron_right</span>
          <span class="text-primary font-semibold truncate">{{ product?.product_name }}</span>
        </nav>
      </AnimateOnScroll>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <AnimateOnScroll animation="fade-right" :duration="700" :delay="100">
          <ProductGallery :main-image="mainImage" :images="allImages" :product-name="product?.product_name" @update:active-image="activeImage = $event" />
        </AnimateOnScroll>
        <AnimateOnScroll animation="fade-left" :duration="700" :delay="200">
<ProductInfo :product="product" :variants="variants" :selected-variant="selectedVariant" :is-out-of-stock="isOutOfStock" :available-variants="availableVariants" :current-price="currentPrice" :original-price="originalPrice" :can-add-to-cart="canAddToCart" :loading="loading" :format-price="formatPrice" :selected-quantity="selectedQuantity" :max-quantity="selectedVariant?.quantity || 1" v-model:note="note" @select-variant="selectVariant" @add-to-cart="handleAddToCart" @update:selected-quantity="(qty) => selectedQuantity = Math.min(qty, selectedVariant?.quantity || 1)" />        
</AnimateOnScroll>
      </div>
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="300">
        <div v-if="product?.description" class="mt-16 bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-10">
          <h2 class="font-serif text-headline-md text-primary mb-6">Mô tả sản phẩm</h2>
          <div class="prose max-w-none font-sans text-body-md text-on-surface-variant leading-relaxed" v-html="product.description"></div>
        </div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="400">
        <ProductReviews :reviews="product?.reviews || []" :product-id="product?.id" :format-date="formatDate" :submit-review="submitReview" />
      </AnimateOnScroll>
    </div>
  </div>
</template>
