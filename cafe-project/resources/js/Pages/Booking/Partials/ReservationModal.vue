<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import BaseButton from '@/Components/Base/BaseButton.vue'
const props = defineProps({
  table: {
    type: Object,
    required: true
  },
  user: {
    type: Object,
    default: null
  }
})
const emit = defineEmits(['close', 'submit'])
const loading = ref(false)
const errors = ref({})
const isSubmitted = ref(false)
const form = ref({
  phone_number: props.user?.phone || '',
  guest_count: Math.min(2, props.table.max_people),
  reservation_date: new Date().toISOString().split('T')[0],
  reservation_time: '',
  note: ''
})
const timeSlots = [
  '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00',
  '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30',
  '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00',
  '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00'
]
const minDate = new Date().toISOString().split('T')[0]
const maxDate = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
const validateForm = () => {
  errors.value = {}
  if (!form.value.phone_number || form.value.phone_number.length < 10) {
    errors.value.phone_number = 'Vui lòng nhập số điện thoại hợp lệ (ít nhất 10 số)'
  }
  if (!form.value.reservation_date) {
    errors.value.reservation_date = 'Vui lòng chọn ngày đặt bàn'
  }
  if (!form.value.reservation_time) {
    errors.value.reservation_time = 'Vui lòng chọn giờ đặt bàn'
  }
  if (form.value.guest_count < 1 || form.value.guest_count > props.table.max_people) {
    errors.value.guest_count = `Số khách từ 1 đến ${props.table.max_people} người`
  }
  return Object.keys(errors.value).length === 0
}
const handleSubmit = async () => {
  if (!validateForm()) return
  loading.value = true
  try {
    const response = await axios.post('/api/reservations', {
      table_id: props.table.id,
      user_id: props.user?.id,
      phone_number: form.value.phone_number,
      guest_count: form.value.guest_count,
      reservation_time: `${form.value.reservation_date} ${form.value.reservation_time}:00`,
      note: form.value.note
    })
    if (response.data.success) {
      isSubmitted.value = true
      setTimeout(() => {
        emit('submit', form.value)
        emit('close')
      }, 1500)
    }
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      errors.value.server = 'Có lỗi xảy ra, vui lòng thử lại sau'
    }
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-surface rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 md:p-8">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h2 class="font-serif text-headline-sm text-primary">Đặt Bàn</h2>
              <p class="font-sans text-body-md text-on-surface-variant mt-1">
                {{ table.table_name }} - Tối đa {{ table.max_people }} người
              </p>
            </div>
            <button @click="$emit('close')" class="p-2 hover:bg-surface-container-low rounded-full transition-colors">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
          <div v-if="isSubmitted" class="text-center py-8">
            <span class="material-symbols-outlined text-6xl text-tertiary mb-4">check_circle</span>
            <h3 class="font-serif text-headline-sm text-on-surface mb-2">Đặt bàn thành công!</h3>
            <p class="font-sans text-body-md text-on-surface-variant">
              Nhân viên sẽ gọi điện xác nhận trong thời gian sớm nhất.
            </p>
          </div>
          <form v-else @submit.prevent="handleSubmit" class="space-y-5">
            <div>
              <label class="block font-sans text-label-sm text-on-surface mb-2">Số điện thoại *</label>
              <input
                v-model="form.phone_number"
                type="tel"
                placeholder="0912 345 678"
                class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20"
              />
              <p v-if="errors.phone_number" class="text-error text-sm mt-1">{{ errors.phone_number }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-sans text-label-sm text-on-surface mb-2">Ngày đặt *</label>
                <input
                  v-model="form.reservation_date"
                  type="date"
                  :min="minDate"
                  :max="maxDate"
                  class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20"
                />
                <p v-if="errors.reservation_date" class="text-error text-sm mt-1">{{ errors.reservation_date }}</p>
              </div>
              <div>
                <label class="block font-sans text-label-sm text-on-surface mb-2">Giờ đặt *</label>
                <select
                  v-model="form.reservation_time"
                  class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20"
                >
                  <option value="" disabled>Chọn giờ</option>
                  <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
                </select>
                <p v-if="errors.reservation_time" class="text-error text-sm mt-1">{{ errors.reservation_time }}</p>
              </div>
            </div>
            <div>
              <label class="block font-sans text-label-sm text-on-surface mb-2">
                Số khách (tối đa {{ table.max_people }} người)
              </label>
              <input
                v-model.number="form.guest_count"
                type="number"
                :min="1"
                :max="table.max_people"
                class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20"
              />
              <p v-if="errors.guest_count" class="text-error text-sm mt-1">{{ errors.guest_count }}</p>
            </div>
            <div>
              <label class="block font-sans text-label-sm text-on-surface mb-2">Ghi chú</label>
              <textarea
                v-model="form.note"
                rows="3"
                placeholder="Yêu cầu đặc biệt..."
                class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 resize-none"
              ></textarea>
            </div>
            <p v-if="errors.server" class="text-error text-sm text-center">{{ errors.server }}</p>
            <div class="flex gap-3 pt-2">
              <BaseButton type="button" variant="outline" @click="$emit('close')" class="flex-1">
                Hủy
              </BaseButton>
              <BaseButton type="submit" variant="primary" :disabled="loading" class="flex-1">
                <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
                {{ loading ? 'Đang xử lý...' : 'Xác nhận đặt bàn' }}
              </BaseButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>
