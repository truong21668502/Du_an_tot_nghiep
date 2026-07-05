import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import axios from 'axios'

export function useCart(initialCart, initialItems, initialVoucherDiscount = 0, initialAppliedVoucher = null) {
  const items = ref(initialItems || [])
  const loading = ref(false)
  const errors = ref({})
  const voucherCode = ref(initialAppliedVoucher?.code || '')
  const appliedVoucher = ref(initialAppliedVoucher)
  const voucherDiscount = ref(Number(initialVoucherDiscount) || 0)

  // Lưu trữ các timeout debounce cho từng itemId riêng biệt
  const debounceTimeouts = {}

  // Hàm tạo debounce thủ công gọn nhẹ
  const debounce = (id, fn, delay) => {
    return (...args) => {
      if (debounceTimeouts[id]) {
        clearTimeout(debounceTimeouts[id])
      }
      debounceTimeouts[id] = setTimeout(() => {
        fn(...args)
        delete debounceTimeouts[id]
      }, delay)
    }
  }

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

  // Logic thực tế gọi API cập nhật số lượng lên server
  const sendUpdateApi = async (itemId, quantity) => {
    loading.value = true
    errors.value = {}
    try {
      const response = await axios.patch(route('customer.cart.update', itemId), { quantity })
      if (response.data.success) {
        items.value = response.data.cartItems || []
      }
    } catch (err) {
      const apiErrors = err.response?.data?.errors || {}
      errors.value = apiErrors
      if (apiErrors.quantity) {
        toast.error(apiErrors.quantity[0])
      } else {
        toast.error(err.response?.data?.message || 'Có lỗi xảy ra khi cập nhật giỏ hàng')
      }
      
      // Nếu lỗi (ví dụ: quá số lượng tồn kho), giao diện tự động đồng bộ lại từ server props nếu cần
    } finally {
      loading.value = false
    }
  }

  // Hàm được gọi ở template/component Vue
  const updateItem = (itemId, quantity) => {
    if (quantity < 1) return

    // BƯỚC 1: Cập nhật ngay lập tức trên UI để người dùng thấy số thay đổi không bị trễ
    const targetItem = items.value.find(item => item.id === itemId)
    if (targetItem) {
      targetItem.quantity = quantity
      targetItem.subtotal = Number(targetItem.variant?.price || targetItem.product?.price || 0) * quantity
    }

    // BƯỚC 2: Trì hoãn gọi API . Nếu tiếp tục nhấn, thời gian sẽ được tính lại từ đầu
    const debouncedUpdate = debounce(itemId, sendUpdateApi, 300)
    debouncedUpdate(itemId, quantity)
  }

  const removeItem = async (itemId) => {
    // Xóa timeout đang chờ của item này nếu có trước khi xóa hẳn
    if (debounceTimeouts[itemId]) {
      clearTimeout(debounceTimeouts[itemId])
      delete debounceTimeouts[itemId]
    }

    loading.value = true
    errors.value = {}
    try {
      const response = await axios.delete(route('customer.cart.remove', itemId))
      if (response.data.success) {
        items.value = response.data.cartItems || []
        toast.success(response.data.message || 'Đã xóa sản phẩm khỏi giỏ hàng')
      }
    } catch (err) {
      toast.error(err.response?.data?.message || 'Có lỗi xảy ra khi xóa sản phẩm')
    } finally {
      loading.value = false
    }
  }

  const clearCart = () => {
    if (!confirm('Bạn có chắc muốn xóa tất cả sản phẩm?')) return
    
    // Xóa sạch tất cả các debounce đang chờ chạy
    Object.keys(debounceTimeouts).forEach(id => clearTimeout(debounceTimeouts[id]))
    
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

  const applyVoucher = async () => {
    if (!voucherCode.value.trim()) {
      toast.warning('Vui lòng nhập mã giảm giá')
      return
    }
    loading.value = true
    errors.value = {}
    try {
      const response = await axios.post(route('customer.cart.voucher.apply'), {
        code: voucherCode.value.trim()
      })
      if (response.data.success) {
        appliedVoucher.value = response.data.appliedVoucher || null
        voucherDiscount.value = Number(response.data.voucherDiscount) || 0
        if (appliedVoucher.value) {
          voucherCode.value = appliedVoucher.value.code || ''
        }
        toast.success(response.data.message || 'Đã áp dụng mã giảm giá')
      }
    } catch (err) {
      const apiErrors = err.response?.data?.errors || {}
      errors.value = { voucher: apiErrors.code?.[0] || err.response?.data?.message }
      toast.error(errors.value.voucher || 'Mã giảm giá không hợp lệ')
    } finally {
      loading.value = false
    }
  }

  const removeVoucher = async () => {
    loading.value = true
    errors.value = {}
    try {
      const response = await axios.delete(route('customer.cart.voucher.remove'))
      if (response.data.success) {
        appliedVoucher.value = null
        voucherDiscount.value = 0
        voucherCode.value = ''
        toast.success(response.data.message || 'Đã xóa mã giảm giá')
      }
    } catch (err) {
      toast.error(err.response?.data?.message || 'Có lỗi xảy ra khi xóa mã giảm giá')
    } finally {
      loading.value = false
    }
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