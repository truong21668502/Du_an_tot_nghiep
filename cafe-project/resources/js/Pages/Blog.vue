<script setup>
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useBlog } from '@/Composables/useBlog'
import BlogHeader from './Blog/Partials/BlogHeader.vue'
import BlogGrid from './Blog/Partials/BlogGrid.vue'
import BlogPagination from './Blog/Partials/BlogPagination.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
defineOptions({ layout: MainLayout })
const { props } = usePage()
const { posts, categories, loading, filters, totalPages, filteredPosts, setSearch, setCategory, setPage, formatDate } = useBlog(
  props.posts || [],
  props.categories || [],
  props.filters || {}
)
</script>
<template>
  <div class="w-full">
    <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="mb-12 text-center">
          <span class="inline-block px-4 py-2 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-sm mb-4">Blog</span>
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-3">Tin tức & Câu chuyện</h1>
          <p class="text-body-lg text-on-surface-variant max-w-2xl mx-auto">Khám phá những câu chuyện thú vị về cà phê, văn hóa và con người tại Nắng Coffee.</p>
        </div>
      </AnimateOnScroll>
      <BlogHeader
        v-model:search="filters.search"
        :categories="categories"
        :active-category="filters.category"
        @update:search="setSearch"
        @update:category="setCategory"
      />
      <div v-if="loading" class="flex justify-center py-20">
        <div class="w-12 h-12 border-4 border-primary-container border-t-primary rounded-full animate-spin"></div>
      </div>
      <div v-else-if="filteredPosts.length === 0" class="text-center py-20">
        <span class="material-symbols-outlined text-7xl text-outline-variant mb-4">article_off</span>
        <h2 class="font-serif text-headline-md text-primary mb-2">Không tìm thấy bài viết</h2>
        <p class="font-sans text-body-md text-on-surface-variant">Thử thay đổi từ khóa hoặc bộ lọc</p>
      </div>
      <BlogGrid v-else :posts="filteredPosts" :format-date="formatDate" />
      <BlogPagination
        v-if="totalPages > 1"
        :current-page="filters.page"
        :total-pages="totalPages"
        @page-change="setPage"
      />
    </div>
  </div>
</template>
