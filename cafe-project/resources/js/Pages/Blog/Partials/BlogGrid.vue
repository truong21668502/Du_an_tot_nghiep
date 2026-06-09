<script setup>
import { Link } from '@inertiajs/vue3'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
defineProps({
  posts: { type: Array, required: true },
  formatDate: { type: Function, required: true }
})
</script>
<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <AnimateOnScroll v-for="(post, index) in posts" :key="post.id" animation="fade-up" :duration="500" :delay="index * 80">
      <Link :href="`/bai-viet/${post.slug}`" class="block bg-surface rounded-xl border border-outline-variant/20 overflow-hidden group hover:shadow-soft transition-all duration-500 h-full">
        <div class="aspect-[16/10] overflow-hidden bg-surface-container-low">
          <img :src="post.thumbnail_url" :alt="post.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy" />
        </div>
        <div class="p-5">
          <span class="inline-block px-2 py-0.5 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-sm mb-3">{{ post.category?.name }}</span>
          <h3 class="font-serif text-headline-sm text-primary mb-2 group-hover:text-secondary transition-colors line-clamp-2">{{ post.title }}</h3>
          <p class="font-sans text-body-md text-on-surface-variant line-clamp-2 mb-4">{{ post.excerpt }}</p>
          <div class="flex justify-between items-center text-on-surface-variant font-sans text-label-sm">
            <span>{{ formatDate(post.published_at || post.created_at) }}</span>
            <span class="flex items-center gap-1 text-secondary group-hover:translate-x-1 transition-transform">Đọc tiếp <span class="material-symbols-outlined text-sm">arrow_forward</span></span>
          </div>
        </div>
      </Link>
    </AnimateOnScroll>
  </div>
</template>
