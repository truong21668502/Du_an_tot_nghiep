<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import { router } from '@inertiajs/vue3';

const heroImages = [
  { 
    src: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBEbAMcohLwkWjEzkiWvcyYSUUDW_zom2KjQUEoKtE4e8YvSaGz5LRdX5n_PH8AlNVlEMIvcdRq4JTkX9e9cN2bGF5DqFhh-QkTOT3QxFoLCl5GvMKGeboJp1WX2k_RGSulNrG3aMvB09aHb6PCX_MDLXe6cdoumAuHStJyQT5m5TBaDAngLRXryRuMkPoV-HCNJhTEmCc2IXhOIkVo8KS8qfpx9t4EBzyrYQWb7hldppVhMn__JrUF2uLhLr_b2mTFKp6OigV6gQY', 
    alt: 'Nghệ thuật Rót Cà Phê', 
    class: 'col-span-1 row-span-2 aspect-[3/4]' 
  },
  { 
    src: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBtxf_1YL0aiNr_fCzJN-2WkWkIfBebpNz6UUpf42ugnzCP5uRfbG30zwGzmNMsqHqA9kQG3wU3YTO6HwHzuWx2Wyf9WVPi4yHztohZRQ_FE2WSRTTzlUhUxZ4iR_L1Aar-72lZKV_LAR6N18cJVrpA_60vV01dOEjugRAGXfkmTrN9BMiFYRpjpjSgwxjzctu8C9sm6gI6cQX3pUbVTln2EbRLB9w7PZ9SoNozfzr-HglWOwQvtkvon1g3XCH5PCZdp2QxnDcnTIY', 
    alt: 'Hạt Cà Phê Rang Mộc', 
    class: 'col-span-1 row-span-1 aspect-square' 
  },
  { 
    src: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDSowuqrWHnjQD8XC09rxJjIqNc987ae-osyUpJhhBPwIesKa1yHreUs4oBZv9BYNRoqb-ffdis9rl3qaTCU-37uSGFj5MFvHCTDAkmR6zpcLDf-I-8RFP16aiJD-3rBJlolvfgUGlY9dGafCin6RpBpuNCLM8nAzIs9Q1jyi3w94Xny12FbxunBbGjwvuZ46dIUVLOfdwaCcpFsgVE42VV85IzhyDBcBTCByUbI4z8ZsdW7RTW9koJj4liRwZnJ0FIYKADvprszyc', 
    alt: 'Góc Không Gian Yên Bình', 
    class: 'col-span-1 row-span-1 aspect-square',
    isInteractive: true 
  }
]
</script>

<template>
  <section class="relative min-h-[85vh] lg:min-h-[90vh] flex items-center justify-center px-margin-mobile md:px-gutter py-20 overflow-hidden">
    <!-- Background Image với hiệu ứng Parallax nhẹ -->
    <div class="absolute inset-0 z-0 scale-105 transform transition-transform duration-[10000ms] hover:scale-100">
      <img 
        alt="Hero Background" 
        class="w-full h-full object-cover opacity-[0.18] dark:opacity-[0.12] filter grayscale-[30%]" 
        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDrC8V8sOloMb9lpEw2Wycshp3uMooBLcXcyfiwPTndZ3fkpPNLwmYo_DQuSKkoDfCJJUMdPio6d-9l8ltGnUsUlJQTBDMqHoYxIYT93ghIk-FihyCfDRIQ3nt0FgyUKPw78RkEdHi0n7XFv7KmQ8H7iuo5stcpEESZEkIgztfbVeo15l5lUqNVcRFPaM9j7hVybr0dKpYZ7RzNSkMtKv2b3NkDCiTVBNogFwLueTbsAi6ncuzxXLdYowEFHTyMRuA-6NAA5Yn3fdk"
      />
      <div class="absolute inset-0 bg-gradient-to-b from-background/30 via-background/80 to-background"></div>
    </div>
    
    <!-- Content Container -->
    <div class="relative z-10 max-w-[1280px] mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
      
      <!-- Cột Nội Dung Trái -->
      <div class="lg:col-span-7 flex flex-col gap-6 max-w-2xl text-left">
        <AnimateOnScroll animation="fade-right" :delay="100" :duration="800">
          <span class="inline-flex items-center gap-2 text-secondary font-sans text-label-md uppercase tracking-wider bg-secondary-container/30 px-3 py-1.5 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
            Nghệ thuật pha chế thủ công
          </span>
        </AnimateOnScroll>

        <AnimateOnScroll animation="fade-right" :delay="250" :duration="850">
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary font-bold leading-[1.1] tracking-tight">
            Nơi Đánh Thức <br class="hidden md:inline" />
            <span class="text-secondary italic font-serif font-medium">Mọi Giác Quan</span> Đích Thực
          </h1>
        </AnimateOnScroll>

        <AnimateOnScroll animation="fade-right" :delay="400" :duration="900">
          <p class="text-body-lg text-on-surface-variant leading-relaxed font-sans max-w-xl">
            Từng hạt Arabica Cầu Đất tinh túy được nâng niu qua đôi tay những người thợ rang tâm huyết tại Cà Phê Mới. Đem lại sự tĩnh lặng và vị ngon nguyên bản đầy cuốn hút.
          </p>
        </AnimateOnScroll>

        <AnimateOnScroll animation="fade-right" :delay="550" :duration="950">
          <div class="flex flex-wrap gap-4 pt-4">
            <BaseButton @click="router.get(route('customer.menu.index'))" variant="primary" class="shadow-lg shadow-primary/10 hover:shadow-xl hover:shadow-primary/20 transition-all duration-300">
              Khám Phá Thực Đơn
            </BaseButton>
            <BaseButton @click="router.get(route('about'))" variant="secondary" class="border border-outline/30 hover:bg-surface-container transition-colors duration-300">
              Về chúng tôi
            </BaseButton>
          </div>
        </AnimateOnScroll>
      </div>

      <!-- Cột Lưới Ảnh Phải -->
      <div class="lg:col-span-5 w-full">
        <AnimateOnScroll animation="scale-in" :delay="450" :duration="1000">
          <div class="grid grid-cols-2 gap-4 items-center">
            <div 
              v-for="(img, idx) in heroImages" 
              :key="idx" 
              :class="[
                'rounded-xl overflow-hidden shadow-soft border border-outline-variant/20 relative group transition-all duration-500 hover:shadow-lg',
                img.class
              ]"
            >
              <div class="absolute inset-0 bg-primary/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10 pointer-events-none"></div>
              <img 
                :alt="img.alt" 
                :src="img.src" 
                class="w-full h-full object-cover transform scale-100 group-hover:scale-[1.06] transition-transform duration-700 ease-out"
                loading="lazy"
              />
            </div>
          </div>
        </AnimateOnScroll>
      </div>
      
    </div>
  </section>
</template>