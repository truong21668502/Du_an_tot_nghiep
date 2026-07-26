<script setup>
import { computed } from 'vue'

const props = defineProps({
  banner: { type: Object, required: true },
  preview: { type: Boolean, default: false },
})

const zone = computed(() => {
  const pos = props.banner.position || 'center'
  if (pos === 'center') return { vertical: 'center', horizontal: 'center' }
  const [vertical, horizontal] = pos.split('-')
  return {
    vertical: vertical || 'center',
    horizontal: horizontal || 'center',
  }
})

const ALIGN_SELF_MAP = { top: 'start', center: 'center', bottom: 'end' }
const JUSTIFY_SELF_MAP = { left: 'start', center: 'center', right: 'end' }

const contentStyle = computed(() => ({
  alignSelf: ALIGN_SELF_MAP[zone.value.vertical] || 'center',
  justifySelf: JUSTIFY_SELF_MAP[zone.value.horizontal] || 'center',
  textAlign: props.banner.text_align || 'center',
}))

const hasContent = computed(
  () =>
    props.banner.title ||
    props.banner.description ||
    (props.banner.button_text && props.banner.button_url),
)
</script>

<template>
  <div
    class="banner-container relative w-full overflow-hidden rounded-2xl bg-surface-container-low"
    style="aspect-ratio: 16/6"
  >
    <!-- Ảnh nền -->
    <img
      v-if="banner.image_url"
      :src="banner.image_url"
      class="absolute inset-0 h-full w-full object-cover"
    />
    <div
      v-else
      class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-outline-variant"
    >
      <span class="material-symbols-outlined text-5xl">image</span>
      <p class="font-sans text-label-sm">Chưa có ảnh</p>
    </div>

    <!-- Overlay nhẹ -->
    <div
      v-if="hasContent"
      class="absolute inset-0 bg-gradient-to-t from-black/35 via-black/5 to-transparent"
    />

    <!-- Khối nội dung -->
    <div class="absolute inset-0 grid p-6 md:p-10">
      <div
        v-if="hasContent"
        class="banner-content space-y-3"
        :style="{
          alignSelf: contentStyle.alignSelf,
          justifySelf: contentStyle.justifySelf,
          textAlign: contentStyle.textAlign,
        }"
        :class="banner.theme === 'dark' ? 'text-white' : 'text-on-surface'"
      >
        <h2 v-if="banner.title" class="banner-heading font-serif leading-tight">
          {{ banner.title }}
        </h2>
        <p v-if="banner.description" class="banner-description font-sans opacity-90">
          {{ banner.description }}
        </p>
        <component
          :is="preview ? 'span' : 'a'"
          v-if="banner.button_text && banner.button_url"
          :href="preview ? undefined : banner.button_url"
          class="banner-button inline-block rounded-full font-sans transition-colors cursor-pointer"
          :class="
            banner.theme === 'dark'
              ? 'bg-white text-on-surface hover:bg-white/90'
              : 'bg-primary text-on-primary hover:bg-primary/90'
          "
        >
          {{ banner.button_text }}
        </component>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Kích hoạt container queries cho banner */
.banner-container {
  container-type: inline-size;
}

/* Tiêu đề: font-size thay đổi theo chiều rộng banner */
.banner-heading {
  font-size: clamp(1.25rem, 3cqw + 0.5rem, 3rem);
}

/* Mô tả */
.banner-description {
  font-size: clamp(0.875rem, 1.5cqw + 0.5rem, 1.5rem);
  line-height: 1.5;
}

/* Nút bấm: font-size và padding đều linh hoạt */
.banner-button {
  font-size: clamp(0.75rem, 1.2cqw + 0.2rem, 1.2rem);
  padding: clamp(0.4rem, 0.8cqw + 0.1rem, 0.8rem) clamp(0.8rem, 1.8cqw + 0.4rem, 1.8rem);
}


/* Giới hạn chiều rộng khối nội dung cũng co giãn theo banner */
.banner-content {
  max-width: clamp(18rem, 50%, 42rem);
}
</style>