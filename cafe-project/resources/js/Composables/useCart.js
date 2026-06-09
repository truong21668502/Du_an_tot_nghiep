import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
export function useCart(initialCart = null, initialItems = []) {
  const cart = ref(initialCart)
  const items = ref(initialItems)
  const loading = ref(false)
  const errors = ref({})
  const voucherCode = ref('')
  const voucherDiscount = ref(0)
  const appliedVoucher = ref(null)
  const subtotal = computed(() => {
    return items.value.reduce((sum, item) => {
      const price = item.variant?.price || item.product?.price || 0
      return sum + price * item.quantity
    }, 0)
  })
  const taxAmount = computed(() => Math.round(subtotal.value * 0.08))
  const total = computed(() => subtotal.value + taxAmount.value - voucherDiscount.value)
  const totalItems = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
  const updateItem = (itemId, quantity) => {
    loading.value = true
    router.put(`/gio-hang/san-pham/${itemId}`, { quantity }, {
      preserveScroll: true,
      onSuccess: (page) => {
        const updated = page.props.cartItems?.find(i => i.id === itemId)
        if (updated) {
          const idx = items.value.findIndex(i => i.id === itemId)
          if (idx !== -1) items.value[idx] = updated
        }
      },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const removeItem = (itemId) => {
    loading.value = true
    router.delete(`/gio-hang/san-pham/${itemId}`, {
      preserveScroll: true,
      onSuccess: () => {
        items.value = items.value.filter(i => i.id !== itemId)
      },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const applyVoucher = () => {
    if (!voucherCode.value.trim()) {
      errors.value = { voucher: 'Vui long nhap ma voucher' }
      return
    }
    loading.value = true
    router.post('/gio-hang/voucher', { code: voucherCode.value }, {
      preserveScroll: true,
      onSuccess: (page) => {
        appliedVoucher.value = page.props.appliedVoucher || null
        voucherDiscount.value = page.props.voucherDiscount || 0
        errors.value = {}
      },
      onError: (err) => {
        voucherDiscount.value = 0
        appliedVoucher.value = null
        errors.value = err
      },
      onFinish: () => { loading.value = false }
    })
  }
  const removeVoucher = () => {
    voucherCode.value = ''
    voucherDiscount.value = 0
    appliedVoucher.value = null
  }
  const clearCart = () => {
    loading.value = true
    router.delete('/gio-hang', {
      preserveScroll: true,
      onSuccess: () => {
        items.value = []
        cart.value = null
        voucherDiscount.value = 0
        appliedVoucher.value = null
        voucherCode.value = ''
      },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
  }
  return {
    cart, items, loading, errors, voucherCode, voucherDiscount, appliedVoucher,
    subtotal, taxAmount, total, totalItems,
    updateItem, removeItem, applyVoucher, removeVoucher, clearCart, formatPrice
  }
}
