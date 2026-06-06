<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import HeroSection from './About/Partials/HeroSection.vue'
import StorySection from './About/Partials/StorySection.vue'
import ValuesSection from './About/Partials/ValuesSection.vue'
import StatsSection from './About/Partials/StatsSection.vue'
import TeamSection from './About/Partials/TeamSection.vue'
import CTASection from './About/Partials/CTASection.vue'

defineOptions({ layout: MainLayout })
</script>

<template>
  <div>
    <HeroSection />
    <StorySection />
    <ValuesSection />
    <StatsSection />
    <TeamSection />
    <CTASection />
  </div>
</template>