<script setup>
defineProps({
  author: { type: Object, default: null }
})
</script>
<template>
  <div v-if="author" class="mt-12 p-6 bg-surface rounded-xl border border-outline-variant/20 flex items-start gap-4">
    <div class="w-14 h-14 rounded-full overflow-hidden bg-primary-container/30 flex-shrink-0 flex items-center justify-center">
      <img v-if="author.avatar" :src="author.avatar" :alt="author.name" class="w-full h-full object-cover" />
      <span v-else class="material-symbols-outlined text-2xl text-primary">person</span>
    </div>
    <div>
      <h4 class="font-serif text-headline-sm text-primary mb-1">{{ author.name }}</h4>
      <p class="font-sans text-body-md text-on-surface-variant">{{ author.bio || 'Người viết tại Nắng Coffee.' }}</p>
    </div>
  </div>
</template>
