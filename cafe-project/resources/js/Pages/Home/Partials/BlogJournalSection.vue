<script setup>
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import { router, Link } from "@inertiajs/vue3";
defineProps({
  articles: {
    type: Array,
    required: true,
    default: () => []
  }
})
</script>
<template>
  <section class="py-24 px-margin-mobile md:px-gutter bg-surface">
    <div class="max-w-[1280px] mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-16 gap-6">
        <div>
          <span class="text-secondary font-sans text-label-md uppercase tracking-wider block mb-2">Nhật ký cà phê</span>
          <h2 class="text-headline-md text-primary font-bold font-serif">Góc Chia Sẻ Kiến Thức</h2>
          <p class="text-body-md text-on-surface-variant max-w-xl mt-1">
            Ghi chép hành trình tìm hạt mộc, những phương pháp pha chế độc đáo và câu chuyện văn hóa cà phê thường nhật.
          </p>
        </div>
        <Link :href="route('blog.index')" class="inline-flex items-center gap-2 text-secondary font-sans text-label-md hover:text-primary hover:translate-x-1 transition-all duration-300">
          Xem tất cả bài viết
          <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </Link>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <Link
          :href="route('blog.dispatch', post.slug)" 
          v-for="post in articles" 
          :key="post.title"
          class="bg-surface-container-low rounded-2xl overflow-hidden border border-outline-variant/30 flex flex-col md:flex-row shadow-soft hover:shadow-md transition-shadow group duration-300"
        >
          <div class="w-full md:w-2/5 h-56 md:h-auto relative overflow-hidden shrink-0">
            <img 
              :src="post.thumbnail" 
              :alt="post.title" 
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
              loading="lazy"
            />
          </div>
          <div class="p-6 md:p-8 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-4 text-label-sm text-on-surface-variant mb-4">
                <span>{{ post.date }}</span>
                <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                <span>{{ post.readTime || post.read_time }}</span>
              </div>
              <h3 class="text-headline-sm text-primary font-serif font-semibold leading-snug group-hover:text-secondary transition-colors duration-300 mb-3">
                {{ post.title }}
              </h3>
              <p class="text-body-md text-on-surface-variant line-clamp-2 leading-relaxed">
                {{ post.desc || post.excerpt || post.short_description }}
              </p>
            </div>
            <button class="inline-flex items-center gap-2 text-label-sm text-secondary font-semibold hover:text-primary mt-6 group/btn">
              Đọc tiếp
              <span class="material-symbols-outlined text-sm group-hover/btn:translate-x-1 transition-transform">arrow_forward</span>
            </button>
          </div>
        </Link>
      </div>
    </div>
  </section>
</template>
