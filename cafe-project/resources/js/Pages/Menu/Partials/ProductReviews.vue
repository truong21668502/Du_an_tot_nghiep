<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/BaseButton.vue'

const props = defineProps({
    reviews:       { type: Array,    default: () => [] },
    productId:     { type: Number,   required: true },
    formatDate:    { type: Function, required: true },
    submitReview:  { type: Function, required: true },
    updateReview:  { type: Function, required: true },
    deleteReview:  { type: Function, required: true },
    errors:        { type: Object,   default: () => ({}) },
})


const page = usePage()
const user = computed(() => page.props.auth?.user || null)


const showForm    = ref(false)
const form        = ref({ rating: 5, comment: '' })
const loading     = ref(false)
const editingId   = ref(null)        // id review đang được sửa
const editForm    = ref({ rating: 5, comment: '' })

const localReviews = ref([...props.reviews])
watch(() => props.reviews, (val) => { localReviews.value = [...val] })

const averageRating = computed(() => {
    if (!localReviews.value.length) return 0
    return (localReviews.value.reduce((acc, r) => acc + r.rating, 0) / localReviews.value.length).toFixed(1)
})

const ratingCounts = computed(() => {
    const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
    localReviews.value.forEach(r => { if (counts[r.rating] !== undefined) counts[r.rating]++ })
    return counts
})


// ── Submit mới ──────────────────────────────────────────────────────────────
const handleSubmitReview = async () => {
    loading.value = true
    try {
        const newReview = await props.submitReview(props.productId, form.value)
        localReviews.value.unshift(newReview)
        form.value     = { rating: 5, comment: '' }
        showForm.value = false
    } catch {
        // lỗi đã toast trong composable
    } finally {
        loading.value = false
    }
}

// ── Sửa ─────────────────────────────────────────────────────────────────────
const startEdit = (review) => {
    editingId.value = review.id
    editForm.value  = { rating: review.rating, comment: review.comment || '' }
}

const cancelEdit = () => { editingId.value = null }

const handleUpdateReview = async (reviewId) => {
    loading.value = true
    try {
        const updated = await props.updateReview(reviewId, editForm.value)
        const idx = localReviews.value.findIndex(r => r.id === reviewId)
        if (idx !== -1) localReviews.value[idx] = { ...localReviews.value[idx], ...updated }
        editingId.value = null
    } catch {
        // lỗi đã toast trong composable
    } finally {
        loading.value = false
    }
}

// ── Xóa ─────────────────────────────────────────────────────────────────────
const handleDeleteReview = async (reviewId) => {
    if (!confirm('Bạn có chắc muốn xóa đánh giá này?')) return
    loading.value = true
    try {
        await props.deleteReview(reviewId)
        localReviews.value = localReviews.value.filter(r => r.id !== reviewId)
    } catch {
        // lỗi đã toast trong composable
    } finally {
        loading.value = false
    }
}
</script>


<template>
    <div class="mt-16">
        <h2 class="font-serif text-headline-md text-primary mb-8">Đánh giá sản phẩm</h2>

        <!-- Rating summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 text-center">
                <p class="font-serif text-display-lg text-primary mb-1">{{ averageRating }}</p>
                <div class="flex justify-center gap-0.5 mb-2">
                    <span v-for="star in 5" :key="star" class="material-symbols-outlined text-xl"
                        :class="star <= Math.round(averageRating) ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
                </div>
                <p class="font-sans text-body-md text-on-surface-variant">{{ localReviews.length }} đánh giá</p>
            </div>
            <div class="md:col-span-2 bg-surface rounded-2xl border border-outline-variant/20 p-6 space-y-2">
                <div v-for="count in [5,4,3,2,1]" :key="count" class="flex items-center gap-3">
                    <span class="font-sans text-label-sm text-on-surface w-8">{{ count }} sao</span>
                    <div class="flex-1 h-2 bg-surface-container-low rounded-full overflow-hidden">
                        <div class="h-full bg-yellow-500 rounded-full transition-all"
                            :style="{ width: localReviews.length ? `${(ratingCounts[count] / localReviews.length) * 100}%` : '0%' }"/>
                    </div>
                    <span class="font-sans text-label-sm text-on-surface-variant w-8 text-right">{{ ratingCounts[count] }}</span>
                </div>
            </div>
        </div>

        <!-- Form thêm mới -->
        <div v-if="user" class="mb-8">
            <button v-if="!showForm" @click="showForm = true"
                class="px-6 py-3 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">rate_review</span> Viết đánh giá
            </button>
            <div v-else class="bg-surface rounded-2xl border border-outline-variant/20 p-6 space-y-4">
                <div class="flex items-center gap-2">
                    <span v-for="star in 5" :key="star" @click="form.rating = star"
                        class="material-symbols-outlined cursor-pointer text-3xl transition-colors"
                        :class="star <= form.rating ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
                </div>
                <textarea v-model="form.comment" rows="3" placeholder="Chia sẻ trải nghiệm của bạn..."
                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary resize-none"/>
                <p
                    v-if="errors.comment"

                    class="mt-0 flex items-center gap-1 text-sm text-error"
                >
                    <span>{{ errors.comment[0] }}</span>
                </p>
                <div class="flex gap-2 justify-end">
                    <button @click="showForm = false"
                        class="px-4 py-2 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors">
                        Hủy
                    </button>
                    <BaseButton @click="handleSubmitReview" variant="primary" :disabled="loading">
                        {{ loading ? 'Đang gửi...' : 'Gửi đánh giá' }}
                    </BaseButton>
                </div>
            </div>
        </div>

        <div v-else class="mb-8 p-6 bg-surface rounded-2xl border border-outline-variant/20 text-center">
            <p class="font-sans text-body-md text-on-surface-variant">
                Vui lòng <Link href="/dang-nhap" class="text-secondary font-semibold hover:underline">đăng nhập</Link> để đánh giá sản phẩm.
            </p>
        </div>

        <!-- Danh sách review -->
        <div v-if="localReviews.length > 0" class="space-y-4">
            <div v-for="review in localReviews" :key="review.id"
                class="bg-surface rounded-xl border border-outline-variant/20 p-5">

                <!-- Header: avatar + tên + sao + ngày + nút sửa/xóa -->
                <div class="flex items-start gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-primary-container/30 flex-shrink-0 flex items-center justify-center overflow-hidden">
                        <img v-if="review.user?.avatar" :src="review.user.avatar" class="w-full h-full object-cover" />
                        <span v-else class="material-symbols-outlined text-primary">person</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-sans text-label-md text-on-surface">
                                    {{ review.user?.full_name || review.user?.name || 'Ẩn danh' }}
                                </p>
                                <div class="flex items-center gap-0.5 mt-0.5">
                                    <span v-for="star in 5" :key="star" class="material-symbols-outlined text-sm"
                                        :class="star <= review.rating ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-sans text-label-sm text-on-surface-variant">
                                    {{ formatDate(review.created_at) }}
                                </span>
                                <!-- Chỉ hiện nút khi là review của chính mình -->
                                <div v-if="user && review.user.id === user.id" class="flex gap-1">
                                    <button @click="startEdit(review)"
                                        class="material-symbols-outlined text-base text-on-surface-variant hover:text-primary transition-colors">
                                        edit
                                    </button>
                                    <button @click="handleDeleteReview(review.id)" :disabled="loading"
                                        class="material-symbols-outlined text-base text-on-surface-variant hover:text-error transition-colors">
                                        delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nội dung review hoặc form sửa -->
                <div class="ml-13">
                    <template v-if="editingId === review.id">
                        <!-- Form sửa inline -->
                        <div class="space-y-3 mt-2">
                            <div class="flex items-center gap-1">
                                <span v-for="star in 5" :key="star" @click="editForm.rating = star"
                                    class="material-symbols-outlined cursor-pointer text-2xl transition-colors"
                                    :class="star <= editForm.rating ? 'text-yellow-500 fill-icon' : 'text-outline-variant'">star</span>
                            </div>
                            <textarea v-model="editForm.comment" rows="2"
                                class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary resize-none"/>
                            <p
                                v-if="errors.comment"
                                class="mt-0 flex items-center gap-1 text-sm text-error">
                                <span>{{ errors.comment[0] }}</span>
                            </p>
                            <div class="flex gap-2 justify-end">
                                <button @click="cancelEdit"
                                    class="px-4 py-2 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors">
                                    Hủy
                                </button>
                                <BaseButton @click="handleUpdateReview(review.id)" variant="primary" :disabled="loading">
                                    {{ loading ? 'Đang lưu...' : 'Lưu' }}
                                </BaseButton>
                            </div>
                        </div>
                    </template>
                    <p v-else-if="review.comment" class="font-sans text-body-md text-on-surface-variant">
                        {{ review.comment }}
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-10">
            <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">reviews</span>
            <p class="font-sans text-body-md text-on-surface-variant">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        </div>
    </div>
</template>