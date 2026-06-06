<script setup>
import AnimateOnScroll from "@/Components/Base/AnimateOnScroll.vue";
</script>

<template>
    <section
        class="relative min-h-[600px] flex items-center justify-center px-margin-mobile md:px-gutter py-24 overflow-hidden"
    >
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img
                alt="About Hero"
                class="w-full h-full object-cover opacity-20"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuDrC8V8sOloMb9lpEw2Wycshp3uMooBLcXcyfiwPTndZ3fkpPNLwmYo_DQuSKkoDfCJJUMdPio6d-9l8ltGnUsUlJQTBDMqHoYxIYT93ghIk-FihyCfDRIQ3nt0FgyUKPw78RkEdHi0n7XFv7KmQ8H7iuo5stcpEESZEkIgztfbVeo15l5lUqNVcRFPaM9j7hVybr0dKpYZ7RzNSkMtKv2b3NkDCiTVBNogFwLueTbsAi6ncuzxXLdYowEFHTyMRuA-6NAA5Yn3fdk"
            />
            <div
                class="absolute inset-0 bg-gradient-to-b from-background/80 to-background"
            ></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 max-w-[1280px] mx-auto text-center">
            <AnimateOnScroll animation="fade-down" :duration="800">
                <span
                    class="inline-block px-4 py-2 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-sm mb-6"
                >
                    Cà Phê Mới
                </span>
            </AnimateOnScroll>

            <AnimateOnScroll animation="fade-up" :delay="200" :duration="800">
                <h1
                    class="text-display-lg-mobile md:text-display-lg text-primary mb-6"
                >
                    Câu Chuyện Của Chúng Tôi
                </h1>
            </AnimateOnScroll>

            <AnimateOnScroll animation="fade-up" :delay="400" :duration="800">
                <p
                    class="text-body-lg text-on-surface-variant max-w-3xl mx-auto leading-relaxed"
                >
                    Từ một niềm đam mê với hạt cà phê nguyên bản, chúng tôi đã
                    xây dựng nên một không gian nơi mỗi tách cà phê đều kể một
                    câu chuyện riêng.
                </p>
            </AnimateOnScroll>
        </div>
    </section>
</template>
