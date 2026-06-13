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

    updateProfile,
    updatePassword,
    updateAvatar,

    storeAddress,
    updateAddress,
    deleteAddress,
    setDefaultAddress
  }
}