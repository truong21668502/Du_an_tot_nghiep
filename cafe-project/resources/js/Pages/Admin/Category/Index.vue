<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import { toast } from 'vue3-toastify';

// Import component form đã tách
import CategoryFormModal from "./Components/CategoryFormModal.vue";

defineProps({
    categories: {
        type: Object,
        required: true,
    },
});

// Các state kiểm soát Modal con
const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedCategory = ref(null);

const openCreateModal = () => {
    isEditMode.value = false;
    selectedCategory.value = null;
    isModalOpen.value = true;
};

const openEditModal = (category) => {
    isEditMode.value = true;
    selectedCategory.value = category;
    isModalOpen.value = true;
};

const deleteCategory = (id, name) => {
    if (confirm(`Bạn có chắc chắn muốn xóa danh mục "${name}" không?`)) {
        router.delete(`/quan-tri/danh-muc/${id}`, {
            onSuccess: () => toast.success('Xoá danh mục thành công !')
        });
    }
};

// Hàm định dạng ngày giờ thân thiện để Admin dễ giám sát
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
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Danh mục sản phẩm</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Quản lý và giám sát các nhóm thực đơn của Nắng Coffee.</p>
                </div>
                
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center cursor-pointer"
                >
                    <span class="material-symbols-outlined text-md">add</span>
                    Thêm danh mục
                </button>
            </div>

            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-error-container/20 border border-error/20 text-on-error-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-error">error</span>
                {{ $page.props.flash.error }}
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">ID</th>
                                <th class="p-4">Tên danh mục</th>
                                <th class="p-4">Slug</th>
                                <th class="p-4 hidden lg:table-cell">Mô tả</th>
                                <th class="p-4 hidden md:table-cell">Ngày khởi tạo</th>
                                <th class="p-4 hidden md:table-cell">Cập nhật cuối</th>
                                <th class="p-4 text-right w-32">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center text-outline font-mono text-body-small">{{ category.id }}</td>
                                <td class="p-4 font-bold text-primary">{{ category.category_name }}</td>
                                <td class="p-4 font-mono text-body-small text-on-surface-variant">{{ category.slug }}</td>
                                <td class="p-4 hidden lg:table-cell text-on-surface-variant truncate max-w-xs">
                                    {{ category.description || 'Chưa có mô tả' }}
                                </td>
                                
                                <td class="p-4 hidden md:table-cell text-on-surface-variant font-mono text-body-small select-none">
                                    {{ formatDateTime(category.created_at) }}
                                </td>
                                <td class="p-4 hidden md:table-cell text-primary font-mono text-body-small select-none">
                                    {{ formatDateTime(category.updated_at) }}
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(category)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer" title="Chỉnh sửa">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteCategory(category.id, category.category_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" title="Xóa">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">folder_open</span>
                                    Chưa có danh mục sản phẩm nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="categories.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-between font-sans text-label-large">
                    <div class="text-body-small text-on-surface-variant hidden sm:block">
                        Hiển thị từ {{ categories.from || 0 }} đến {{ categories.to || 0 }} trong tổng số {{ categories.total }} danh mục
                    </div>
                    <div class="flex gap-1 ml-auto sm:ml-0">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, index) in categories.links"
                            :key="index"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 text-center min-w-9 rounded-lg transition-all duration-200 select-none',
                                link.active ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-low',
                                !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                            ]"
                        />
                    </div>
                </div>
            </div>

            <CategoryFormModal 
                :isOpen="isModalOpen" 
                :editMode="isEditMode" 
                :category="selectedCategory" 
                @close="isModalOpen = false" 
            />

        </div>
    </AdminLayout>
</template>