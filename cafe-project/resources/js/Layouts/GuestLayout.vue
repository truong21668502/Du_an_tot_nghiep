<script setup>
import { Link } from '@inertiajs/vue3'
</script>
<template>
  <div class="min-h-screen bg-surface-container-low">
    <header class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30">
      <div class="flex justify-between items-center h-16 px-margin-mobile md:px-gutter max-w-[1280px] mx-auto">
        <Link href="/" class="font-serif text-headline-sm font-bold text-primary hover:opacity-80 transition-opacity">
          Cà Phê Mới
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
