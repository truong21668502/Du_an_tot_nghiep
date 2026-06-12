import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
export function useProfile() {
  const loading = ref(false)
  const errors = ref({})
  const successMessage = ref('')
  const updateProfile = (data) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''
    router.put(route('profile.update'), data, {
      preserveScroll: true,
      onSuccess: () => { successMessage.value = 'Cập nhật thông tin thành công' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const updatePassword = (data) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''
    router.put(route('profile.update-password'), data, {
      preserveScroll: true,
      onSuccess: () => { successMessage.value = 'Đổi mật khẩu thành công' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
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
      onSuccess: () => { successMessage.value = 'Cập nhật ảnh đại diện thành công' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const storeAddress = (data) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''
    router.post(route('profile.addresses.store'), data, {
      preserveScroll: true,
      onSuccess: () => { successMessage.value = 'Thêm địa chỉ thành công' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const updateAddress = (addressId, data) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''
    router.put(route('profile.addresses.update', addressId), data, {
      preserveScroll: true,
      onSuccess: () => { successMessage.value = 'Cập nhật địa chỉ thành công' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const deleteAddress = (addressId) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''
    router.delete(route('profile.addresses.delete', addressId), {
      preserveScroll: true,
      onSuccess: () => { successMessage.value = 'Xóa địa chỉ thành công' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const setDefaultAddress = (addressId) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''
    router.put(route('profile.addresses.set-default', addressId), {}, {
      preserveScroll: true,
      onSuccess: () => { successMessage.value = 'Đã đặt làm địa chỉ mặc định' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const cancelOrder = (orderId) => {
    loading.value = true
    errors.value = {}
    successMessage.value = ''
    router.put(route('profile.orders.cancel', orderId), {}, {
      preserveScroll: true,
      onSuccess: () => { successMessage.value = 'Hủy đơn hàng thành công' },
      onError: (err) => { errors.value = err },
      onFinish: () => { loading.value = false }
    })
  }
  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
  }
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('vi-VN', {
      year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    })
  }
  const orderStatusMap = {
    PENDING: { label: 'Chờ duyệt', color: 'bg-secondary-container text-on-secondary-container' },
    PROCESSING: { label: 'Đang pha chế', color: 'bg-primary-container text-on-primary-container' },
    COMPLETED: { label: 'Hoàn thành', color: 'bg-tertiary-container text-on-tertiary-container' },
    CANCELLED: { label: 'Đã hủy', color: 'bg-error-container text-on-error-container' }
  }
  const paymentStatusMap = {
    PENDING: { label: 'Chưa thanh toán', color: 'bg-secondary-container text-on-secondary-container' },
    PAID: { label: 'Đã thanh toán', color: 'bg-tertiary-container text-on-tertiary-container' },
    REFUNDED: { label: 'Đã hoàn tiền', color: 'bg-primary-container text-on-primary-container' }
  }
  const orderTypeMap = {
    DINE_IN: 'Tại chỗ',
    TAKE_AWAY: 'Mang đi',
    DELIVERY: 'Giao hàng'
  }
  return {
    loading, errors, successMessage,
    updateProfile, updatePassword, updateAvatar,
    storeAddress, updateAddress, deleteAddress, setDefaultAddress,
    cancelOrder,
    formatPrice, formatDate,
    orderStatusMap, paymentStatusMap, orderTypeMap
  }
}
