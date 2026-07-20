<script setup>
// 1. Thêm import ref từ vue
import { ref } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

// 2. Import Component Modal vừa tạo
import PostFormModal from "./Components/PostFormModal.vue";

const props = defineProps({
    posts: { type: Object, required: true },
    // 3. Khai báo thêm categories để truyền vào form
    categories: { type: Array, required: true },
});

// Các state quản lý Modal
const isFormModalOpen = ref(false);
const isEditMode = ref(false);
const selectedPost = ref(null);

// Mở Modal Thêm mới
const openCreateModal = () => {
    isEditMode.value = false;
    selectedPost.value = null;
    isFormModalOpen.value = true;
};

// Mở Modal Chỉnh sửa
const openEditModal = (post) => {
    isEditMode.value = true;
    selectedPost.value = post;
    isFormModalOpen.value = true;
};

// Thao tác xóa bài viết
const deletePost = (id, title) => {
    if (confirm(`Bạn có chắc muốn xóa bài viết "${title}"?`)) {
        router.delete(`/quan-tri/bai-viet/${id}`);
    }
};

// Hàm định dạng ngày giờ thân thiện
const formatDateTime = (dateStr) => {
    if (!dateStr) return "---";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;

    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();

    return `${hours}:${minutes} — ${day}/${month}/${year}`;
};

// Hàm dịch trạng thái và style màu cho Badge
const getStatusData = (status) => {
    switch (status) {
        case 'PUBLISHED':
            return { label: 'Xuất bản', class: 'bg-primary-container text-on-primary-container' };
        case 'DRAFT':
            return { label: 'Bản nháp', class: 'bg-surface-container-high text-on-surface-variant' };
        case 'ARCHIVED':
            return { label: 'Lưu trữ', class: 'bg-error-container/30 text-error' };
        default:
            return { label: status, class: 'bg-surface-container text-on-surface' };
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl">Quản lý bài viết</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Danh sách tin tức và bài viết trên
                        Nắng Coffee.</p>
                </div>
                <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full cursor-pointer transition-colors">
                    <span class="material-symbols-outlined text-md">add</span>Viết bài mới
                </button>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-24 text-center">Ảnh</th>
                                <th class="p-4 text-left">Tiêu đề bài viết</th>
                                <th class="p-4 hidden lg:table-cell">Danh mục</th>
                                <th class="p-4 text-center">Trạng thái</th>
                                <th class="p-4 text-right w-36">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="post in posts.data" :key="post.id"
                                class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center">
                                    <img :src="post.thumbnail_url || 'https://placehold.co/150x100?text=No+Image'"
                                        class="w-20 h-12 rounded object-cover mx-auto border border-outline-variant/20" />
                                </td>

                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <div
                                            class="font-bold text-primary hover:text-primary-dark transition-colors line-clamp-1">
                                            {{ post.title }}
                                        </div>
                                        <div
                                            class="font-mono text-body-small text-on-surface-variant mt-0.5 line-clamp-1">
                                            {{ post.slug }}
                                        </div>

                                        <div
                                            class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all animate-fade-in">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                <span class="text-neutral-400 select-none">Tạo lúc:</span>
                                                <span>{{ formatDateTime(post.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                                <span class="text-neutral-400 select-none">Sửa cuối:</span>
                                                <span>{{ formatDateTime(post.updated_at) }}</span>
                                            </div>
                                            <div
                                                class="absolute top-full left-4 border-4 border-transparent border-t-neutral-900">
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 hidden lg:table-cell">
                                    <span class="px-3 py-1 bg-surface-container-high rounded-full text-body-small">
                                        {{ post.category?.name || 'Chưa phân loại' }}
                                    </span>
                                </td>

                                <td class="p-4 text-center">
                                    <span
                                        :class="['px-3 py-1 rounded-full text-label-medium font-bold', getStatusData(post.status).class]">
                                        {{ getStatusData(post.status).label }}
                                    </span>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <button @click="openEditModal(post)"
                                            class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors inline-block text-center cursor-pointer">
                                            <span class="material-symbols-outlined text-xl align-middle">edit</span>
                                        </button>
                                        <button @click="deletePost(post.id, post.title)"
                                            class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined text-xl align-middle">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!posts.data || posts.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-on-surface-variant">
                                    Chưa có bài viết nào. Hãy thêm bài viết mới!
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="posts.links && posts.data.length > 0"
                    class="flex items-left justify-center gap-1 mt-6 mb-3 font-sans">
                    <Component :is="link.url ? Link : 'span'" v-for="(link, index) in posts.links" :key="index"
                        :href="link.url" v-html="link.label" :preserve-scroll="true"
                        :class="['px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']" />
                </div>
            </div>

            <PostFormModal :isOpen="isFormModalOpen" :editMode="isEditMode" :post="selectedPost"
                :categories="categories" @close="isFormModalOpen = false" />

        </div>
    </AdminLayout>
</template>