import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios';
import { toast } from "vue3-toastify";
export function useProduct(initialProduct = null) {
  const product = ref(initialProduct)
  const selectedVariant = ref(null)
  const selectedQuantity = ref(1)
  const loading = ref(false)
  const errors = ref({})
  const variants = computed(() => product.value?.variants || [])
  const isOutOfStock = computed(() => {
    if (!variants.value.length) return true
    return variants.value.every(v => v.quantity <= 0)
  })
  const availableVariants = computed(() => variants.value.filter(v => v.quantity > 0))
  const currentPrice = computed(() => {
    if (!selectedVariant.value) return null
    return selectedVariant.value.discount_price || selectedVariant.value.price
  })
  const originalPrice = computed(() => {
    if (!selectedVariant.value) return null
    return selectedVariant.value.discount_price ? selectedVariant.value.price : null
  })
  const selectVariant = (variant) => {
    if (variant.quantity <= 0) return
    selectedVariant.value = variant
    selectedQuantity.value = 1
  }
  const canAddToCart = computed(() => {
    if (!selectedVariant.value) return false
    return selectedVariant.value.quantity > 0
  })
  const addToCart = (note = '') => {
      if (!canAddToCart.value) return
      loading.value = true
      
      axios.post(route('customer.cart.add'), {
        product_id: product.value.id,
        variant_id: selectedVariant.value.id,
        quantity: selectedQuantity.value,
        note: note
      })
      .then(response => {
        loading.value = false
        toast.success('Đã thêm sản phẩm vào giỏ hàng')
      })
      .catch(error => {
        loading.value = false
        errors.value = error.response?.data?.errors || {}
        toast.error('Có lỗi xảy ra khi thêm vào giỏ hàng')
      })
    }
  const submitReview = (productId, data) => {
    loading.value = true
    router.post(`/menu/${productId}/review`, data, {
      preserveScroll: true,
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const formatPrice = (price) => {
    if (!price) return ''
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
  }
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' })
  }
  return {
    product, selectedVariant, selectedQuantity, loading, errors,
    variants, isOutOfStock, availableVariants, currentPrice, originalPrice,
    selectVariant, canAddToCart, addToCart, submitReview,
    formatPrice, formatDate
  }
}
