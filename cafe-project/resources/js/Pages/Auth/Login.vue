<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import { useGoogleOneTap } from '@/Composables/useGoogleOneTap'

defineOptions({ layout: GuestLayout })

const { loading: googleLoading, errors: googleErrors, isBlocked, initialize, triggerGooglePrompt } = useGoogleOneTap()

const loading = ref(false)
const errors = ref({})
const form = ref({ email: '', password: '', remember: false })
const showPassword = ref(false)

const handleLogin = () => {
    loading.value = true
    router.post('/login', form.value, {
        onError: (err) => {
            errors.value = err
        },
        onFinish: () => {
            loading.value = false
        }
    })
}

initialize()
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-margin-mobile py-12 bg-surface-container-low">
    <div class="w-full max-w-md">
      <div class="text-center mb-10">
        <Link href="/" class="inline-block font-serif text-headline-md text-primary mb-3 hover:opacity-80 transition-opacity">Nắng Coffee</Link>
        <h1 class="font-serif text-headline-sm text-on-surface">Đăng nhập</h1>
        <p class="font-sans text-body-md text-on-surface-variant mt-2">Chào mừng bạn trở lại!</p>
      </div>
      <div class="bg-surface rounded-2xl shadow-soft border border-outline-variant/20 p-8">
        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="space-y-2">
            <label for="email" class="block font-sans text-label-sm text-on-surface">Email <span class="text-error">*</span></label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60"><span class="material-symbols-outlined text-lg">mail</span></span>
              <input id="email" v-model="form.email" type="email" required placeholder="email@example.com" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300" />
            </div>
            <p v-if="errors.email" class="text-error text-label-sm">{{ errors.email }}</p>
          </div>
          <div class="space-y-2">
            <label for="password" class="block font-sans text-label-sm text-on-surface">Mật khẩu <span class="text-error">*</span></label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60"><span class="material-symbols-outlined text-lg">lock</span></span>
              <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="Nhập mật khẩu" class="w-full pl-12 pr-12 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300" />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>
            <p v-if="errors.password" class="text-error text-label-sm">{{ errors.password }}</p>
          </div>
          <div class="flex justify-between items-center">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-outline-variant/50 text-primary focus:ring-secondary/30 cursor-pointer" />
              <span class="font-sans text-label-sm text-on-surface-variant">Ghi nhớ đăng nhập</span>
            </label>
            <Link href="/quen-mat-khau" class="font-sans text-label-sm text-secondary hover:text-secondary/80 transition-colors">Quên mật khẩu?</Link>
          </div>
          <p v-if="errors.message" class="text-error text-label-sm text-center bg-error-container/20 rounded-lg py-2 px-3">{{ errors.message }}</p>
          <p v-if="googleErrors.error" class="text-error text-label-sm text-center bg-error-container/20 rounded-lg py-2 px-3">{{ googleErrors.error }}</p>
          <BaseButton type="submit" variant="primary" :disabled="loading" class="w-full justify-center">
            <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
            {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
          </BaseButton>
        </form>
        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-outline-variant/30"></div></div>
          <div class="relative flex justify-center"><span class="px-4 bg-surface font-sans text-label-sm text-on-surface-variant">hoặc</span></div>
        </div>

        <button type="button" @click="triggerGooglePrompt" :disabled="googleLoading" class="w-full flex items-center justify-center gap-3 py-3 px-4 bg-surface-container-low hover:bg-surface-container border border-outline-variant/30 rounded-xl font-sans text-label-md text-on-surface transition-all duration-300 hover:shadow-sm disabled:opacity-50">
          <svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          <span v-if="googleLoading">Đang kết nối...</span>
          <span v-else-if="isBlocked">Đăng nhập với Google</span>
          <span v-else>Đăng nhập với Google</span>
        </button>

        <p v-if="isBlocked" class="text-amber-600 text-label-xs text-center mt-3 bg-amber-50 rounded-lg py-2 px-3">
          <span class="material-symbols-outlined text-sm align-middle">info</span>
          Nếu không thấy popup Google, vui lòng kiểm tra cài đặt "Đăng nhập qua bên thứ ba" trong trình duyệt
        </p>

        <p class="text-center mt-6 font-sans text-body-md text-on-surface-variant">
          Chưa có tài khoản? <Link href="/dang-ky" class="text-secondary font-semibold hover:text-secondary/80 transition-colors ml-1">Đăng ký ngay</Link>
        </p>
      </div>
    </div>
  </div>
</template>