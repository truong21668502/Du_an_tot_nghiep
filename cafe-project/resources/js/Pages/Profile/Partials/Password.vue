<script setup>
import { reactive, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ProfileLayout from '@/Layouts/ProfileLayout.vue'
import { useProfile } from '@/Composables/useProfile'
import BaseButton from '@/Components/Base/BaseButton.vue'

defineOptions({ layout: ProfileLayout })

const page = usePage()
const { loading, errors, successMessage, updatePassword } = useProfile()

const isGoogleUser = computed(() => !!page.props.auth.user?.google_id)

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const handleSubmit = () => {
  updatePassword(form)
}
</script>

<template>
  <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6">
    <div>
      <h2 class="font-serif text-headline-sm text-primary mb-1">Đổi mật khẩu</h2>
      <p class="font-sans text-body-md text-on-surface-variant">
        {{ isGoogleUser ? 'Tài khoản liên kết Google không cần mật khẩu.' : 'Đảm bảo mật khẩu có ít nhất 8 ký tự' }}
      </p>
    </div>

    <div v-if="successMessage" class="p-4 bg-green-50 border border-green-200 rounded-xl font-sans text-body-md text-green-800">
      {{ successMessage }}
    </div>

    <div v-if="isGoogleUser" class="p-5 border border-amber-200 bg-amber-50 rounded-xl font-sans text-body-md text-amber-800">
      Tài khoản của bạn đã được liên kết với Google. Bạn không thể thực hiện chức năng đổi mật khẩu trực tiếp tại đây.
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-4 max-w-md">
      <div class="space-y-2">
        <label class="block font-sans text-label-sm text-on-surface">Mật khẩu hiện tại</label>
        <input v-model="form.current_password" type="password" required class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all" />
        <p v-if="errors.current_password" class="text-error text-label-sm">{{ errors.current_password }}</p>
      </div>

      <div class="space-y-2">
        <label class="block font-sans text-label-sm text-on-surface">Mật khẩu mới</label>
        <input v-model="form.password" type="password" required class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all" />
        <p v-if="errors.password" class="text-error text-label-sm">{{ errors.password }}</p>
      </div>

      <div class="space-y-2">
        <label class="block font-sans text-label-sm text-on-surface">Xác nhận mật khẩu mới</label>
        <input v-model="form.password_confirmation" type="password" required class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all" />
      </div>

      <div class="pt-2">
        <BaseButton type="submit" variant="primary" :disabled="loading">
          {{ loading ? 'Đang lưu...' : 'Đổi mật khẩu' }}
        </BaseButton>
      </div>
    </form>
  </div>
</template>