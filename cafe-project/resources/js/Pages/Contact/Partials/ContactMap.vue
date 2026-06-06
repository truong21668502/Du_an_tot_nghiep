<script setup>
import { ref, onMounted } from 'vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
const mapLoaded = ref(false)
const storeInfo = {
  name: 'Cà Phê Mới',
  address: '137 Đường Nguyễn Thị Thập, Thanh Khê, Đà Nẵng, Việt Nam',
  lat: 10.7769,
  lng: 106.7009,
  zoom: 16
}
const mapUrl = `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d958.4502622652067!2d108.16879227575338!3d16.075810615347628!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x312d0763e938a625%3A0xed2edc58d1b6fe5b!2zQ2FvIMSR4bqzbmcgRlBUIFBvbHl0ZWNobmljIMSQw6AgTuG6tW5n!5e0!3m2!1svi!2s!4v1780736609360!5m2!1svi!2s`
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
