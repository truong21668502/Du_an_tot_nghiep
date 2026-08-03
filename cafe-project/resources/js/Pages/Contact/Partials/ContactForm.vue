<script setup>
import { ref } from 'vue'
import axios from 'axios'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'

const isSubmitted = ref(false)
const isProcessing = ref(false)
const errors = ref({})

const form = ref({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: ''
})

const subjects = [
  'Góp ý về dịch vụ',
  'Hỗ trợ đặt bàn',
  'Hợp tác kinh doanh',
  'Tuyển dụng',
  'Khác'
]

const submitForm = async () => {
  isProcessing.value = true
  errors.value = {}
  
  try {
    const response = await axios.post('/contact/send', form.value)
    
    if (response.data.success) {
      isSubmitted.value = true
      // Reset form
      form.value = {
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: ''
      }
    }
  } catch (error) {
    if (error.response && error.response.status === 422) {
      // Validation errors from Laravel
      errors.value = error.response.data.errors
      console.error('Lỗi validation:', errors.value)
    } else {
      console.error('Lỗi xử lý:', error)
      // Hiển thị thông báo lỗi chung nếu cần
    }
  } finally {
    isProcessing.value = false
  }
}
</script>

<template>
  <section class="py-24 px-margin-mobile md:px-gutter">
    <div class="max-w-[1280px] mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
        <AnimateOnScroll animation="fade-right" :duration="700">
          <div>
            <h2 class="text-headline-md text-primary mb-4">Gửi Tin Nhắn Cho Chúng Tôi</h2>
            <p class="text-body-md text-on-surface-variant mb-8">
              Bạn có thắc mắc hoặc cần hỗ trợ? Hãy điền vào form bên dưới, chúng tôi sẽ phản hồi trong thời gian sớm nhất.
            </p>
            
            <div 
              v-if="isSubmitted"
              class="mb-8 p-6 bg-tertiary-container/30 border border-tertiary-container rounded-xl text-center"
            >
              <span class="material-symbols-outlined text-4xl text-tertiary mb-2">check_circle</span>
              <h3 class="font-serif text-headline-sm text-on-tertiary-container mb-1">Cảm ơn bạn!</h3>
              <p class="font-sans text-body-md text-on-tertiary-container">Tin nhắn của bạn đã được gửi thành công. Chúng tôi sẽ phản hồi sớm nhất có thể.</p>
            </div>

            <form v-else @submit.prevent="submitForm" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label for="name" class="font-sans text-label-sm text-on-surface">Họ và tên *</label>
                  <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="Nguyễn Văn A"
                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300"
                    :class="{ 'border-error focus:border-error focus:ring-error/20': errors.name }"
                  />
                  <div v-if="errors.name" class="text-error text-sm">{{ errors.name[0] }}</div>
                </div>

                <div class="space-y-2">
                  <label for="email" class="font-sans text-label-sm text-on-surface">Email *</label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    placeholder="email@example.com"
                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300"
                    :class="{ 'border-error focus:border-error focus:ring-error/20': errors.email }"
                  />
                  <div v-if="errors.email" class="text-error text-sm">{{ errors.email[0] }}</div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label for="phone" class="font-sans text-label-sm text-on-surface">Số điện thoại</label>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    placeholder="0123 456 789"
                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300"
                  />
                </div>

                <div class="space-y-2">
                  <label for="subject" class="font-sans text-label-sm text-on-surface">Chủ đề *</label>
                  <select
                    id="subject"
                    v-model="form.subject"
                    required
                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300 cursor-pointer"
                    :class="{ 'border-error focus:border-error focus:ring-error/20': errors.subject }"
                  >
                    <option value="" disabled>Chọn chủ đề</option>
                    <option v-for="subject in subjects" :key="subject" :value="subject">{{ subject }}</option>
                  </select>
                  <div v-if="errors.subject" class="text-error text-sm">{{ errors.subject[0] }}</div>
                </div>
              </div>

              <div class="space-y-2">
                <label for="message" class="font-sans text-label-sm text-on-surface">Tin nhắn *</label>
                <textarea
                  id="message"
                  v-model="form.message"
                  required
                  rows="6"
                  placeholder="Nhập nội dung tin nhắn của bạn..."
                  class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300 resize-none"
                  :class="{ 'border-error focus:border-error focus:ring-error/20': errors.message }"
                ></textarea>
                <div v-if="errors.message" class="text-error text-sm">{{ errors.message[0] }}</div>
              </div>

              <div class="flex justify-start pt-4">
                <BaseButton 
                  type="submit" 
                  variant="primary"
                  :disabled="isProcessing"
                >
                  <span v-if="isProcessing" class="material-symbols-outlined animate-spin text-lg">refresh</span>
                  {{ isProcessing ? 'Đang gửi...' : 'Gửi Tin Nhắn' }}
                </BaseButton>
              </div>
            </form>
          </div>
        </AnimateOnScroll>

        <AnimateOnScroll animation="fade-left" :duration="700" :delay="200">
          <div class="hidden lg:block">
            <div class="rounded-xl overflow-hidden shadow-soft relative">
              <img 
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBc5gGat6KGg5Frk9AyGVuzWHh2fhv6s0dv1okXFbq-CByXlSt76QtYK47ULBFwhM1yPj0i0k017UTcPuwrZVk1ygrUJqzcTjfKBo2kS5rtT2_DaiU_M2kMOTPLcedIZGGAew5Sd8klWtj37OKU89hF_Y38pNfSxxY9vTT2E6b6Rt_CCaWSSUhdNav2pgAyoY3-sxo5VkHVFwdD_5B7r92N68Kq2mhj6u6eW3NVUla0LFZPgSCNMW862p8DqJrdbNSPYkRwxJLJjhQ"
                alt="Cafe Interior"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-primary/20 to-transparent"></div>
            </div>
          </div>
        </AnimateOnScroll>
      </div>
    </div>
  </section>
</template>