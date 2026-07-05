import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useProfile() {
  const loading = ref(false)
  const errors = ref({})
  const successMessage = ref('')

  // Helper functions
  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(price)
  }

  const formatDate = (date) => {
    return new Intl.DateTimeFormat('vi-VN', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(date))
  }

  const orderStatusMap = {
    PENDING: { label: 'Chờ duyệt', color: 'bg-yellow-100 text-yellow-700' },
    PROCESSING: { label: 'Đang pha chế', color: 'bg-blue-100 text-blue-700' },
    READY: { label: 'Sẵn sàng', color: 'bg-green-100 text-green-700' },
    DELIVERING: { label: 'Đang giao', color: 'bg-purple-100 text-purple-700' },
    COMPLETED: { label: 'Hoàn thành', color: 'bg-green-100 text-green-700' },
    CANCELLED: { label: 'Đã hủy', color: 'bg-red-100 text-red-700' },
  }

  const paymentStatusMap = {
    PENDING: { label: 'Chờ thanh toán', color: 'bg-yellow-100 text-yellow-700' },
    PAID: { label: 'Đã thanh toán', color: 'bg-green-100 text-green-700' },
    FAILED: { label: 'Thất bại', color: 'bg-red-100 text-red-700' },
    REFUNDED: { label: 'Đã hoàn tiền', color: 'bg-gray-100 text-gray-700' },
  }

  const orderTypeMap = {
    DINE_IN: 'Tại chỗ',
    TAKE_AWAY: 'Mang đi',
    DELIVERY: 'Giao hàng',
  }

  const cancelOrder = (orderId) => {
    if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) return
    
    router.put(route('profile.orders.cancel', orderId), {}, {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Hủy đơn hàng thành công'
      },
      onError: (err) => {
        errors.value = err
      },
    })
  }

  const updateProfile = (data) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    router.put(route('profile.update'), data, {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Cập nhật thông tin thành công'
      },
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const updatePassword = (data) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    router.put(route('profile.update-password'), data, {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Đổi mật khẩu thành công'
      },
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const updateAvatar = (file) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    const formData = new FormData()
    formData.append('avatar', file)

    router.post(route('profile.update-avatar'), formData, {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Cập nhật ảnh đại diện thành công'
      },
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const storeAddress = (data, cb) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    router.post(route('profile.addresses.store'), data, {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Thêm địa chỉ thành công'
        cb?.()
      },
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const updateAddress = (id, data, cb) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    router.put(route('profile.addresses.update', id), data, {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Cập nhật địa chỉ thành công'
        cb?.()
      },
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const deleteAddress = (id) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    router.delete(route('profile.addresses.delete', id), {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Xóa địa chỉ thành công'
      },
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const setDefaultAddress = (id) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    router.put(route('profile.addresses.set-default', id), {}, {
      preserveScroll: true,
      onSuccess: () => {
        successMessage.value = 'Đã đặt làm địa chỉ mặc định'
      },
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  return {
    loading,
    errors,
    successMessage,

    // Helpers
    formatPrice,
    formatDate,
    orderStatusMap,
    paymentStatusMap,
    orderTypeMap,
    cancelOrder,

    // Actions
    updateProfile,
    updatePassword,
    updateAvatar,
    storeAddress,
    updateAddress,
    deleteAddress,
    setDefaultAddress,
  }
}