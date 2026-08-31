<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    reviews: Object,
    filters: Object
});

const filterRating = ref(props.filters?.rating || '');
const filterStatus = ref(props.filters?.status || '');

const replyInputs = ref({});
const loadingAi = ref({});

// Lọc sao & trạng thái
watch([filterRating, filterStatus], () => {
    router.get('/quan-tri/danh-gia-san-pham', {
        rating: filterRating.value,
        status: filterStatus.value
    }, { preserveState: true, replace: true });
});

// Lấy gợi ý từ AI (Axios lấy text nhét vào input)
const getAiSuggestion = async (reviewId) => {
    if (!reviewId) return;
    loadingAi.value[reviewId] = true;
    try {
        const res = await axios.post(`/quan-tri/danh-gia-san-pham/${reviewId}/ai-reply`);
        replyInputs.value[reviewId] = res.data?.ai_reply || '';
    } catch (err) {
        alert("Không thể tạo câu trả lời AI!");
    } finally {
        loadingAi.value[reviewId] = false;
    }
};

// Gửi câu trả lời bằng Inertia Router -> Trigger Toast Backend
const submitReply = (reviewId) => {
    const comment = replyInputs.value[reviewId];
    if (!comment?.trim()) return;

    router.post(`/quan-tri/danh-gia-san-pham/${reviewId}/reply`, 
        { comment },
        {
            preserveScroll: true,
            onSuccess: () => {
                replyInputs.value[reviewId] = ''; // Xoá trắng ô nhập sau khi gửi thành công
            }
        }
    );
};

// Xoá câu trả lời bằng Inertia Router -> Trigger Toast Backend
const deleteReply = (replyId) => {
    if (!replyId || !confirm("Bạn có chắc chắn muốn xoá câu phản hồi này?")) return;

    router.delete(`/quan-tri/danh-gia-san-pham/reply/${replyId}`, {
        preserveScroll: true
    });
};

// Toggle Ẩn/Hiện bằng Inertia Router -> Trigger Toast Backend
const toggleVisibility = (reviewId) => {
    if (!reviewId) return;
    router.patch(`/quan-tri/danh-gia-san-pham/${reviewId}/toggle-visibility`, {}, {
        preserveScroll: true
    });
};
</script>

<template>
    <AdminLayout>
        <div class="p-6 bg-surface-container-low rounded-2xl space-y-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">star</span> QUẢN LÝ ĐÁNH GIÁ
                    </h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">
                        Danh sách đánh giá từ khách hàng.
                    </p>
                </div>

                <!-- Bộ lọc -->
                <div class="flex gap-3">
                    <select v-model="filterRating" class="px-3 py-2 rounded-xl border border-outline-variant text-base">
                        <option value="">Tất cả sao</option>
                        <option v-for="s in 5" :key="s" :value="s">{{ s }} sao</option>
                    </select>

                    <select v-model="filterStatus" class="px-3 py-2 rounded-xl border border-outline-variant text-base">
                        <option value="">Tất cả trạng thái</option>
                        <option value="visible">Đang hiển thị</option>
                        <option value="hidden">Đã ẩn</option>
                    </select>
                </div>
            </div>

            <!-- Danh sách Đánh giá -->
            <div v-if="!reviews?.data || reviews.data.length === 0" class="text-center py-8 text-outline">
                Chưa có đánh giá nào.
            </div>

            <div v-else class="space-y-4">
                <template v-for="item in reviews.data" :key="item?.id">
                    <div 
                        v-if="item && item.id"
                        :class="['p-4 rounded-xl border transition-all', item.is_visible ? 'bg-surface border-outline-variant/30' : 'bg-surface-variant/20 border-error/30']"
                    >
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-on-surface">{{ item.user?.full_name || 'Khách vãng lai' }}</span>
                                    <span class="text-amber-500 font-bold">★ {{ item.rating }}/5</span>
                                    <span v-if="item.product" class="text-base bg-secondary-container px-2 py-0.5 rounded-full text-on-secondary-container">
                                        {{ item.product.product_name }}
                                    </span>
                                </div>
                                <p class="text-base text-on-surface-variant">{{ item.comment }}</p>
                            </div>

                            <button 
                                @click="toggleVisibility(item.id)" 
                                :class="['px-3 py-1 text-base rounded-lg font-bold', item.is_visible ? 'bg-outline-variant/20 text-on-surface' : 'bg-error/10 text-error']"
                            >
                                {{ item.is_visible ? '👁️ Đang hiện' : '🙈 Đã ẩn' }}
                            </button>
                        </div>

                        <!-- Danh sách Phản hồi -->
                        <div v-if="item.replies && item.replies.length > 0" class="mt-3 pl-4 border-l-2 border-primary space-y-2">
                            <template v-for="(rep, idx) in item.replies" :key="rep?.id || idx">
                                <div v-if="rep" class="text-base bg-surface-container-high p-2 rounded-lg flex justify-between items-center group">
                                    <div>
                                        <span class="font-bold text-primary">{{ rep.user?.full_name || 'Quản lý' }} (Bộ phận CSKH):</span>
                                        <span class="text-on-surface ml-1">{{ rep.comment }}</span>
                                    </div>
                                    <button 
                                        v-if="rep.id"
                                        @click="deleteReply(rep.id)" 
                                        title="Xoá câu phản hồi"
                                        class="text-error p-1 hover:bg-error/10 rounded"
                                    >
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Ô nhập Phản hồi & Nút AI -->
                        <div class="mt-3 flex gap-2">
                            <textarea 
                                v-model="replyInputs[item.id]" 
                                placeholder="Nhập nội dung phản hồi..." 
                                class="flex-1 px-3 py-1.5 border border-outline-variant/40 rounded-lg text-base resize-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            />
                            <button 
                                @click="getAiSuggestion(item.id)" 
                                :disabled="loadingAi[item.id]"
                                class="px-3 py-1.5 bg-tertiary text-on-tertiary text-base font-bold rounded-lg hover:opacity-90 disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>✨ {{ loadingAi[item.id] ? 'Đang tạo...' : 'AI Gợi Ý' }}</span>
                            </button>
                            <button 
                                @click="submitReply(item.id)" 
                                class="px-3 py-1.5 bg-primary text-on-primary text-base font-bold rounded-lg"
                            >
                                Gửi
                            </button>
                        </div>
                    </div>
                </template>
                <!-- Phân trang -->
                <div v-if="reviews?.links && reviews.links.length > 0" class="mt-4">
                    <nav class="flex justify-center items-center gap-2 flex-wrap">
                        <template v-for="(link, idx) in reviews.links" :key="idx">
                            <button 
                                v-if="link.url"
                                @click.prevent="router.get(link.url, {}, { preserveState: true })"
                                :class="['px-3 py-1 rounded-lg text-base cursor-pointer', link.active ? 'bg-primary text-on-primary font-bold' : 'bg-surface border border-outline-variant/30 text-on-surface hover:bg-surface-variant']"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 rounded-lg text-base bg-surface border border-outline-variant/30 text-on-surface" v-html="link.label"></span>
                        </template>
                    </nav>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>