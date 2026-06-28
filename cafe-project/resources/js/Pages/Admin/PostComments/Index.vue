<script setup>
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({ comments: Object });

const updateStatus = (id, newStatus) => {
    router.put(`/quan-tri/binh-luan/${id}`, { status: newStatus }, { preserveScroll: true });
};

const deleteComment = (id) => {
    if (confirm("Bạn có chắc muốn xóa vĩnh viễn bình luận này?")) {
        router.delete(`/quan-tri/binh-luan/${id}`, { preserveScroll: true });
    }
};

const getStatusBadge = (status) => {
    if (status === 'APPROVED') return 'bg-primary-container text-on-primary-container';
    if (status === 'HIDDEN') return 'bg-error-container/30 text-error';
    return 'bg-surface-container-high text-on-surface-variant'; // PENDING
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Kiểm duyệt bình luận</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Quản lý phản hồi của khách hàng trên
                        bài viết.</p>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-48">Người dùng</th>
                                <th class="p-4">Nội dung bình luận</th>
                                <th class="p-4 text-center">Trạng thái</th>
                                <th class="p-4 text-right">Duyệt / Xóa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="comment in comments.data" :key="comment.id"
                                class="hover:bg-surface-container-low/50">
                                <td class="p-4">
                                    <div class="font-bold">{{ comment.user?.name || 'Khách' }}</div>
                                    <div class="text-body-small text-on-surface-variant mt-0.5">{{ comment.created_at ?
                                        new Date(comment.created_at).toLocaleDateString() : '' }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="text-body-small text-primary font-bold mb-1">
                                        Bình luận tại: {{ comment.post?.title || 'Bài viết đã xóa' }}
                                    </div>
                                    <div class="flex items-center gap-1 mb-1 text-orange-400 text-sm">
                                        <span v-for="n in 5" :key="n" class="material-symbols-outlined text-[16px]">{{ n
                                            <= comment.rating ? 'star' : 'star_outline' }}</span>
                                    </div>
                                    <p class="text-on-surface-variant">{{ comment.content }}</p>
                                </td>
                                <td class="p-4 text-center">
                                    <span
                                        :class="['px-3 py-1 rounded-full text-label-medium font-bold', getStatusBadge(comment.status)]">
                                        {{ comment.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button v-if="comment.status !== 'APPROVED'"
                                            @click="updateStatus(comment.id, 'APPROVED')"
                                            class="p-2 hover:bg-primary-container/30 text-on-surface-variant hover:text-primary rounded-full transition-colors"
                                            title="Duyệt hiện">
                                            <span class="material-symbols-outlined text-xl">check_circle</span>
                                        </button>
                                        <button v-if="comment.status !== 'HIDDEN'"
                                            @click="updateStatus(comment.id, 'HIDDEN')"
                                            class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-orange-500 rounded-full transition-colors"
                                            title="Ẩn bình luận">
                                            <span class="material-symbols-outlined text-xl">visibility_off</span>
                                        </button>
                                        <button @click="deleteComment(comment.id)"
                                            class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors"
                                            title="Xóa vĩnh viễn">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!comments.data || comments.data.length === 0">
                                <td colspan="4" class="p-8 text-center text-on-surface-variant">Chưa có bình luận nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="comments.links" class="flex justify-center gap-1 mt-6 font-sans">
                <Component :is="link.url ? Link : 'span'" v-for="(link, index) in comments.links" :key="index"
                    :href="link.url" v-html="link.label"
                    :class="['px-3 py-1.5 text-label-medium rounded-lg', link.active ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant', !link.url ? 'opacity-40' : 'cursor-pointer']" />
            </div>
        </div>
    </AdminLayout>
</template>