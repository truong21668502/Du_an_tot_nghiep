import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
export function useAuth() {
  const loading = ref(false)
  const errors = ref({})
  const login = async (credentials) => {
    loading.value = true
    errors.value = {}
    try {
      await router.post('/login', credentials, {
        onError: (err) => {
          errors.value = err
        }
      })
    } finally {
      loading.value = false
    }
  }
  const register = async (userData) => {
    loading.value = true
    errors.value = {}
    try {
      await router.post('/register', userData, {
        onError: (err) => {
          errors.value = err
        }
      })
    } finally {
      loading.value = false
    }
  }
  const loginWithGoogle = () => {
    window.location.href = '/auth/google'
  }
  const logout = () => {
    router.post('/logout')
  }
  return {
    loading,
    errors,
    login,
    register,
    loginWithGoogle,
    logout
  }
}
