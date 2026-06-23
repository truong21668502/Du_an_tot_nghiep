<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const order = computed(() => page.props.order)
</script>
<template>
  <div class="w-full">
    <div class="max-w-[640px] mx-auto px-margin-mobile py-24 text-center">
      <AnimateOnScroll animation="scale-in" :duration="700">
        <div class="w-24 h-24 mx-auto mb-6 bg-secondary-container/30 rounded-full flex items-center justify-center animate-pulse">
          <span class="material-symbols-outlined text-5xl text-secondary">hourglass_top</span>
        </div>
        <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-3">Đang chờ xác nhận</h1>
        <p class="text-body-lg text-on-surface-variant mb-2">Mã đơn hàng: <span class="font-bold text-primary">{{ order?.order_code }}</span></p>
        <p class="text-body-md text-on-surface-variant mb-8">Nhân viên đang kiểm tra giao dịch của bạn. Vui lòng chờ trong giây lát.</p>
        <div class="flex flex-wrap justify-center gap-4">
          <Link href="/ho-so/don-hang"><BaseButton variant="primary">Xem đơn hàng</BaseButton></Link>
          <Link href="/"><BaseButton variant="secondary">Về trang chủ</BaseButton></Link>
        </div>
      </AnimateOnScroll>
    </div>
  </div>
</template>
