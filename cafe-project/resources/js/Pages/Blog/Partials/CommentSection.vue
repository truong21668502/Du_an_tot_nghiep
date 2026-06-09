<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/BaseButton.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
const props = defineProps({
  postId: { type: Number, required: true },
  comments: { type: Array, default: () => [] }
})
const { props: pageProps } = usePage()
const user = pageProps.auth?.user || null
const form = ref({ content: '', rating: null })
const loading = ref(false)
const errors = ref({})
const submitComment = () => {
  if (!form.value.content.trim()) return
  loading.value = true
  router.post(`/bai-viet/${props.postId}/binh-luan`, form.value, {
    preserveScroll: true,
    onSuccess: () => {
      form.value = { content: '', rating: null }
      errors.value = {}
    },
    onError: (err) => { errors.value = err },
    onFinish: () => { loading.value = false }
  })
}
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' })
</script>
<template>
  <section class="py-16 px-margin-mobile md:px-gutter">
    <div class="max-w-[900px] mx-auto">
      <AnimateOnScroll animation="fade-up" :duration="700">
        <h2 class="text-headline-md text-primary mb-8">Bình luận ({{ comments.length }})</h2>
      </AnimateOnScroll>
      <AnimateOnScroll v-if="user" animation="fade-up" :duration="700" :delay="100">
        <form @submit.prevent="submitComment" class="bg-surface rounded-xl border border-outline-variant/20 p-6 mb-10 space-y-4">
          <div class="flex items-center gap-2 mb-2">
            <span v-for="star in 5" :key="star" @click="form.rating = star" class="material-symbols-outlined cursor-pointer text-2xl transition-colors" :class="star <= (form.rating || 0) ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
          </div>
          <textarea v-model="form.content" rows="4" required placeholder="Viết bình luận của bạn..." class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all resize-none"></textarea>
          <p v-if="errors.content" class="text-error text-label-sm">{{ errors.content }}</p>
          <div class="flex justify-end">
            <BaseButton type="submit" variant="primary" :disabled="loading">{{ loading ? 'Đang gửi...' : 'Gửi bình luận' }}</BaseButton>
          </div>
        </form>
      </AnimateOnScroll>
      <AnimateOnScroll v-else animation="fade-up" :duration="700" :delay="100">
        <div class="bg-surface rounded-xl border border-outline-variant/20 p-6 text-center mb-10">
          <p class="font-sans text-body-md text-on-surface-variant">Vui lòng <a href="/dang-nhap" class="text-secondary font-semibold hover:underline">đăng nhập</a> để bình luận.</p>
        </div>
      </AnimateOnScroll>
      <div class="space-y-6">
        <AnimateOnScroll v-for="(comment, index) in comments" :key="comment.id" animation="fade-up" :duration="500" :delay="index * 80">
          <div class="bg-surface rounded-xl border border-outline-variant/20 p-5">
            <div class="flex items-start gap-3 mb-3">
              <div class="w-10 h-10 rounded-full bg-primary-container/30 flex-shrink-0 flex items-center justify-center">
                <img v-if="comment.user?.avatar" :src="comment.user.avatar" :alt="comment.user.name" class="w-full h-full rounded-full object-cover" />
                <span v-else class="material-symbols-outlined text-primary">person</span>
              </div>
              <div class="flex-1">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-sans text-label-md text-on-surface">{{ comment.user?.name || 'Ẩn danh' }}</p>
                    <div v-if="comment.rating" class="flex items-center gap-0.5 mt-0.5">
                      <span v-for="star in 5" :key="star" class="material-symbols-outlined text-sm" :class="star <= comment.rating ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
                    </div>
                  </div>
                  <span class="font-sans text-label-sm text-on-surface-variant">{{ formatDate(comment.created_at) }}</span>
                </div>
              </div>
            </div>
            <p class="font-sans text-body-md text-on-surface-variant ml-13">{{ comment.content }}</p>
          </div>
        </AnimateOnScroll>
      </div>
    </div>
  </section>
</template>
