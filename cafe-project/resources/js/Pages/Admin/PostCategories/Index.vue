<script setup>
import { ref, watch } from "vue";
import { router, useForm, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({ categories: Object });

// Modal State
const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedId = ref(null);

const form = useForm({
    name: '',
    slug: '',
    description: ''
});

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (category) => {
    isEditMode.value = true;
    selectedId.value = category.id;
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description || '';
    form.clearErrors();
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditMode.value) {
        form.put(`/quan-tri/danh-muc-bai-viet/${selectedId.value}`, {
            onSuccess: () => isModalOpen.value = false
        });
    } else {
        form.post(`/quan-tri/danh-muc-bai-viet`, {
            onSuccess: () => isModalOpen.value = false
        });
    }
};

const deleteCategory = (id, name, postsCount) => {
    if (postsCount > 0) {
        alert(`Không thể xóa! Danh mục "${name}" đang có ${postsCount} bài viết.`);
        return;
    }
    if (confirm(`Bạn có chắc muốn xóa danh mục "${name}"?`)) {
        router.delete(`/quan-tri/danh-muc-bai-viet/${id}`);
    }
};

const generateSlug = () => {
    if (!isEditMode.value) {
        form.slug = form.name.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[đĐ]/g, "d").replace(/([^0-9a-z-\s])/g, "").replace(/(\s+)/g, "-").replace(/-+/g, "-").replace(/^-+|-+$/g, "");
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl">Danh mục bài viết</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Phân loại nội dung tin tức, blog.</p>
                </div>
                <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-md">add</span>Thêm danh mục
                </button>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-12 text-center">ID</th>
                                <th class="p-4">Tên danh mục</th>
                                <th class="p-4 hidden md:table-cell">Mô tả</th>
                                <th class="p-4 text-center">Số bài viết</th>
                                <th class="p-4 text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-surface-container-low/50">
                                <td class="p-4 text-center text-on-surface-variant font-mono">{{ cat.id }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-primary">{{ cat.name }}</div>
                                    <div class="font-mono text-body-small text-on-surface-variant mt-0.5">{{ cat.slug }}
                                    </div>
                                </td>
                                <td
                                    class="p-4 hidden md:table-cell text-on-surface-variant line-clamp-2 mt-2 border-none">
                                    {{ cat.description || '---' }}</td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 bg-surface-container-high rounded-full font-bold">{{
                                        cat.posts_count }}</span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <button @click="openEditModal(cat)"
                                            class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full"><span
                                                class="material-symbols-outlined text-xl">edit</span></button>
                                        <button @click="deleteCategory(cat.id, cat.name, cat.posts_count)"
                                            class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full"><span
                                                class="material-symbols-outlined text-xl">delete</span></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="categories.links" class="flex items-left justify-center gap-1 mt-6 font-sans">
                <Component :is="link.url ? Link : 'span'" v-for="(link, index) in categories.links" :key="index"
                    :href="link.url" v-html="link.label"
                    :class="['px-3 py-1.5 text-label-medium rounded-lg', link.active ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant', !link.url ? 'opacity-40' : 'cursor-pointer']" />
            </div>

            <div v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                <div class="bg-surface w-full max-w-lg rounded-2xl shadow-xl flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between p-6 border-b border-outline-variant/20">
                        <h3 class="font-serif text-headline-small">{{ isEditMode ? 'Sửa danh mục' : 'Thêm danh mục' }}
                        </h3>
                        <button @click="isModalOpen = false"
                            class="p-1 hover:bg-surface-container-high rounded-full"><span
                                class="material-symbols-outlined">close</span></button>
                    </div>
                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-label-medium font-bold mb-1">Tên danh mục *</label>
                            <input v-model="form.name" @input="generateSlug" type="text"
                                class="w-full px-4 py-2 rounded-xl border border-outline-variant bg-surface" required />
                        </div>
                        <div>
                            <label class="block text-label-medium font-bold mb-1">Slug</label>
                            <input v-model="form.slug" type="text"
                                class="w-full px-4 py-2 rounded-xl border border-outline-variant bg-surface font-mono" />
                        </div>
                        <div>
                            <label class="block text-label-medium font-bold mb-1">Mô tả</label>
                            <textarea v-model="form.description" rows="3"
                                class="w-full px-4 py-2 rounded-xl border border-outline-variant bg-surface"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/20">
                            <button type="button" @click="isModalOpen = false"
                                class="px-5 py-2.5 rounded-full hover:bg-surface-container-high">Hủy</button>
                            <button type="submit" :disabled="form.processing"
                                class="px-5 py-2.5 bg-primary text-on-primary rounded-full">Lưu lại</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>