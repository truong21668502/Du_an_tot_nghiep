<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: GuestLayout })
const props = defineProps({ status: { type: String } })
const form = useForm({})
const submit = () => {
  form.post(route('verification.send'))
}
const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>
<template>
  <Head title="Xác thực email" />
  <div class="min-h-screen flex items-center justify-center px-margin-mobile py-12 bg-surface-container-low">
    <div class="w-full max-w-md">
      <div class="text-center mb-10">
        <Link href="/" class="inline-block font-serif text-headline-md text-primary mb-3 hover:opacity-80 transition-opacity">Nắng Coffee</Link>
        <h1 class="font-serif text-headline-sm text-on-surface">Xác thực email</h1>
        <p class="font-sans text-body-md text-on-surface-variant mt-2">Xác nhận địa chỉ email của bạn để tiếp tục.</p>
      </div>
      <div class="bg-surface rounded-2xl shadow-soft border border-outline-variant/20 p-8">
        <div class="text-center space-y-4">
          <div class="w-16 h-16 mx-auto bg-primary-container/30 rounded-full flex items-center justify-center">
            <span class="material-symbols-outlined text-3xl text-primary">mail</span>
          </div>
          <p class="font-sans text-body-md text-on-surface-variant leading-relaxed">
            Cảm ơn bạn đã đăng ký! Trước khi bắt đầu, vui lòng xác thực email bằng cách nhấn vào liên kết chúng tôi vừa gửi. Nếu bạn không nhận được email, chúng tôi sẽ gửi lại cho bạn.
          </p>
          <div v-if="verificationLinkSent" class="p-4 bg-tertiary-container/20 border border-tertiary-container rounded-xl">
            <p class="font-sans text-body-md text-on-tertiary-container">Đã gửi liên kết xác thực mới đến email của bạn.</p>
          </div>
        </div>
        <form @submit.prevent="submit" class="mt-6 space-y-4">
          <BaseButton type="submit" variant="primary" :disabled="form.processing" class="w-full justify-center">
            <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">refresh</span>
            {{ form.processing ? 'Đang gửi...' : 'Gửi lại email xác thực' }}
          </BaseButton>
          <Link :href="route('logout')" method="post" as="button" class="block w-full text-center font-sans text-label-sm text-on-surface-variant hover:text-error transition-colors py-2">
            Đăng xuất
          </Link>
        </form>
      </div>
    </div>
  </div>
</template>
