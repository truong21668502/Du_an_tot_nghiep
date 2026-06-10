<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import CommentSection from './Partials/CommentSection.vue'
import RelatedPosts from './Partials/RelatedPosts.vue'
import AuthorBox from './Partials/AuthorBox.vue'
defineOptions({ layout: MainLayout })
const { props } = usePage()
const post = props.post || {}
const relatedPosts = props.relatedPosts || []
const comments = props.comments || []
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' })
}
const readingTime = (content) => {
  if (!content) return '1 phút đọc'
  const words = content.replace(/<[^>]*>/g, '').split(/\s+/).length
  const minutes = Math.max(1, Math.ceil(words / 200))
  return `${minutes} phút đọc`
}
const shareLinks = [
  { icon: 'camera_alt', label: 'Facebook', url: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}` },
  { icon: 'alternate_email', label: 'Twitter', url: `https://twitter.com/intent/tweet?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(post.title)}` },
  { icon: 'mail', label: 'Email', url: `mailto:?subject=${encodeURIComponent(post.title)}&body=${encodeURIComponent(window.location.href)}` }
]
</script>
<template>
  <div class="w-full">
    <article class="max-w-[900px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="mb-8">
          <Link :href="`/bai-viet/danh-muc/${post.category?.slug || 'all'}`" class="inline-block px-3 py-1 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-sm mb-4 hover:bg-primary-container/50 transition-colors">
            {{ post.category?.name || 'Chưa phân loại' }}
          </Link>
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-4">{{ post.title }}</h1>
          <div class="flex flex-wrap items-center gap-4 text-on-surface-variant font-sans text-label-sm">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">person</span> {{ post.author?.name || 'Nắng Coffee' }}</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">calendar_today</span> {{ formatDate(post.published_at || post.created_at) }}</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">schedule</span> {{ readingTime(post.content) }}</span>
          </div>
        </div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="200">
        <div v-if="post.thumbnail_url" class="rounded-xl overflow-hidden mb-10 shadow-soft">
          <img :src="post.thumbnail_url" :alt="post.title" class="w-full h-auto object-cover" />
        </div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="300">
        <div class="prose prose-lg max-w-none font-sans text-body-lg text-on-surface leading-relaxed space-y-6" v-html="post.content"></div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="400">
        <div class="flex flex-wrap items-center justify-between gap-4 mt-10 pt-8 border-t border-outline-variant/20">
          <div class="flex flex-wrap gap-2">
            <span v-for="tag in (post.tags || [])" :key="tag" class="px-3 py-1 bg-surface-container-low border border-outline-variant/20 rounded-full font-sans text-label-sm text-on-surface-variant">{{ tag }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="font-sans text-label-sm text-on-surface-variant">Chia sẻ:</span>
            <a v-for="link in shareLinks" :key="link.label" :href="link.url" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-low hover:bg-primary-container/30 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all">
              <span class="material-symbols-outlined text-sm">{{ link.icon }}</span>
            </a>
          </div>
        </div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="fade-up" :duration="700" :delay="500">
        <AuthorBox :author="post.author" />
      </AnimateOnScroll>
    </article>
    <RelatedPosts :posts="relatedPosts" :format-date="formatDate" />
    <CommentSection :post-id="post.id" :comments="comments" />
  </div>
</template>
