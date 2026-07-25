<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/BaseButton.vue'
import axios from 'axios'

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

const canReply = computed(() => {
    if (!user.value) return false
    return ['ADMIN', 'STAFF', 'BARISTA'].includes(user.value.role)
})

const isAdmin = computed(() => user.value?.role === 'ADMIN')

// ── Review state ────────────────────────────────────────────────────────────
const showForm    = ref(false)
const form        = ref({ rating: 5, comment: '' })
const loading     = ref(false)
const editingId   = ref(null)
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

// ── Sửa review ──────────────────────────────────────────────────────────────
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

// ── Xóa review ──────────────────────────────────────────────────────────────
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

// ── Reply state ─────────────────────────────────────────────────────────────
const replyingTo       = ref(null)
const replyContent     = ref('')
const replyLoading     = ref(false)
const replyError       = ref('')

const editingReplyId   = ref(null)
const editReplyContent = ref('')
const editReplyLoading = ref(false)
const editReplyError   = ref('')

// Kiểm tra user đã reply review này chưa
const hasReplied = (review) => {
    if (!canReply.value || !review.replies) return false
    return review.replies.some(r => r.user?.id === user.value?.id)
}

// ── Submit reply mới ────────────────────────────────────────────────────────
const handleSubmitReply = async (reviewId) => {
    if (!replyContent.value.trim()) return
    
    replyLoading.value = true
    replyError.value = ''
    
    try {
        const response = await axios.post(`/reviews/${reviewId}/replies`, {
            comment: replyContent.value
        })
        
        if (response.data.success) {
            const review = localReviews.value.find(r => r.id === reviewId)
            if (review) {
                if (!review.replies) review.replies = []
                review.replies.push(response.data.data)
            }
            replyContent.value = ''
            replyingTo.value = null
        }
    } catch (error) {
        if (error.response?.data?.message) {
            replyError.value = error.response.data.message
        } else {
            replyError.value = 'Có lỗi xảy ra, vui lòng thử lại.'
        }
    } finally {
        replyLoading.value = false
    }
}

// ── Sửa reply ───────────────────────────────────────────────────────────────
const startEditReply = (reply) => {
    editingReplyId.value = reply.id
    editReplyContent.value = reply.comment
    editReplyError.value = ''
}

const cancelEditReply = () => {
    editingReplyId.value = null
    editReplyContent.value = ''
    editReplyError.value = ''
}

const handleUpdateReply = async (replyId, reviewId) => {
    if (!editReplyContent.value.trim()) return
    
    editReplyLoading.value = true
    editReplyError.value = ''
    
    try {
        const response = await axios.patch(`/replies/${replyId}`, {
            comment: editReplyContent.value
        })
        
        if (response.data.success) {
            const review = localReviews.value.find(r => r.id === reviewId)
            if (review && review.replies) {
                const replyIndex = review.replies.findIndex(r => r.id === replyId)
                if (replyIndex !== -1) {
                    review.replies[replyIndex] = {
                        ...review.replies[replyIndex],
                        ...response.data.data
                    }
                }
            }
            cancelEditReply()
        }
    } catch (error) {
        if (error.response?.data?.message) {
            editReplyError.value = error.response.data.message
        } else {
            editReplyError.value = 'Có lỗi xảy ra, vui lòng thử lại.'
        }
    } finally {
        editReplyLoading.value = false
    }
}

// ── Xóa reply ───────────────────────────────────────────────────────────────
const handleDeleteReply = async (replyId, reviewId) => {
    if (!confirm('Bạn có chắc muốn xóa phản hồi này?')) return
    
    try {
        const response = await axios.delete(`/replies/${replyId}`)
        
        if (response.data.success) {
            const review = localReviews.value.find(r => r.id === reviewId)
            if (review && review.replies) {
                review.replies = review.replies.filter(r => r.id !== replyId)
            }
        }
    } catch (error) {
        // Toast error
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
<!-- ── Replies Section ────────────────────────────────────── -->
<div v-if="review.replies && review.replies.length > 0" class="mt-3 space-y-2">
    <div v-for="reply in review.replies" :key="reply.id"
        class="bg-primary-container/10 rounded-lg p-3 ml-4 border-l-2 border-primary/30">
        
        <div class="flex items-start gap-2">
            <div class="w-8 h-8 rounded-full bg-primary/10 flex-shrink-0 flex items-center justify-center overflow-hidden">
                <img v-if="reply.user?.avatar" :src="reply.user.avatar" class="w-full h-full object-cover" />
                <span v-else class="material-symbols-outlined text-primary text-sm">support_agent</span>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-sans text-label-sm font-semibold text-primary">
                            {{ reply.user?.name || 'Nhân viên' }}
                        </span>
                        <span class="bg-primary/10 text-primary text-label-xs px-2 py-0.5 rounded-full">
                            {{ reply.user?.role === 'ADMIN' ? 'Quản lý' : 'Nhân viên' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-sans text-label-xs text-on-surface-variant">
                            {{ formatDate(reply.created_at) }}
                        </span>
                        <!-- Nút sửa: chỉ hiện khi là chủ sở hữu reply -->
                        <button 
                            v-if="user && reply.user?.id === user.id"
                            @click="startEditReply(reply)"
                            class="material-symbols-outlined text-sm text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                            title="Chỉnh sửa"
                        >
                            edit
                        </button>
                        <!-- Nút xóa: chỉ admin -->
                        <button 
                            v-if="isAdmin"
                            @click="handleDeleteReply(reply.id, review.id)"
                            class="material-symbols-outlined text-sm text-on-surface-variant hover:text-error transition-colors cursor-pointer"
                            title="Xóa phản hồi"
                        >
                            delete
                        </button>
                    </div>
                </div>
                
                <!-- Nội dung reply hoặc form sửa -->
                <template v-if="editingReplyId === reply.id">
                    <div class="space-y-2 mt-1">
                        <textarea 
                            v-model="editReplyContent"
                            rows="2"
                            class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-lg font-sans text-body-sm focus:outline-none focus:border-primary resize-none"
                        ></textarea>
                        
                        <p v-if="editReplyError" class="text-label-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">error</span>
                            {{ editReplyError }}
                        </p>
                        
                        <div class="flex gap-2 justify-end">
                            <button 
                                @click="cancelEditReply"
                                class="px-3 py-1.5 text-label-xs border border-outline-variant/30 rounded-full text-on-surface-variant hover:bg-surface-container-low transition-colors cursor-pointer"
                            >
                                Hủy
                            </button>
                            <button 
                                @click="handleUpdateReply(reply.id, review.id)"
                                :disabled="editReplyLoading || !editReplyContent.trim()"
                                class="px-3 py-1.5 text-label-xs bg-primary text-on-primary rounded-full hover:bg-primary/90 transition-colors disabled:opacity-50 cursor-pointer"
                            >
                                {{ editReplyLoading ? 'Đang lưu...' : 'Lưu' }}
                            </button>
                        </div>
                    </div>
                </template>
                <p v-else class="font-sans text-body-sm text-on-surface-variant mt-1">
                    {{ reply.comment }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ── Reply Button ────────────────────────────────────── -->
<!-- ── Reply Button & Form Section ────────────────────────────────────── -->
<div v-if="canReply" class="mt-3 ml-4">
    <!-- 1. Nút "Trả lời": Chỉ hiện khi CHƯA trả lời VÀ ĐANG KHÔNG mở form của review này -->
    <button 
        v-if="!hasReplied(review) && replyingTo !== review.id"
        @click="replyingTo = review.id; replyContent = ''; replyError = ''"
        class="ml-auto flex items-center gap-1 text-primary hover:text-primary/80 font-sans text-label-sm transition-colors cursor-pointer"
    >
        <span class="material-symbols-outlined text-sm">reply</span>
        Trả lời
    </button>

    <!-- 2. Form Trả lời: Chỉ hiện khi người dùng BẤM nút "Trả lời" (replyingTo === review.id) -->
    <div v-if="replyingTo === review.id" class="space-y-2">
        <textarea 
            v-model="replyContent"
            rows="2"
            placeholder="Nhập phản hồi của bạn..."
            class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-lg font-sans text-body-sm focus:outline-none focus:border-primary resize-none"
        ></textarea>
        
        <p v-if="replyError" class="text-label-xs text-error flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">error</span>
            {{ replyError }}
        </p>
        
        <div class="flex gap-2 justify-end">
            <button 
                @click="replyingTo = null; replyError = ''"
                class="px-3 py-1.5 text-label-xs border border-outline-variant/30 rounded-full text-on-surface-variant hover:bg-surface-container-low transition-colors cursor-pointer"
            >
                Hủy
            </button>
            <button 
                @click="handleSubmitReply(review.id)"
                :disabled="replyLoading || !replyContent.trim()"
                class="px-3 py-1.5 text-label-xs bg-primary text-on-primary rounded-full hover:bg-primary/90 transition-colors disabled:opacity-50 cursor-pointer flex items-center gap-1"
            >
                <span v-if="replyLoading" class="material-symbols-outlined text-sm animate-spin">sync</span>
                {{ replyLoading ? 'Đang gửi...' : 'Gửi' }}
            </button>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-10">
            <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">reviews</span>
            <p class="font-sans text-body-md text-on-surface-variant">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        </div>
    </div>
</template>
<style scoped>
/* Thêm vào cuối file style hiện tại */
.animate-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>