<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import HeroSection from './Home/Partials/HeroSection.vue'
import QrOrderingSection from './Home/Partials/QrOrderingSection.vue'
import SignatureDrinksSection from './Home/Partials/SignatureDrinksSection.vue'
import BookingBannerSection from './Home/Partials/BookingBannerSection.vue'
import { useGoogleOneTap } from "@/Composables/useGoogleOneTap";

const { initialize } = useGoogleOneTap()

defineOptions({ layout: MainLayout })

initialize()
</script>

<template>
  <div>
    <HeroSection />
    <QrOrderingSection />
    <SignatureDrinksSection />
    <BookingBannerSection />
  </div>
</template>