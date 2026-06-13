<script setup>
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/BaseButton.vue'
const props = defineProps({
  reviews: { type: Array, default: () => [] },
  productId: { type: Number, required: true },
  formatDate: { type: Function, required: true },
  submitReview: { type: Function, required: true }
})
const page = usePage()
const user = computed(() => page.props.auth?.user || null)
const showForm = ref(false)
const form = ref({ rating: 5, comment: '' })
const loading = ref(false)
const averageRating = computed(() => {
  if (!props.reviews.length) return 0
  return (props.reviews.reduce((acc, r) => acc + r.rating, 0) / props.reviews.length).toFixed(1)
})
const ratingCounts = computed(() => {
  const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
  props.reviews.forEach(r => { if (counts[r.rating] !== undefined) counts[r.rating]++ })
  return counts
})
const handleSubmitReview = () => {
  loading.value = true
  props.submitReview(props.productId, form.value)
  form.value = { rating: 5, comment: '' }
  showForm.value = false
  loading.value = false
}
</script>
<template>
  <div class="mt-16">
    <h2 class="font-serif text-headline-md text-primary mb-8">Đánh giá sản phẩm</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
      <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 text-center">
        <p class="font-serif text-display-lg text-primary mb-1">{{ averageRating }}</p>
        <div class="flex justify-center gap-0.5 mb-2">
          <span v-for="star in 5" :key="star" class="material-symbols-outlined text-xl" :class="star <= Math.round(averageRating) ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
        </div>
        <p class="font-sans text-body-md text-on-surface-variant">{{ reviews.length }} đánh giá</p>
      </div>
      <div class="md:col-span-2 bg-surface rounded-2xl border border-outline-variant/20 p-6 space-y-2">
        <div v-for="count in 5" :key="count" class="flex items-center gap-3">
          <span class="font-sans text-label-sm text-on-surface w-8">{{ count }} sao</span>
          <div class="flex-1 h-2 bg-surface-container-low rounded-full overflow-hidden"><div class="h-full bg-yellow-500 rounded-full transition-all" :style="{ width: reviews.length ? `${(ratingCounts[count] / reviews.length) * 100}%` : '0%' }"></div></div>
          <span class="font-sans text-label-sm text-on-surface-variant w-8 text-right">{{ ratingCounts[count] }}</span>
        </div>
      </div>
    </div>
    <div v-if="user" class="mb-8">
      <button v-if="!showForm" @click="showForm = true" class="px-6 py-3 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-lg">rate_review</span> Viết đánh giá</button>
      <div v-else class="bg-surface rounded-2xl border border-outline-variant/20 p-6 space-y-4">
        <div class="flex items-center gap-2">
          <span v-for="star in 5" :key="star" @click="form.rating = star" class="material-symbols-outlined cursor-pointer text-3xl transition-colors" :class="star <= form.rating ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
        </div>
        <textarea v-model="form.comment" rows="3" placeholder="Chia sẻ trải nghiệm của bạn..." class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary resize-none"></textarea>
        <div class="flex gap-2 justify-end">
          <button @click="showForm = false" class="px-4 py-2 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors">Hủy</button>
          <BaseButton @click="handleSubmitReview" variant="primary" :disabled="loading">{{ loading ? 'Đang gửi...' : 'Gửi đánh giá' }}</BaseButton>
        </div>
      </div>
    </div>
    <div v-else class="mb-8 p-6 bg-surface rounded-2xl border border-outline-variant/20 text-center">
      <p class="font-sans text-body-md text-on-surface-variant">Vui lòng <Link href="/dang-nhap" class="text-secondary font-semibold hover:underline">đăng nhập</Link> để đánh giá sản phẩm.</p>
    </div>
    <div v-if="reviews.length > 0" class="space-y-4">
      <div v-for="review in reviews" :key="review.id" class="bg-surface rounded-xl border border-outline-variant/20 p-5">
        <div class="flex items-start gap-3 mb-2">
          <div class="w-10 h-10 rounded-full bg-primary-container/30 flex-shrink-0 flex items-center justify-center overflow-hidden">
            <img v-if="review.user?.avatar" :src="review.user.avatar" :alt="review.user.name" class="w-full h-full object-cover" />
            <span v-else class="material-symbols-outlined text-primary">person</span>
          </div>
          <div class="flex-1">
            <div class="flex justify-between items-start">
              <div><p class="font-sans text-label-md text-on-surface">{{ review.user?.name || 'Ẩn danh' }}</p>
                <div class="flex items-center gap-0.5 mt-0.5"><span v-for="star in 5" :key="star" class="material-symbols-outlined text-sm" :class="star <= review.rating ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span></div>
              </div>
              <span class="font-sans text-label-sm text-on-surface-variant">{{ formatDate(review.created_at) }}</span>
            </div>
          </div>
        </div>
        <p v-if="review.comment" class="font-sans text-body-md text-on-surface-variant ml-13">{{ review.comment }}</p>
      </div>
    </div>
    <div v-else class="text-center py-10">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">reviews</span>
      <p class="font-sans text-body-md text-on-surface-variant">Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá!</p>
    </div>
  </div>
</template>
