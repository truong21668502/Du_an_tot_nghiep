<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    editMode: { type: Boolean, default: false },
    post: { type: Object, default: null },
    categories: { type: Array, required: true },
});

const emit = defineEmits(["close"]);

const form = useForm({
    title: "",
    slug: "",
    category_id: "",
    image: "", // Link URL ảnh
    summary: "",
    content: "",
    status: "DRAFT",
});

// Đồng bộ hóa data khi mở modal sửa bài viết
watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.editMode && props.post) {
            form.title = props.post.title || "";
            form.slug = props.post.slug || "";
            form.category_id = props.post.category_id || "";
            form.image = props.post.image || "";
            form.summary = props.post.summary || "";
            form.content = props.post.content || "";
            form.status = props.post.status || "DRAFT";
        } else {
            form.reset();
            form.clearErrors();
        }
    }
});

const submitForm = () => {
    const url = props.editMode
        ? `/quan-tri/bai-viet/${props.post.id}`
        : "/quan-tri/bai-viet";

    if (props.editMode) {
        form.put(url, {
            onSuccess: () => {
                emit("close");
                form.reset();
            },
        });
    } else {
        form.post(url, {
            onSuccess: () => {
                emit("close");
                form.reset();
            },
        });
    }
};

// Hàm hỗ trợ tự tạo Slug khi nhập tiêu đề (Option)
const generateSlug = () => {
    if (!form.editMode) {
        form.slug = form.title
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[đĐ]/g, "d")
            .replace(/([^0-9a-z-\s])/g, "")
            .replace(/(\s+)/g, "-")
            .replace(/-+/g, "-")
            .replace(/^-+|-+$/g, "");
    }
};
</script>

<template>
    <div v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
        <div
            class="bg-surface w-full max-w-4xl max-h-[90vh] rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden flex flex-col">

            <div class="flex items-center justify-between p-6 border-b border-outline-variant/20 flex-shrink-0">
                <h3 class="font-serif text-headline-small text-on-surface">
                    {{ editMode ? 'Chỉnh sửa nội dung bài viết' : 'Viết bài mới' }}
                </h3>
                <button @click="$emit('close')"
                    class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm"
                class="flex-1 overflow-y-auto p-6 space-y-6 font-sans text-body-medium custom-scrollbar">

                <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20 space-y-4">
                    <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">1. Thông tin chung</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Tiêu đề bài viết
                                *</label>
                            <input v-model="form.title" @input="generateSlug" type="text"
                                placeholder="Nhập tiêu đề hấp dẫn..."
                                class="px-4 py-2 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all" />
                            <span v-if="form.errors.title"
                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.title }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Đường dẫn sạch
                                (Slug)</label>
                            <input v-model="form.slug" type="text" placeholder="tieu-de-bai-viet"
                                class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-mono text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Danh mục bài viết
                                *</label>
                            <select v-model="form.category_id"
                                class="px-4 py-2 rounded-xl border border-outline-variant bg-surface outline-none">
                                <option value="" disabled>-- Chọn nhóm nội dung --</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <span v-if="form.errors.category_id"
                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.category_id
                                }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Trạng thái hiển
                                thị</label>
                            <select v-model="form.status"
                                class="px-4 py-2 rounded-xl border border-outline-variant bg-surface outline-none">
                                <option value="DRAFT">Bản nháp (Chưa hiện)</option>
                                <option value="PUBLISHED">Xuất bản (Công khai)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20 space-y-4">
                    <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">2. Hình ảnh & Tóm tắt
                    </h4>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-medium text-on-surface-variant font-bold">Link ảnh đại diện (Banner
                            URL)</label>
                        <input v-model="form.image" type="text" placeholder="https://..."
                            class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-mono text-sm" />
                        <div v-if="form.image" class="mt-2">
                            <img :src="form.image"
                                class="h-32 w-full object-cover rounded-xl border border-outline-variant/20 shadow-sm"
                                @error="$event.target.src = 'https://placehold.co/600x200?text=Lỗi+Link+Ảnh'" />
                        </div>
                    </div>

                </div>

                <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20 space-y-4">
                    <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">3. Nội dung bài viết
                    </h4>

                    <div class="flex flex-col gap-1">
                        <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden">
                            <QuillEditor v-model:content="form.content" contentType="html" theme="snow" toolbar="full"
                                placeholder="Bắt đầu viết nội dung chi tiết (hỗ trợ H1, H2, In đậm, Hình ảnh)..." />
                        </div>
                        <span v-if="form.errors.content"
                            class="text-body-small text-error flex items-center gap-0.5 mt-1">
                            <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.content }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20 flex-shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="px-6 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full transition-colors">
                        Hủy bỏ
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-8 py-2.5 bg-primary text-on-primary rounded-full shadow-md hover:bg-primary/90 transition-all font-bold disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined">save</span>
                        {{ editMode ? 'Cập nhật bài viết' : 'Đăng bài viết' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

/* Custom scrollbar cho nội dung dài */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e2e2;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cccccc;
}

/* Thêm đoạn này vào dưới cùng của thẻ <style scoped> */
:deep(.ql-toolbar.ql-snow) {
    border: none !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
    background-color: #f8fafc;
    /* Trắng xám nhẹ cho thanh công cụ */
    font-family: inherit;
}

:deep(.ql-container.ql-snow) {
    border: none !important;
    min-height: 350px;
    font-family: inherit;
    font-size: 1rem;
}

:deep(.ql-editor) {
    min-height: 350px;
}
</style>