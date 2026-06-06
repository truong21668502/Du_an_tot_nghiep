<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'

defineOptions({ layout: MainLayout })
</script>

<template>
  <div class="w-full max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
    <div class="text-center py-20">
      <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">shopping_cart</span>
      <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-4">Giỏ Hàng</h1>
      <p class="text-body-lg text-on-surface-variant">Giỏ hàng của bạn đang trống. Hãy thêm món vào nhé!</p>
    </div>
  </div>
</template>