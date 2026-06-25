<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: GuestLayout })
const props = defineProps({ token: String, email: String })
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' })
const showPassword = ref(false)
const showConfirm = ref(false)
</script>
<template>
  <div class="min-h-screen flex items-center justify-center px-margin-mobile py-12 bg-surface-container-low">
    <div class="w-full max-w-md">
      <div class="text-center mb-10">
        <Link href="/" class="inline-block font-serif text-headline-md text-primary mb-3 hover:opacity-80 transition-opacity">Nắng Coffee</Link>
        <h1 class="font-serif text-headline-sm text-on-surface">Đặt lại mật khẩu</h1>
        <p class="font-sans text-body-md text-on-surface-variant mt-2">Nhập mật khẩu mới cho tài khoản của bạn.</p>
      </div>
      <div class="bg-surface rounded-2xl shadow-soft border border-outline-variant/20 p-8">
        <form @submit.prevent="form.post('/reset-password')" class="space-y-5">
          <div class="space-y-2">
            <label for="email" class="block font-sans text-label-sm text-on-surface">Email</label>
            <input id="email" v-model="form.email" type="email" disabled class="w-full px-4 py-3 bg-surface-container-high border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface-variant opacity-60 cursor-not-allowed" />
          </div>
          <div class="space-y-2">
            <label for="password" class="block font-sans text-label-sm text-on-surface">Mật khẩu mới</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60"><span class="material-symbols-outlined text-lg">lock</span></span>
              <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="Ít nhất 8 ký tự" class="w-full pl-12 pr-12 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300" />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60 hover:text-primary transition-colors"><span class="material-symbols-outlined text-lg">{{ showPassword ? 'visibility_off' : 'visibility' }}</span></button>
            </div>
            <p v-if="form.errors.password" class="text-error text-label-sm">{{ form.errors.password }}</p>
          </div>
          <div class="space-y-2">
            <label for="password_confirmation" class="block font-sans text-label-sm text-on-surface">Xác nhận mật khẩu</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60"><span class="material-symbols-outlined text-lg">lock_reset</span></span>
              <input id="password_confirmation" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'" required placeholder="Nhập lại mật khẩu" class="w-full pl-12 pr-12 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300" />
            </div>
          </div>
          <BaseButton type="submit" variant="primary" :disabled="form.processing" class="w-full justify-center">
            <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">refresh</span>
            {{ form.processing ? 'Đang xử lý...' : 'Đặt lại mật khẩu' }}
          </BaseButton>
        </form>
      </div>
    </div>
  </div>
</template>
