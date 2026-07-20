<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

defineProps({
    counters: Object,
    revenue: Number,
    totalOrders: Number,
    lowStockIngredients: Array,
    topSellingProducts: Array,
    topLikedProducts: Array,
    topPosts: Array
});

// Hàm format tiền tệ Việt Nam
const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};
</script>

<template>

<AdminLayout>
    <Head title="Trang chủ Quản trị | Nắng Coffee" />

    <div class="p-6 space-y-6 bg-surface min-h-screen text-on-surface font-sans">
        
        <!-- HEADER TRANG -->
        <div class="flex flex-col gap-1">
            <h1 class="font-sans font-bold text-primary text-3xl">Tổng quan vận hành</h1>
            <p class="text-body-medium text-outline">Dữ liệu phân tích hoạt động kinh doanh thực tế tại Nắng Coffee.</p>
        </div>

        <hr class="border-outline-variant/30" />

        <!-- TẦNG 1: THẺ SỐ LIỆU DOANH THU & ĐƠN HÀNG NHANH -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Thẻ Doanh Thu -->
            <div class="bg-primary-container text-on-primary-container p-5 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="space-y-1">
                    <span class="text-label-medium font-bold uppercase tracking-wider opacity-80">Doanh thu tháng này</span>
                    <h2 class="text-headline-medium font-bold font-serif">{{ formatCurrency(revenue) }}</h2>
                </div>
                <span class="material-symbols-outlined text-display-small">payments</span>
            </div>

            <!-- Thẻ Tổng Đơn Hàng -->
            <div class="bg-secondary-container text-on-secondary-container p-5 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="space-y-1">
                    <span class="text-label-medium font-bold uppercase tracking-wider opacity-80">Đơn hàng hoàn tất</span>
                    <h2 class="text-headline-medium font-bold font-serif">{{ totalOrders }} đơn</h2>
                </div>
                <span class="material-symbols-outlined text-display-small">local_cafe</span>
            </div>

            <!-- Thẻ Tổng Sản Phẩm Hệ Thống -->
            <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Tổng sản phẩm</span>
                    <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.products.total }}</h2>
                    <p class="text-body-small text-outline">{{ counters.products.active }} món đang bán</p>
                </div>
                <span class="material-symbols-outlined text-display-small text-primary">restaurant_menu</span>
            </div>

            <!-- Thẻ Tổng Khách Hàng -->
            <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Thành viên (Khách)</span>
                    <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.users.customers }}</h2>
                    <p class="text-body-small text-outline">Hệ thống có {{ counters.users.staff }} nhân viên</p>
                </div>
                <span class="material-symbols-outlined text-display-small text-primary">group</span>
            </div>
        </div>

        <!-- TẦNG 2: BÁO CÁO KHO NGUYÊN LIỆU & SẢN PHẨM -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Cột 1: Cảnh báo nguyên liệu sắp hết -->
            <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
                <div class="flex justify-between gap-2 text-error">
                    <span class="material-symbols-outlined">warning</span>
                    <h3 class="font-serif text-title-large font-bold">Cảnh báo hết kho</h3>
                    <h3 class="font-serif text-title-large font-bold">Còn</h3>
                </div>
                <div class="divide-y divide-outline-variant/20">
                    <div v-for="ing in lowStockIngredients" :key="ing.material_name" class="py-3 flex justify-between items-center">
                        <span class="font-medium text-on-surface">{{ ing.material_name }}</span>
                        <span class="px-2.5 py-1 bg-error-container text-on-error-container text-label-small font-bold rounded-full">
                            {{ ing.quantity_in_stock }} {{ ing.base_unit }}
                        </span>
                    </div>
                    <div v-if="lowStockIngredients.length === 0" class="py-4 text-center text-outline text-body-medium">
                        Kho nguyên liệu hiện tại rất an toàn!
                    </div>
                </div>
            </div>

            <!-- Cột 2: Top Sản phẩm Bán Chạy Nhất -->
            <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-primary">
                    <span class="material-symbols-outlined">leaderboard</span>
                    <h3 class="font-serif text-title-large font-bold">Mua nhiều nhất</h3>
                </div>
                <div class="divide-y divide-outline-variant/20">
                    <div v-for="(prod, idx) in topSellingProducts" :key="prod.id" class="py-3 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-outline w-5">{{ idx + 1 }}.</span>
                            <img :src="prod.image_url" alt="Hình sản phẩm" class="w-10 h-10 object-cover rounded-lg" />
                            <span class="font-medium text-on-surface">{{ prod.product_name }}</span>
                        </div>
                        <span class="text-body-medium font-bold text-primary">{{ prod.total_sold }} lượt mua</span>
                    </div>
                </div>
            </div>

            <!-- Cột 3: Top Sản phẩm Được Yêu Thích -->
            <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-tertiary">
                    <span class="material-symbols-outlined">favorite</span>
                    <h3 class="font-serif text-title-large font-bold">Yêu thích nhất (Món)</h3>
                </div>
                <div class="divide-y divide-outline-variant/20">
                    <div v-for="(prod, idx) in topLikedProducts" :key="prod.id" class="py-3 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-outline w-5">{{ idx + 1 }}.</span>
                            <img :src="prod.image_url" alt="Hình sản phẩm" class="w-10 h-10 object-cover rounded-lg" />
                            <span class="font-medium text-on-surface">{{ prod.product_name }}</span>
                        </div>
                        <span class="text-body-medium font-bold text-tertiary">{{ prod.total_favorites }} lượt thích</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TẦNG 3: BẢNG XẾP HẠNG BÀI VIẾT NỘI DUNG -->
        <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
            <div class="flex items-center gap-2 text-on-surface">
                <span class="material-symbols-outlined text-primary">article</span>
                <h3 class="font-serif text-title-large font-bold">Hiệu suất bài viết & Tin tức</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans text-body-medium">
                    <thead>
                        <tr class="text-on-surface-variant border-b border-outline-variant/30 text-label-large">
                            <th class="py-3 pl-4">Tiêu đề bài viết</th>
                            <th class="py-3">Lượt đọc</th>
                            <th class="py-3">Tương tác thích</th>
                            <th class="py-3 text-right pr-4">Đánh giá chung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="post in topPosts" :key="post.id" class="hover:bg-surface-container-high/50 transition-colors">
                            <td class="py-3 pl-4 font-medium max-w-xs truncate">{{ post.title }}</td>
                            <td class="py-3 text-on-surface-variant">{{ post.view_count?.toLocaleString('vi-VN') || 0 }} xem</td>
                            <td class="py-3 text-tertiary font-bold">{{ post.likes_count || 0 }} tim</td>
                            <td class="py-3 text-right pr-4">
                                <span class="px-2.5 py-0.5 bg-success-container text-on-success-container rounded text-label-small font-bold">Tốt</span>
                            </td>
                        </tr>
                        <tr v-if="topPosts.length === 0">
                            <td colspan="4" class="py-6 text-center text-outline">Chưa có bài viết nào được đăng tải công khai.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</AdminLayout>
    
</template>