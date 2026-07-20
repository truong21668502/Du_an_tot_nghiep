<!-- resources/js/Pages/Admin/Brands/Index.vue -->
<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

import BrandFormModal from "./Components/BrandFormModal.vue";

const props = defineProps({
    brands: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedBrand = ref(null);

// Bộ lọc tìm kiếm
const searchFilters = ref({
    search: props.filters.search || "",
});

watch(searchFilters, (newFilters) => {
    router.get("/quan-tri/thuong-hieu", newFilters, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

const openCreateModal = () => {
    isEditMode.value = false;
    selectedBrand.value = null;
    isModalOpen.value = true;
};

const openEditModal = (brand) => {
    isEditMode.value = true;
    selectedBrand.value = brand;
    isModalOpen.value = true;
};

const deleteBrand = (id, name) => {
    if (confirm(`Bạn có chắc chắn muốn xóa thương hiệu "${name}" không?`)) {
        router.delete(`/quan-tri/thuong-hieu/${id}`, {
            onSuccess: () => {
                // Flash từ Laravel tự xử lý thông báo thành công hoặc lỗi ràng buộc
            }
        });
    }
};

// Định dạng thời gian cho Tooltip
const formatDateTime = (dateStr) => {
    if (!dateStr) return "---";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${hours}:${minutes} — ${day}/${month}/${d.getFullYear()}`;
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative font-sans">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl">Thương hiệu sản phẩm</h1>
                    <p class="text-body-medium text-on-surface-variant">Quản lý và giám sát nguồn gốc các dòng sản phẩm của Nắng Coffee.</p>
                </div>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-md">add</span> Thêm thương hiệu
                </button>
            </div>

            <!-- Flash Session Messages -->
            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-error-container/20 border border-error/20 text-on-error-container rounded-xl text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-error">error</span>
                {{ $page.props.flash.error }}
            </div>

            <!-- Bộ lọc tìm kiếm nhanh -->
            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm text-body-medium">
                <div class="flex items-center gap-2 px-3 py-2 max-w-md rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                    <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                    <input v-model="searchFilters.search" type="text" placeholder="Tìm tên thương hiệu hoặc mô tả..." class="w-full bg-transparent focus:outline-none text-on-surface" />
                </div>
            </div>

            <!-- Bảng Dữ Liệu -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 text-label-large text-on-surface-variant">
                                <th class="p-4 w-20">Logo</th>
                                <th class="p-4 text-left">Tên thương hiệu</th>
                                <th class="p-4 text-left hidden md:table-cell">Mô tả chi tiết</th>
                                <th class="p-4 text-right w-32">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-for="brand in brands.data" :key="brand.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <!-- Logo preview -->
                                <td class="p-4 text-center">
                                    <img :src="brand.logo_url || 'https://placehold.co/80x80?text=No+Logo'" class="w-12 h-12 rounded-xl object-cover border border-outline-variant/30 mx-auto" alt="Logo" />
                                </td>

                                <!-- 📌 Tên thương hiệu + HOVER TOOLTIP Xem mốc thời gian kiểm toán -->
                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <div class="font-bold text-primary">{{ brand.brand_name }}</div>
                                        <div class="text-body-small text-outline font-mono">ID: #{{ brand.id }}</div>

                                        <!-- Hộp thoại logs logs nổi lên mượt mà khi hover -->
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all animate-fade-in">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                <span class="text-neutral-400">Ngày tạo:</span> 
                                                <span>{{ formatDateTime(brand.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                                <span class="text-neutral-400">Sửa cuối:</span> 
                                                <span>{{ formatDateTime(brand.updated_at) }}</span>
                                            </div>
                                            <div class="absolute top-full left-4 border-4 border-transparent border-t-neutral-900"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Mô tả -->
                                <td class="p-4 text-left hidden md:table-cell text-on-surface-variant truncate max-w-md" :title="brand.description">
                                    {{ brand.description || 'Chưa có thông tin mô tả chi tiết.' }}
                                </td>

                                <!-- Hành động -->
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(brand)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer" title="Chỉnh sửa">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteBrand(brand.id, brand.brand_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" title="Xóa">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="brands.data.length === 0">
                                <td colspan="4" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block font-light">verified_user</span>
                                    Chưa có dữ liệu thương hiệu nào phù hợp với bộ lọc.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <div v-if="brands.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-center gap-1">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in brands.links"
                        :key="index"
                        :href="link.url"
                        v-html="link.label"
                        :preserve-scroll="true" 
                        :class="[ 'px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' ]"
                    />
                </div>
            </div>

            <!-- Modal Form -->
            <BrandFormModal :isOpen="isModalOpen" :editMode="isEditMode" :brandData="selectedBrand" @close="isModalOpen = false" />
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.15s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(2px); } to { opacity: 1; transform: translateY(0); } }
</style>