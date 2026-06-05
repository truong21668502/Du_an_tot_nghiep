<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'

const heroImages = [
  { 
    src: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBEbAMcohLwkWjEzkiWvcyYSUUDW_zom2KjQUEoKtE4e8YvSaGz5LRdX5n_PH8AlNVlEMIvcdRq4JTkX9e9cN2bGF5DqFhh-QkTOT3QxFoLCl5GvMKGeboJp1WX2k_RGSulNrG3aMvB09aHb6PCX_MDLXe6cdoumAuHStJyQT5m5TBaDAngLRXryRuMkPoV-HCNJhTEmCc2IXhOIkVo8KS8qfpx9t4EBzyrYQWb7hldppVhMn__JrUF2uLhLr_b2mTFKp6OigV6gQY', 
    alt: 'Coffee Pour', 
    class: 'col-span-1 row-span-2' 
  },
  { 
    src: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBtxf_1YL0aiNr_fCzJN-2WkWkIfBebpNz6UUpf42ugnzCP5uRfbG30zwGzmNMsqHqA9kQG3wU3YTO6HwHzuWx2Wyf9WVPi4yHztohZRQ_FE2WSRTTzlUhUxZ4iR_L1Aar-72lZKV_LAR6N18cJVrpA_60vV01dOEjugRAGXfkmTrN9BMiFYRpjpjSgwxjzctu8C9sm6gI6cQX3pUbVTln2EbRLB9w7PZ9SoNozfzr-HglWOwQvtkvon1g3XCH5PCZdp2QxnDcnTIY', 
    alt: 'Coffee Beans', 
    class: 'col-span-1 row-span-1' 
  },
  { 
    src: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDSowuqrWHnjQD8XC09rxJjIqNc987ae-osyUpJhhBPwIesKa1yHreUs4oBZv9BYNRoqb-ffdis9rl3qaTCU-37uSGFj5MFvHCTDAkmR6zpcLDf-I-8RFP16aiJD-3rBJlolvfgUGlY9dGafCin6RpBpuNCLM8nAzIs9Q1jyi3w94Xny12FbxunBbGjwvuZ46dIUVLOfdwaCcpFsgVE42VV85IzhyDBcBTCByUbI4z8ZsdW7RTW9koJj4liRwZnJ0FIYKADvprszyc', 
    alt: 'Cozy Corner', 
    class: 'col-span-1 row-span-1',
    isInteractive: true 
  }
]
</script>

<template>
  <section class="relative min-h-[819px] flex items-center justify-center px-margin-mobile md:px-gutter py-24 overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0 z-0">
      <img 
        alt="Hero Background" 
        class="w-full h-full object-cover opacity-30" 
        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDrC8V8sOloMb9lpEw2Wycshp3uMooBLcXcyfiwPTndZ3fkpPNLwmYo_DQuSKkoDfCJJUMdPio6d-9l8ltGnUsUlJQTBDMqHoYxIYT93ghIk-FihyCfDRIQ3nt0FgyUKPw78RkEdHi0n7XFv7KmQ8H7iuo5stcpEESZEkIgztfbVeo15l5lUqNVcRFPaM9j7hVybr0dKpYZ7RzNSkMtKv2b3NkDCiTVBNogFwLueTbsAi6ncuzxXLdYowEFHTyMRuA-6NAA5Yn3fdk"
      />
      <div class="absolute inset-0 bg-gradient-to-b from-background/50 to-background"></div>
    </div>
    
    <!-- Content -->
    <div class="relative z-10 max-w-[1280px] mx-auto w-full grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
      <!-- Left Content -->
      <div class="flex flex-col gap-6 max-w-xl">
        <span class="text-secondary font-sans text-label-md uppercase tracking-wider">
          Trải nghiệm nghệ thuật
        </span>
        <h1 class="text-display-lg-mobile md:text-display-lg text-primary">
          Thức Tỉnh Mọi Giác Quan
        </h1>
        <p class="text-body-lg text-on-surface-variant leading-relaxed">
          Hương vị cà phê thủ công tinh tế, được rang xay mỗi ngày. Khám phá không gian yên bình và tận hưởng những phút giây thư giãn.
        </p>
        <div class="flex flex-wrap gap-4 pt-4">
          <BaseButton variant="primary">Khám Phá Thực Đơn</BaseButton>
          <BaseButton variant="secondary">Đặt Bàn Ngay</BaseButton>
        </div>
      </div>

      <!-- Right Images Grid -->
      <div class="grid grid-cols-2 gap-4 h-125">
        <div 
          v-for="(img, idx) in heroImages" 
          :key="idx" 
          :class="[
            'rounded-xl overflow-hidden shadow-sm',
            img.class
          ]"
        >
          <img 
            :alt="img.alt" 
            :src="img.src" 
            class="w-full h-full object-cover hover:scale-105 transition-transform duration-700"
          />
        </div>
      </div>
    </div>
  </section>
</template>