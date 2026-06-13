<script setup>
import { ref } from 'vue'
const props = defineProps({ mainImage: { type: String, default: '' }, images: { type: Array, default: () => [] }, productName: { type: String, default: '' } })
const emit = defineEmits(['update:active-image'])
const showLightbox = ref(false)
</script>
<template>
  <div class="space-y-4">
    <div class="aspect-square rounded-2xl overflow-hidden bg-surface-container-low cursor-pointer relative group" @click="showLightbox = true">
      <img v-if="mainImage" :src="mainImage" :alt="productName" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
      <div v-else class="w-full h-full flex items-center justify-center"><span class="material-symbols-outlined text-6xl text-outline-variant">image</span></div>
      <div v-if="images.length > 1" class="absolute bottom-3 right-3 px-3 py-1 bg-surface/80 backdrop-blur rounded-full font-sans text-label-sm text-on-surface">{{ images.length }} ảnh</div>
    </div>
    <div v-if="images.length > 1" class="flex gap-2 overflow-x-auto pb-2">
      <button v-for="(img, idx) in images" :key="idx" @click="emit('update:active-image', img)" :class="['w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 border-2 transition-all', mainImage === img ? 'border-primary' : 'border-transparent hover:border-outline-variant']">
        <img :src="img" :alt="`${productName} ${idx + 1}`" class="w-full h-full object-cover" />
      </button>
    </div>
    <Teleport to="body">
      <div v-if="showLightbox" class="fixed inset-0 z-[999] bg-black/90 flex items-center justify-center p-4" @click="showLightbox = false">
        <button @click="showLightbox = false" class="absolute top-4 right-4 text-white p-2 hover:bg-white/20 rounded-full transition-colors"><span class="material-symbols-outlined text-3xl">close</span></button>
        <img :src="mainImage" :alt="productName" class="max-w-full max-h-[90vh] object-contain rounded-xl" @click.stop />
      </div>
    </Teleport>
  </div>
</template>
