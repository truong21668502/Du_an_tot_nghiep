<script setup>
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

defineProps({
    products: {
        type: Object,
        required: true,
    },
});

// Hàm Khôi Phục Sản Phẩm
const restoreProduct = (id, name) => {
    if (confirm(`Bạn có chắc chắn muốn khôi phục sản phẩm "${name}" không?`)) {
        router.post(`/quan-tri/san-pham/${id}/restore`);
    }
};

// Hàm Xóa Vĩnh Viễn Sản Phẩm
const forceDeleteProduct = (id, name) => {
    if (confirm(`CẢNH BÁO: Dữ liệu sản phẩm sẽ bị xóa hoàn toàn khỏi hệ thống!\n\nBạn có chắc chắn muốn XÓA VĨNH VIỄN "${name}" không?`)) {
        router.delete(`/quan-tri/san-pham/${id}/force-delete`);
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
                        THÙNG RÁC - SẢN PHẨM
                    </h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Quản lý các sản phẩm đồ uống đã bị xóa tạm thời.</p>
                </div>
                
                <Link
                    href="/quan-tri/san-pham"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center cursor-pointer"
                >
                    <span class="material-symbols-outlined text-md">arrow_back</span>
                    Quay lại danh sách
                </Link>
            </div>

            <!-- Flash notification -->
            <div v-if="$page.props.flash?.['toast-success']" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash['toast-success'] }}
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">Ảnh</th>
                                <th class="p-4">Tên sản phẩm</th>
                                <th class="p-4 hidden lg:table-cell">Danh mục</th>
                                <th class="p-4 hidden md:table-cell">Thời gian xóa</th>
                                <th class="p-4 text-right w-36">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center">
                                    <img :src="product.image_url || 'https://placehold.co/100x100?text=No+Image'" class="w-12 h-12 rounded-lg object-cover mx-auto border border-outline-variant/20" />
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-on-surface">{{ product.product_name }}</div>
                                    <div class="font-mono text-body-small text-on-surface-variant">{{ product.slug }}</div>
                                </td>
                                <td class="p-4 hidden lg:table-cell">
                                    <span class="px-3 py-1 bg-surface-container-high rounded-full text-body-small">
                                        {{ product.category?.category_name || 'Khác' }}
                                    </span>
                                </td>
                                
                                <td class="p-4 hidden md:table-cell text-error font-mono text-body-small select-none">
                                    {{ formatDateTime(product.deleted_at) }}
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Nút Khôi phục -->
                                        <button 
                                            @click="restoreProduct(product.id, product.product_name)" 
                                            class="p-2 hover:bg-primary-container/30 text-primary rounded-full transition-colors cursor-pointer" 
                                            title="Khôi phục"
                                        >
                                            <span class="material-symbols-outlined text-xl">restore_from_trash</span>
                                        </button>

                                        <!-- Nút Xóa vĩnh viễn -->
                                        <button 
                                            @click="forceDeleteProduct(product.id, product.product_name)" 
                                            class="p-2 hover:bg-error-container/20 text-error rounded-full transition-colors cursor-pointer" 
                                            title="Xóa vĩnh viễn"
                                        >
                                            <span class="material-symbols-outlined text-xl">delete_forever</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">cleaning_services</span>
                                    Thùng rác trống! Không có sản phẩm nào bị xóa.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-center gap-1 mt-6 mb-3 font-sans">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in products.links"
                        :key="index"
                        :href="link.url"
                        v-html="link.label"
                        :preserve-scroll="true" 
                        :class="[ 'px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' ]"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>