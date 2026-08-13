<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const brand = computed(() => page.props.brand || null)
const brandName = computed(() => brand.value?.brand_name || 'Nắng Coffee')
const logoUrl = computed(() => brand.value?.logo_url || 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780996916/NangCoffee_logo_fullmau_wl8jbz.png')
</script>
<template>
  <div class="min-h-screen bg-surface-container-low">
    <header class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30">
      <div class="flex justify-between items-center h-20 px-margin-mobile md:px-gutter max-w-[1280px] mx-auto">
        <Link href="/" class="flex items-center gap-2.5 hover:opacity-80 transition-opacity">
          <img 
            :src="logoUrl" 
            :alt="brandName" 
            class="w-10 h-10 rounded-full object-cover border-2 border-primary/20"
          />
          <span class="font-serif text-headline-sm font-bold text-primary">{{ brandName }}</span>
        </Link>
        <Link href="/" class="font-sans text-label-sm text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">arrow_back</span>
          Về trang chủ
        </Link>
      </div>
    </header>
    <main class="pt-16">
      <slot />
    </main>
  </div>
</template>
