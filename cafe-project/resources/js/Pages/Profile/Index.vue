<script setup>
import ProfileLayout from '@/Layouts/ProfileLayout.vue'
defineOptions({ layout: ProfileLayout })
</script>
<template>
  <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 text-center py-12">
    <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">person</span>
    <p class="font-sans text-body-md text-on-surface-variant">Chọn một mục từ menu bên trái</p>
  </div>
</template>