import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export function useCheckout() {
  const loading = ref(false)
  const errors = ref({})
  const selectedAddressId = ref(null)
  const selectedPaymentMethod = ref('CASH')
  const note = ref('')

  const paymentMethods = [
    { id: 'CASH', label: 'Thanh toán khi nhận hàng (COD)', icon: 'payments' },
    { id: 'BANK_TRANSFER', label: 'Chuyển khoản ngân hàng', icon: 'account_balance' },
    { id: 'MOMO', label: 'Ví MoMo', icon: 'wallet' },
    { id: 'VNPAY', label: 'VNPay', icon: 'credit_card' },
  ]

  const isOnlinePayment = computed(() => {
    return ['BANK_TRANSFER', 'MOMO', 'VNPAY'].includes(selectedPaymentMethod.value)
  })

  const submitCheckout = () => {
    loading.value = true
    errors.value = {}

    router.post(route('checkout.store'), {
      address_id: selectedAddressId.value,
      payment_method: selectedPaymentMethod.value,
      note: note.value,
    }, {
      preserveScroll: true,
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const confirmPayment = (orderId) => {
    loading.value = true
    errors.value = {}

    router.post(route('checkout.confirm-payment', orderId), {}, {
      preserveScroll: true,
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        loading.value = false
      }
    })
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
  }

  return {
    loading,
    errors,
    selectedAddressId,
    selectedPaymentMethod,
    note,
    paymentMethods,
    isOnlinePayment,
    submitCheckout,
    confirmPayment,
    formatPrice,
  }
}