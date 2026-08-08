```vue
<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import { useGoogleOneTap } from '@/Composables/useGoogleOneTap'

defineOptions({ layout: GuestLayout })

const {
    loading: googleLoading,
    errors: googleErrors,
    isBlocked,
    initialize,
    triggerGooglePrompt
} = useGoogleOneTap()

const loading = ref(false)
const errors = ref({})
const form = ref({
    email: '',
    password: '',
    remember: false
})
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

/**
 * Render Google button
 */
const renderGoogleButton = () => {
    const button = document.getElementById('googleButtonBtn')

    if (!button) {
        console.warn('Google button container chưa tồn tại')
        return
    }

    if (!window.google?.accounts?.id) {
        console.warn('Google Identity Services chưa load')
        return
    }

    // Tránh render nhiều lần khi Inertia điều hướng
    button.innerHTML = ''

    window.google.accounts.id.renderButton(
        button,
        {
            theme: 'outline',
            size: 'large',
            width: 384,
            text: 'signin_with',
            shape: 'rectangular',
            logo_alignment: 'left'
        }
    )
}

/**
 * Khởi tạo Google One Tap
 */
initialize()

onMounted(async () => {
    await nextTick()

    // Google SDK đã có
    if (window.google?.accounts?.id) {
        renderGoogleButton()
        return
    }

    // Google SDK chưa có -> chờ callback từ SDK
    window.onGoogleLibraryLoad = () => {
        renderGoogleButton()
    }
})
</script>
```



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
    <!-- THAY THẾ BẰNG THẺ DIV NÀY -->
    <div class="w-full flex justify-center min-h-[44px]">
      <div id="googleButtonBtn"></div>
    </div>


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