<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
const isSubmitted = ref(false)
const form = useForm({
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
const submitForm = () => {
axios.post('/lien-he/gui', form)
    .then(res => {
        if (res.data.success) {
            isSubmitted.value = true
        }
    })
}
</script>
<template>
  <section class="py-24 px-margin-mobile md:px-gutter">
    <div class="max-w-[1280px] mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
        <!-- Left: Form -->
        <AnimateOnScroll animation="fade-right" :duration="700">
          <div>
            <h2 class="text-headline-md text-primary mb-4">Gửi Tin Nhắn Cho Chúng Tôi</h2>
            <p class="text-body-md text-on-surface-variant mb-8">
              Bạn có thắc mắc hoặc cần hỗ trợ? Hãy điền vào form bên dưới, chúng tôi sẽ phản hồi trong thời gian sớm nhất.
            </p>
            <!-- Success Message -->
            <div 
              v-if="isSubmitted"
              class="mb-8 p-6 bg-tertiary-container/30 border border-tertiary-container rounded-xl text-center"
            >
              <span class="material-symbols-outlined text-4xl text-tertiary mb-2">check_circle</span>
              <h3 class="font-serif text-headline-sm text-on-tertiary-container mb-1">Cảm ơn bạn!</h3>
              <p class="font-sans text-body-md text-on-tertiary-container">Tin nhắn của bạn đã được gửi thành công. Chúng tôi sẽ phản hồi sớm nhất có thể.</p>
            </div>
            <form v-else @submit.prevent="submitForm" class="space-y-6">
              <!-- Name & Email -->
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
                  />
                  <div v-if="form.errors.name" class="text-error text-sm">{{ form.errors.name }}</div>
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
                  />
                  <div v-if="form.errors.email" class="text-error text-sm">{{ form.errors.email }}</div>
                </div>
              </div>
              <!-- Phone & Subject -->
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
                  >
                    <option value="" disabled>Chọn chủ đề</option>
                    <option v-for="subject in subjects" :key="subject" :value="subject">{{ subject }}</option>
                  </select>
                  <div v-if="form.errors.subject" class="text-error text-sm">{{ form.errors.subject }}</div>
                </div>
              </div>
              <!-- Message -->
              <div class="space-y-2">
                <label for="message" class="font-sans text-label-sm text-on-surface">Tin nhắn *</label>
                <textarea
                  id="message"
                  v-model="form.message"
                  required
                  rows="6"
                  placeholder="Nhập nội dung tin nhắn của bạn..."
                  class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300 resize-none"
                ></textarea>
                <div v-if="form.errors.message" class="text-error text-sm">{{ form.errors.message }}</div>
              </div>
              <!-- Submit Button -->
              <div class="flex justify-start pt-4">
                <BaseButton 
                  type="submit" 
                  variant="primary"
                  :disabled="form.processing"
                >
                  <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">refresh</span>
                  {{ form.processing ? 'Đang gửi...' : 'Gửi Tin Nhắn' }}
                </BaseButton>
              </div>
            </form>
          </div>
        </AnimateOnScroll>
        <!-- Right: Decorative Image -->
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
