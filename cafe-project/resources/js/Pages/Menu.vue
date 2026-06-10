<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { useMenuFilters } from '@/Composables/useMenuFilters'
import MenuHeader from './Menu/Partials/MenuHeader.vue'
import MenuGrid from '@/Components/Main/MenuGrid.vue'
import FilterSidebar from '@/Components/Main/FilterSidebar.vue'
import Pagination from '@/Components/Main/Pagination.vue'

defineOptions({ layout: MainLayout })

const props = defineProps({
  categories: Array,
  products: Object,
  filters: Object,
})

const {
  filters,
  showFilterSidebar,
  sortOptions,
  paginatedItems,
  loading,
  totalPages,
  totalItems,
  setCategory,
  setSearch,
  setPriceRange,
  setRating,
  setSortBy,
  setPage,
  toggleFilter,
} = useMenuFilters(props)
</script>

<template>
  <div class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
    <div class="flex gap-8">
      <FilterSidebar
        :filters="filters"
        :show="showFilterSidebar"
        @update:price-range="setPriceRange($event.min, $event.max)"
        @update:rating="setRating"
        @close="showFilterSidebar = false"
      />

      <div class="flex-1 min-w-0">
        <MenuHeader
          :categories="categories"
          :sort-options="sortOptions"
          :filters="filters"
          @update:category="setCategory"
          @update:search="setSearch"
          @update:sort-by="setSortBy"
          @toggle-filter="toggleFilter"
        />

        <MenuGrid
          :items="paginatedItems"
          :loading="loading"
        />

        <Pagination
          v-if="props.products"
          :current-page="filters.page"
          :total-pages="totalPages"
          :total-items="totalItems"
          @page-change="setPage"
        />
      </div>
    </div>
  </div>
</template>