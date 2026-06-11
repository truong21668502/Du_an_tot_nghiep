<script setup>
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
defineProps({
  tables: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  getTableSizeClass: {
    type: Function,
    required: true
  },
  getTableStatusColor: {
    type: Function,
    required: true
  },
  getTableStatusLabel: {
    type: Function,
    required: true
  }
})
defineEmits(['table-click'])
</script>
<template>
  <section class="py-16 px-margin-mobile md:px-gutter">
    <div class="max-w-[1280px] mx-auto">
      <div v-if="loading" class="flex justify-center py-20">
        <div class="w-12 h-12 border-4 border-primary-container border-t-primary rounded-full animate-spin"></div>
      </div>
      <div v-else-if="error" class="text-center py-20">
        <span class="material-symbols-outlined text-6xl text-error mb-4">error</span>
        <p class="font-sans text-body-lg text-error">{{ error }}</p>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 auto-rows-[120px] gap-4">
        <AnimateOnScroll 
          v-for="(table, index) in tables" 
          :key="table.id"
          animation="scale-in"
          :duration="500"
          :delay="index * 80"
          :class="getTableSizeClass(table.capacity)"
        >
          <button
            @click="$emit('table-click', table)"
            :disabled="table.status !== 'EMPTY'"
            :class="[
              'w-full h-full rounded-xl border-2 p-4 flex flex-col items-center justify-center gap-2 transition-all duration-300',
              getTableStatusColor(table.status),
              table.status === 'EMPTY' 
                ? 'hover:shadow-lg hover:scale-[1.02] cursor-pointer hover:border-primary' 
                : 'cursor-not-allowed opacity-60'
            ]"
          >
            <span class="material-symbols-outlined text-2xl">
              {{ table.capacity > 6 ? 'table_restaurant' : 'table_bar' }}
            </span>
            <div class="text-center">
              <h3 class="font-serif text-headline-sm">{{ table.table_name }}</h3>
              <p class="font-sans text-label-sm flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-sm">person</span>
                {{ table.capacity }} người
              </p>
              <span class="inline-block mt-1 px-2 py-0.5 rounded-full font-sans text-label-sm bg-white/50">
                {{ getTableStatusLabel(table.status) }}
              </span>
            </div>
          </button>
        </AnimateOnScroll>
      </div>
    </div>
  </section>
</template>
