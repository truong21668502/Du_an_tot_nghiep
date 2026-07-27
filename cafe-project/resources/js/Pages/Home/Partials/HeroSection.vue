<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/BaseButton.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import Banner from '@/Components/Banner.vue'

const props = defineProps({
    banners: { type: Array, default: () => [] },
})

// ── Slider (chỉ cần khi có banner) ──────────────────────────────────────────
const activeIndex = ref(0)
const isPaused    = ref(false)
let timer = null

const goTo = (i) => { activeIndex.value = (i + props.banners.length) % props.banners.length }
const next = () => goTo(activeIndex.value + 1)
const prev = () => goTo(activeIndex.value - 1)

onMounted(() => {
    timer = setInterval(() => {
        if (!isPaused.value && props.banners.length > 1) next()
    }, 6000)
})
onUnmounted(() => clearInterval(timer))

// ── Nội dung mặc định của thương hiệu — dùng khi admin chưa tạo banner nào ──
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
    <section class="relative overflow-hidden">

        <!-- ── Có banner: slider ảnh do admin quản lý, dùng chung component Banner ── -->
        <div
    v-if="banners.length"
    class="relative w-full
           h-[40vh]
           sm:h-[50vh]
           md:h-[60vh]
           lg:h-[80vh]
           xl:h-[90vh]"
            role="region"
            aria-roledescription="carousel"
            @mouseenter="isPaused = true"
            @mouseleave="isPaused = false"
        >
            <div
                v-for="(banner, i) in banners" :key="banner.id"
                class="absolute inset-0 transition-opacity duration-700"
                :class="i === activeIndex ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
                :aria-hidden="i !== activeIndex"
            >
                <Banner :banner="banner" aspect-class="h-full" :rounded="false" class="h-full" />
            </div>

            <template v-if="banners.length > 1">
                <button @click="prev" aria-label="Banner trước"
                    class="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-surface/80 backdrop-blur flex items-center justify-center text-on-surface hover:bg-surface transition-colors">
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <button @click="next" aria-label="Banner tiếp theo"
                    class="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-surface/80 backdrop-blur flex items-center justify-center text-on-surface hover:bg-surface transition-colors">
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>

                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
                    <button
                        v-for="(banner, i) in banners" :key="banner.id"
                        @click="goTo(i)"
                        :aria-label="`Xem banner ${i + 1}`"
                        :class="[
                            'h-2 rounded-full transition-all',
                            i === activeIndex ? 'w-6 bg-surface' : 'w-2 bg-surface/50 hover:bg-surface/80',
                        ]"
                    />
                </div>
            </template>
        </div>

        <!-- ── Chưa có banner nào active: giữ nội dung mặc định của thương hiệu ── -->
        <div v-else class="relative min-h-[85vh] lg:min-h-[90vh] flex items-center justify-center px-margin-mobile md:px-gutter py-20">
            <div class="absolute inset-0 z-0 scale-105 transform transition-transform duration-[10000ms] hover:scale-100">
                <img
                    alt="Hero Background"
                    class="w-full h-full object-cover opacity-[0.18] dark:opacity-[0.12] filter grayscale-[30%]"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuDrC8V8sOloMb9lpEw2Wycshp3uMooBLcXcyfiwPTndZ3fkpPNLwmYo_DQuSKkoDfCJJUMdPio6d-9l8ltGnUsUlJQTBDMqHoYxIYT93ghIk-FihyCfDRIQ3nt0FgyUKPw78RkEdHi0n7XFv7KmQ8H7iuo5stcpEESZEkIgztfbVeo15l5lUqNVcRFPaM9j7hVybr0dKpYZ7RzNSkMtKv2b3NkDCiTVBNogFwLueTbsAi6ncuzxXLdYowEFHTyMRuA-6NAA5Yn3fdk"
                />
                <div class="absolute inset-0 bg-gradient-to-b from-background/30 via-background/80 to-background"></div>
            </div>

            <div class="relative z-10 max-w-[1280px] mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
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
        </div>
    </section>
</template>