<script setup>
import { reactive, ref } from 'vue'
import ProfileLayout from '@/Layouts/ProfileLayout.vue'
import { useProfile } from '@/Composables/useProfile'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: ProfileLayout })
const props = defineProps({ user: Object })
const { loading, errors, successMessage, updateProfile, updateAvatar } = useProfile()
const form = reactive({
  full_name: props.user?.full_name || '',
  phone_number: props.user?.phone_number || '',
  gender: props.user?.gender || 'Khác',
  date_of_birth: props.user?.date_of_birth || '',
})
const avatarFile = ref(null)
const avatarPreview = ref(props.user?.avatar || null)
const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
  }
}
const handleUploadAvatar = () => {
  if (avatarFile.value) {
    updateAvatar(avatarFile.value)
    avatarFile.value = null
  }
}
const handleSubmit = () => {
  updateProfile(form)
}
</script>
<template>
  <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-8">
    <div>
      <h2 class="font-serif text-headline-sm text-primary mb-1">Thông tin cá nhân</h2>
      <p class="font-sans text-body-md text-on-surface-variant">Cập nhật thông tin hiển thị của bạn</p>
    </div>
    <div v-if="successMessage" class="p-4 bg-green-50 border border-green-200 rounded-xl font-sans text-body-md text-green-800">
      {{ successMessage }}
    </div>
    <div class="flex items-center gap-4">
      <div class="w-20 h-20 rounded-full bg-primary-container/30 overflow-hidden border-2 border-primary/20 flex-shrink-0 flex items-center justify-center">
        <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar" class="w-full h-full object-cover" />
        <span v-else class="material-symbols-outlined text-4xl text-primary">person</span>
      </div>
      <div>
        <!-- <label class="block mb-2">
          <span class="px-4 py-2 bg-surface-container-low hover:bg-surface-container border border-outline-variant/30 rounded-full font-sans text-label-sm cursor-pointer transition-colors">Chọn ảnh</span>
          <input type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
        </label> -->
        <button
          v-if="avatarFile"
          @click="handleUploadAvatar"
          :disabled="loading"
          class="px-4 py-2 bg-primary text-on-primary rounded-full font-sans text-label-sm hover:bg-primary/90 transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Đang tải...' : 'Tải lên' }}
        </button>
      </div>
    </div>
    <form @submit.prevent="handleSubmit" class="space-y-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="space-y-2">
          <label class="block font-sans text-label-sm text-on-surface">Họ và tên</label>
          <input v-model="form.full_name" type="text" required class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all" />
          <p v-if="errors.full_name" class="text-error text-label-sm">{{ errors.full_name }}</p>
        </div>
        <div class="space-y-2">
          <label class="block font-sans text-label-sm text-on-surface">Email</label>
          <input :value="props.user?.email" type="email" disabled class="w-full px-4 py-3 bg-surface-container-high border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface-variant opacity-60 cursor-not-allowed" />
        </div>
        <div class="space-y-2">
          <label class="block font-sans text-label-sm text-on-surface">Số điện thoại</label>
          <input v-model="form.phone_number" type="tel" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all" />
          <p v-if="errors.phone_number" class="text-error text-label-sm">{{ errors.phone_number }}</p>
        </div>
        <div class="space-y-2">
          <label class="block font-sans text-label-sm text-on-surface">Giới tính</label>
          <select v-model="form.gender" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all cursor-pointer">
            <option value="Nam">Nam</option>
            <option value="Nữ">Nữ</option>
            <option value="Khác">Khác</option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block font-sans text-label-sm text-on-surface">Ngày sinh</label>
          <input v-model="form.date_of_birth" type="date" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all" />
        </div>
      </div>
      <div class="flex justify-end pt-2">
        <BaseButton type="submit" variant="primary" :disabled="loading">
          {{ loading ? 'Đang lưu...' : 'Lưu thay đổi' }}
        </BaseButton>
      </div>
    </form>
  </div>
</template>