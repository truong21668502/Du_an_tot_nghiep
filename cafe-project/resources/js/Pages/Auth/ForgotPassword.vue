<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: GuestLayout })
const form = useForm({ email: '' })
const submitted = ref(false)
</script>
<template>
  <div class="min-h-screen flex items-center justify-center px-margin-mobile py-12 bg-surface-container-low">
    <div class="w-full max-w-md">
      <div class="text-center mb-10">
        <Link href="/" class="inline-block font-serif text-headline-md text-primary mb-3 hover:opacity-80 transition-opacity">Nắng Coffee</Link>
        <h1 class="font-serif text-headline-sm text-on-surface">Quên mật khẩu</h1>
        <p class="font-sans text-body-md text-on-surface-variant mt-2">Nhập email để nhận liên kết đặt lại mật khẩu.</p>
      </div>
      <div class="bg-surface rounded-2xl shadow-soft border border-outline-variant/20 p-8">
        <div v-if="submitted" class="text-center space-y-4">
          <div class="w-16 h-16 mx-auto bg-tertiary-container/30 rounded-full flex items-center justify-center"><span class="material-symbols-outlined text-3xl text-tertiary">mark_email_read</span></div>
          <p class="font-sans text-body-md text-on-surface">Đã gửi liên kết đặt lại mật khẩu!</p>
          <p class="font-sans text-body-sm text-on-surface-variant">Vui lòng kiểm tra hộp thư đến của bạn.</p>
          <Link href="/dang-nhap" class="inline-block text-secondary font-sans text-label-md hover:underline mt-4">Quay lại đăng nhập</Link>
        </div>
        <form v-else @submit.prevent="form.post('/forgot-password', { onSuccess: () => submitted = true })" class="space-y-5">
          <div class="space-y-2">
            <label for="email" class="block font-sans text-label-sm text-on-surface">Email</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60"><span class="material-symbols-outlined text-lg">mail</span></span>
              <input id="email" v-model="form.email" type="email" required placeholder="email@example.com" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300" />
            </div>
            <p v-if="form.errors.email" class="text-error text-label-sm">{{ form.errors.email }}</p>
          </div>
          <BaseButton type="submit" variant="primary" :disabled="form.processing" class="w-full justify-center">
            <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">refresh</span>
            {{ form.processing ? 'Đang gửi...' : 'Gửi liên kết đặt lại' }}
          </BaseButton>
        </form>
        <p class="text-center mt-6 font-sans text-body-md text-on-surface-variant">
          <Link href="/dang-nhap" class="text-secondary font-semibold hover:text-secondary/80 transition-colors">Quay lại đăng nhập</Link>
        </p>
      </div>
    </div>
  </div>
</template>
