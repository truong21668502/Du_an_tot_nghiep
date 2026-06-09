<script setup>
import { Link } from '@inertiajs/vue3'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
defineProps({
  posts: { type: Array, default: () => [] },
  formatDate: { type: Function, required: true }
})
</script>
<template>
  <section v-if="posts.length > 0" class="py-16 px-margin-mobile md:px-gutter bg-surface-container-low">
    <div class="max-w-[1280px] mx-auto">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <h2 class="text-headline-md text-primary text-center mb-10">Bài viết liên quan</h2>
      </AnimateOnScroll>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <AnimateOnScroll v-for="(post, index) in posts" :key="post.id" animation="fade-up" :duration="500" :delay="index * 100">
          <Link :href="`/bai-viet/${post.slug}`" class="block bg-surface rounded-xl border border-outline-variant/20 overflow-hidden group hover:shadow-soft transition-all duration-500">
            <div class="aspect-[16/10] overflow-hidden bg-surface-container-low">
              <img :src="post.thumbnail_url" :alt="post.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy" />
            </div>
            <div class="p-4">
              <h3 class="font-serif text-headline-sm text-primary mb-1 group-hover:text-secondary transition-colors line-clamp-2">{{ post.title }}</h3>
              <span class="font-sans text-label-sm text-on-surface-variant">{{ formatDate(post.published_at || post.created_at) }}</span>
            </div>
          </Link>
        </AnimateOnScroll>
      </div>
    </div>
  </section>
</template>
