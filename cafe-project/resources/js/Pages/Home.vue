<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import HeroSection from './Home/Partials/HeroSection.vue'
import QrOrderingSection from './Home/Partials/QrOrderingSection.vue'
import SignatureDrinksSection from './Home/Partials/SignatureDrinksSection.vue'
import BrandStorySection from './Home/Partials/BrandStorySection.vue'
import TestimonialsSection from './Home/Partials/TestimonialsSection.vue'
import BlogJournalSection from './Home/Partials/BlogJournalSection.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import { useGoogleOneTap } from "@/Composables/useGoogleOneTap"
import { usePage } from '@inertiajs/vue3'
const { initialize } = useGoogleOneTap()
const page = usePage()
const props = page.props
// console.log(props)
defineOptions({ layout: MainLayout })
initialize()
</script>
<template>
  <div class="space-y-0 overflow-hidden bg-background">
    <HeroSection />
    <AnimateOnScroll animation="fade-up" :delay="100" :duration="800">
      <QrOrderingSection />
    </AnimateOnScroll>
    <AnimateOnScroll animation="fade-up" :delay="150" :duration="850">
      <SignatureDrinksSection :drinks="props.drinks || []" />
    </AnimateOnScroll>
    <AnimateOnScroll animation="slide-up" :delay="100" :duration="900">
      <BrandStorySection />
    </AnimateOnScroll>
    <AnimateOnScroll animation="fade-up" :delay="100" :duration="800">
      <TestimonialsSection />
    </AnimateOnScroll>
    <AnimateOnScroll animation="fade-up" :delay="100" :duration="800">
      <BlogJournalSection :articles="props.articles || []" />
    </AnimateOnScroll>
  </div>
</template>
