<script setup>
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
const contactDetails = [
  {
    icon: 'location_on',
    title: 'Địa chỉ',
    content: '137 Đường Nguyễn Thị Thập, Thanh Khê, Đà Nẵng, Việt Nam',
    link: 'https://maps.google.com/?q=123+Nguyen+Hue+Quan+1+Ho+Chi+Minh'
  },
  {
    icon: 'call',
    title: 'Điện thoại',
    content: '(+84) 28 1234 5678',
    link: 'tel:+842812345678'
  },
  {
    icon: 'mail',
    title: 'Email',
    content: 'hello@caphemoi.vn',
    link: 'mailto:hello@caphemoi.vn'
  },
  {
    icon: 'schedule',
    title: 'Giờ mở cửa',
    content: 'Thứ 2 - Chủ nhật: 7:00 - 22:00',
    link: null
  }
]
const socialLinks = [
  { icon: 'camera_alt', label: 'Instagram', url: '#' },
  { icon: 'thumb_up', label: 'Facebook', url: '#' },
  { icon: 'alternate_email', label: 'Twitter', url: '#' },
  { icon: 'play_circle', label: 'YouTube', url: '#' }
]
</script>
<template>
  <section class="relative py-24 px-margin-mobile md:px-gutter bg-surface-container-low">
    <div class="max-w-[1280px] mx-auto">
      <!-- Header -->
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="text-center mb-16">
          <span class="inline-block px-4 py-2 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-sm mb-6">
            Liên hệ
          </span>
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-4">
            Hãy Kết Nối Với Chúng Tôi
          </h1>
          <p class="text-body-lg text-on-surface-variant max-w-2xl mx-auto">
            Chúng tôi luôn sẵn sàng lắng nghe từ bạn. Đừng ngần ngại liên hệ để được hỗ trợ hoặc chia sẻ những góp ý.
          </p>
        </div>
      </AnimateOnScroll>
      <!-- Contact Details Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
        <AnimateOnScroll 
          v-for="(detail, index) in contactDetails" 
          :key="detail.title"
          animation="fade-up"
          :duration="600"
          :delay="index * 100"
        >
          <component 
            :is="detail.link ? 'a' : 'div'"
            :href="detail.link"
            :target="detail.link?.startsWith('http') ? '_blank' : undefined"
            :rel="detail.link?.startsWith('http') ? 'noopener noreferrer' : undefined"
            :class="[
              'glass-panel p-8 rounded-xl text-center group hover:shadow-soft transition-all duration-500 block',
              detail.link ? 'cursor-pointer hover:-translate-y-1' : ''
            ]"
          >
            <div class="w-16 h-16 mx-auto mb-4 bg-primary-container/30 rounded-full flex items-center justify-center group-hover:bg-primary-container/50 group-hover:scale-110 transition-all duration-500">
              <span class="material-symbols-outlined text-3xl text-primary">{{ detail.icon }}</span>
            </div>
            <h3 class="font-sans text-label-md text-on-surface mb-2">{{ detail.title }}</h3>
            <p class="font-sans text-body-md text-on-surface-variant">{{ detail.content }}</p>
          </component>
        </AnimateOnScroll>
      </div>
      <!-- Social Links -->
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="400">
        <div class="flex justify-center gap-4">
          <a 
            v-for="social in socialLinks" 
            :key="social.label"
            :href="social.url"
            :aria-label="social.label"
            class="w-12 h-12 rounded-full bg-surface-container-high hover:bg-primary-container/40 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all duration-300 hover:scale-110"
          >
            <span class="material-symbols-outlined text-xl">{{ social.icon }}</span>
          </a>
        </div>
      </AnimateOnScroll>
    </div>
  </section>
</template>
