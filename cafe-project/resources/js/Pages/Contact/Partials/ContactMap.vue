<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'

const page = usePage()
const settings = computed(() => page.props.settings || {})
const brand = computed(() => page.props.brand || {})

const mapLoaded = ref(false)

const storeInfo = computed(() => {
  const shop = settings.value?.shop_info || {}
  return {
    name: brand.value?.brand_name || shop.shop_name || 'Cửa hàng',
    address: shop.shop_address || '',
    lat: parseFloat(shop.shop_lat) || 0,
    lng: parseFloat(shop.shop_lng) || 0,
    zoom: 16
  }
})

const mapUrl = computed(() => settings.value?.website?.google_maps_embed_url || '')

onMounted(() => {
  setTimeout(() => {
    mapLoaded.value = true
  }, 300)
})
</script>

<template>
  <section class="py-24 px-margin-mobile md:px-gutter bg-surface-container-low">
    <div class="max-w-[1280px] mx-auto">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <div class="text-center mb-12">
          <h2 class="text-headline-md text-primary mb-4">Tìm Chúng Tôi Trên Bản Đồ</h2>
          <p class="text-body-md text-on-surface-variant max-w-2xl mx-auto">
            Ghé thăm cửa hàng của chúng tôi tại {{ storeInfo.address }}.
          </p>
        </div>
      </AnimateOnScroll>
      <AnimateOnScroll animation="scale-in" :duration="800" :delay="200">
        <div class="rounded-xl overflow-hidden shadow-soft border border-outline-variant/20">
          <!-- Google Maps Embed -->
          <div class="w-full aspect-[16/9] md:aspect-[21/9] bg-surface-container relative">
            <div v-if="!mapLoaded" class="absolute inset-0 flex items-center justify-center">
              <div class="w-12 h-12 border-4 border-primary-container border-t-primary rounded-full animate-spin"></div>
            </div>
            <iframe
              :src="mapUrl"
              width="100%"
              height="100%"
              style="border:0;"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              class="absolute inset-0"
              @load="mapLoaded = true"
            ></iframe>
          </div>
          <!-- Store Info Overlay -->
          <div class="p-6 md:p-8 bg-surface">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-primary-container/30 rounded-full flex items-center justify-center flex-shrink-0">
                  <span class="material-symbols-outlined text-2xl text-primary">storefront</span>
                </div>
                <div>
                  <h3 class="font-serif text-headline-sm text-primary mb-1">{{ storeInfo.name }}</h3>
                  <p class="font-sans text-body-md text-on-surface-variant">{{ storeInfo.address }}</p>
                </div>
              </div>
              <a 
                :href="`https://www.google.com/maps/dir/?api=1&destination=${storeInfo.lat},${storeInfo.lng}`"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-all duration-300 shadow-sm hover:shadow-md whitespace-nowrap"
              >
                <span class="material-symbols-outlined text-lg">directions</span>
                Chỉ đường
              </a>
            </div>
          </div>
        </div>
      </AnimateOnScroll>
    </div>
  </section>
</template>
