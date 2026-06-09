<script setup>
import { Link } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/BaseButton.vue'
</script>
<template>
  <div class="text-center py-20">
    <span class="material-symbols-outlined text-7xl text-outline-variant mb-6">shopping_cart_off</span>
    <h2 class="font-serif text-headline-md text-primary mb-3">Giỏ hàng trống</h2>
    <p class="font-sans text-body-md text-on-surface-variant mb-8 max-w-md mx-auto">
      Hãy thêm những món yêu thích vào giỏ hàng để thưởng thức hương vị tuyệt vời.
    </p>
    <Link href="/thuc-don">
      <BaseButton variant="primary">Xem thực đơn</BaseButton>
    </Link>
  </div>
</template>
