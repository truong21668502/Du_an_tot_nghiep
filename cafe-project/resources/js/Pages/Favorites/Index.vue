<script setup>
import { computed } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useFavorites } from '@/Composables/useFavorites'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import MenuGrid from '@/Components/Main/MenuGrid.vue'
defineOptions({ layout: MainLayout })
const page = usePage()
const favorites = computed(() => page.props.favorites)
const { removeFavorite } = useFavorites()
const items = computed(() => {
  if (!favorites.value?.data) return []
  return favorites.value.data.map(fav => ({
    id: fav.id,
    name: fav.name,
    price: fav.price,
    category: fav.category || 'all',
    badge: fav.badge || null,
    badgeVariant: fav.badgeVariant || 'tertiary',
    description: fav.description || '',
    image: fav.image,
    rating: fav.rating || 0,
    createdAt: fav.createdAt,
    slug: fav.slug,
    variants: fav.variants || [],
    isFavorited: true,
  }))
})

const totalPages = computed(() => favorites.value?.last_page || 1)
const currentPage = computed(() => favorites.value?.current_page || 1)
const totalItems = computed(() => favorites.value?.total || 0)
</script>
<template>
  <div class="w-full">
    <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="mb-12 text-center">
          <span class="inline-block px-4 py-2 bg-secondary-container/30 text-on-secondary-container rounded-full font-sans text-label-sm mb-4">Yêu thích</span>
          <h1 class="text-display-lg-mobile md:text-display-lg text-primary mb-3">Sản phẩm yêu thích</h1>
          <p class="text-body-md text-on-surface-variant max-w-xl mx-auto">
            Danh sách những món bạn đã lưu lại để dễ dàng tìm thấy và đặt hàng sau.
          </p>
        </div>
      </AnimateOnScroll>
      <MenuGrid :items="items" :loading="false" />
      <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-12">
        <Link
          v-for="page in totalPages"
          :key="page"
          :href="`${route('favorites.index')}?page=${page}`"
          :class="[
            'w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-sm transition-all',
            currentPage === page ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-low'
          ]"
          preserve-scroll
        >
          {{ page }}
        </Link>
      </div>
    </div>
  </div>
</template>
