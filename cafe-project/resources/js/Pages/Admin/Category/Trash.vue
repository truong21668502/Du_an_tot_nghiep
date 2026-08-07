<script setup>
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import { toast } from 'vue3-toastify';

defineProps({
    categories: {
        type: Object,
        required: true,
    },
});

// Hàm Khôi Phục
const restoreCategory = (id, name) => {
    if (confirm(`Bạn có chắc chắn muốn khôi phục danh mục "${name}" không?`)) {
        router.post(`/quan-tri/danh-muc/${id}/restore`, {}, {
        });
    }
};

// Hàm Xóa Vĩnh Viễn
const forceDeleteCategory = (id, name) => {
    if (confirm(`CẢNH BÁO: Dữ liệu sẽ bị xóa hoàn toàn khỏi hệ thống!\n\nBạn có chắc chắn muốn XÓA VĨNH VIỄN danh mục "${name}" không?`)) {
        router.delete(`/quan-tri/danh-muc/${id}/force-delete`, {
        });
    }
};

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
                    <h1 class="font-sans text-headline-md text-error text-3xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-error">delete_sweep</span> 
                        THÙNG RÁC - DANH MỤC SẢN PHẨM
                    </h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Quản lý danh sách các danh mục đã bị xóa tạm thời.</p>
                </div>
                
                <Link
                    href="/quan-tri/danh-muc"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center cursor-pointer"
                >
                    <span class="material-symbols-outlined text-md">arrow_back</span>
                    Quay lại danh sách
                </Link>
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
                                <th class="p-4 hidden md:table-cell">Thời gian xóa</th>
                                <th class="p-4 text-right w-40">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center text-outline font-mono text-body-small">{{ category.id }}</td>
                                <td class="p-4 font-bold text-on-surface">{{ category.category_name }}</td>
                                <td class="p-4 font-mono text-body-small text-on-surface-variant">{{ category.slug }}</td>
                                <td class="p-4 hidden lg:table-cell text-on-surface-variant truncate max-w-xs">
                                    {{ category.description || 'Chưa có mô tả' }}
                                </td>
                                
                                <td class="p-4 hidden md:table-cell text-error font-mono text-body-small select-none">
                                    {{ formatDateTime(category.deleted_at) }}
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Khôi phục -->
                                        <button 
                                            @click="restoreCategory(category.id, category.category_name)" 
                                            class="p-2 hover:bg-primary-container/30 text-primary rounded-full transition-colors cursor-pointer" 
                                            title="Khôi phục danh mục"
                                        >
                                            <span class="material-symbols-outlined text-xl">restore_from_trash</span>
                                        </button>

                                        <!-- Xóa vĩnh viễn -->
                                        <button 
                                            @click="forceDeleteCategory(category.id, category.category_name)" 
                                            class="p-2 hover:bg-error-container/20 text-error rounded-full transition-colors cursor-pointer" 
                                            title="Xóa vĩnh viễn"
                                        >
                                            <span class="material-symbols-outlined text-xl">delete_forever</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">cleaning_services</span>
                                    Thùng rác trống! không có danh mục nào bị xóa.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="categories.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-between font-sans text-label-large">
                    <div class="text-body-small text-on-surface-variant hidden sm:block">
                        Hiển thị từ {{ categories.from || 0 }} đến {{ categories.to || 0 }} trong tổng số {{ categories.total }} danh mục đã bị xóa
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
        </div>
    </AdminLayout>
</template>