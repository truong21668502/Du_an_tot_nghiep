import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'

export function useCart(initialCart, initialItems, initialVoucherDiscount = 0, initialAppliedVoucher = null) {
  const items = ref(initialItems || [])
  const loading = ref(false)
  const errors = ref({})
  const voucherCode = ref(initialAppliedVoucher?.code || '')
  const appliedVoucher = ref(initialAppliedVoucher)
  const voucherDiscount = ref(Number(initialVoucherDiscount) || 0)

  const subtotal = computed(() => {
    return items.value.reduce((sum, item) => {
      const price = Number(item.variant?.price || item.product?.price || 0)
      return sum + (price * item.quantity)
    }, 0)
  })


  const total = computed(() => {
    return Math.max(0, subtotal.value - voucherDiscount.value)
  })

  const totalItems = computed(() => {
    return items.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(Number(price))
  }

  const updatePageData = (page) => {
    items.value = page.props.cartItems || []
    voucherDiscount.value = Number(page.props.voucherDiscount) || 0
    appliedVoucher.value = page.props.appliedVoucher || null
    if (appliedVoucher.value) {
      voucherCode.value = appliedVoucher.value.code || ''
    }
    loading.value = false
    errors.value = {}
  }

  const updateItem = (itemId, quantity) => {
    loading.value = true
    router.patch(
      route('customer.cart.update', itemId),
      { quantity },
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          updatePageData(page)
          // toast.success('Đã cập nhật giỏ hàng')
        },
        onError: (err) => {
          errors.value = err
          loading.value = false
          if (err.quantity) {
            toast.error(err.quantity)
          } else {
            toast.error('Có lỗi xảy ra khi cập nhật giỏ hàng')
          }
        },
      }
    )
  }

  const removeItem = (itemId) => {
    loading.value = true
    router.delete(
      route('customer.cart.remove', itemId),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          updatePageData(page)
          toast.success('Đã xóa sản phẩm khỏi giỏ hàng')
        },
        onError: (err) => {
          errors.value = err
          loading.value = false
          toast.error('Có lỗi xảy ra khi xóa sản phẩm')
        },
      }
    )
  }

  const clearCart = () => {
    if (!confirm('Bạn có chắc muốn xóa tất cả sản phẩm?')) return
    loading.value = true
    router.delete(
      route('customer.cart.clear'),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          items.value = page.props.cartItems || []
          voucherDiscount.value = 0
          appliedVoucher.value = null
          voucherCode.value = ''
          loading.value = false
          errors.value = {}
          toast.success('Đã xóa toàn bộ giỏ hàng')
        },
        onError: (err) => {
          errors.value = err
          loading.value = false
          toast.error('Có lỗi xảy ra khi xóa giỏ hàng')
        },
      }
    )
  }

  const applyVoucher = () => {
    if (!voucherCode.value.trim()) {
      toast.warning('Vui lòng nhập mã giảm giá')
      return
    }
    loading.value = true
    router.post(
      route('customer.cart.voucher.apply'),
      { code: voucherCode.value.trim() },
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          updatePageData(page)
          if (page.props.flash?.success) {
            toast.success(page.props.flash.success)
          }
        },
        onError: (err) => {
          errors.value = err
          loading.value = false
          if (err.voucher) {
            toast.error(err.voucher)
          } else {
            toast.error('Mã giảm giá không hợp lệ')
          }
        },
      }
    )
  }

  const removeVoucher = () => {
        

    loading.value = true
    router.delete(
      route('customer.cart.voucher.remove'),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          updatePageData(page)
          voucherCode.value = ''
          // toast.success('Đã xóa mã giảm giá')
        },
        onError: (err) => {
          errors.value = err
          loading.value = false
          toast.error('Có lỗi xảy ra khi xóa mã giảm giá')
        },
      }
    )
  }

  return {
    items,
    loading,
    errors,
    voucherCode,
    voucherDiscount,
    appliedVoucher,
    subtotal,
    total,
    totalItems,
    updateItem,
    removeItem,
    applyVoucher,
    removeVoucher,
    clearCart,
    formatPrice,
  }
}