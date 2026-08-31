<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import RevenueChart from './Components/RevenueChart.vue';

defineProps({
    counters: Object,
    revenue: Number,
    totalOrders: Number,
    lowStockIngredients: Array,
    topSellingProducts: Array,
    topLikedProducts: Array,
    topPosts: Array,
    chartData: Object,
    selectedYear: Number,
    availableYears: Array,
    topCustomers: Array
});

// Hàm format tiền tệ Việt Nam
const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};
</script>

<template>

<AdminLayout>
    <Head title="Trang chủ Quản trị" />

    <div class="p-6 space-y-6 bg-surface min-h-screen text-on-surface font-sans">
        
        <!-- HEADER TRANG -->
        <div class="flex flex-col gap-1">
            <h1 class="font-sans font-bold text-primary text-3xl"><span class="material-symbols-outlined text-primary">space_dashboard</span> TỔNG QUAN VẬN HÀNH</h1>
            <p class="text-body-medium text-outline">Dữ liệu phân tích hoạt động kinh doanh thực tế.</p>
        </div>

        <hr class="border-outline-variant/30" />

        <!-- TẦNG 1: THẺ SỐ LIỆU DOANH THU & ĐƠN HÀNG NHANH -->
        <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!--Tiêu đề thẻ-->
                <div class="col-span-1 md:col-span-2 lg:col-span-4">
                    <h1 class="text-2xl font-bold text-on-surface text-primary"><span class="material-symbols-outlined text-primary">speed</span> Thống kê nhanh</h1>
                </div>

                <!-- Thẻ Tổng Doanh Thu -->
                <div class="bg-green-100 text-on-primary-container p-5 rounded-2xl flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <span class="text-label-medium font-bold uppercase tracking-wider opacity-80">Doanh thu tổng</span>
                        <h2 class="text-headline-medium font-bold font-serif">{{ formatCurrency(revenue) }}</h2>
                        <p class="text-body-small text-outline">Tổng doanh thu của quán</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small">payments</span>
                </div>

                <!-- Thẻ Tổng danh mục sản phẩm -->
                <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Danh mục sản phẩm</span>
                        <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.categories.total }}</h2>
                        <p class="text-body-small text-outline">Hệ thống có {{ counters.categories.total }} danh mục</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small text-primary">category</span>
                </div>

                <!-- Thẻ Tổng Sản Phẩm Hệ Thống -->
                <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Tổng sản phẩm</span>
                        <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.products.total }}</h2>
                        <p class="text-body-small text-outline">{{ counters.products.active }} món đang bán</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small text-primary">menu</span>
                </div>

                <!-- Thẻ Tổng Khách Hàng -->
                <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Thành viên</span>
                        <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.users.total }}</h2>
                        <p class="text-body-small text-outline">Hệ thống có {{ counters.users.staff }} nhân viên, {{ counters.users.barista }} pha chế và {{ counters.users.customers }} khách hàng</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small text-primary">group</span>
                </div>

                <!-- Thẻ Tổng Nguyên Liệu -->
                <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Nguyên liệu</span>
                        <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.ingredients.total }} mục</h2>
                        <p class="text-body-small text-outline">Tổng số nguyên liệu trong kho</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small text-primary">inventory_2</span>
                </div>

                <!-- Thẻ Tổng Bài Viết -->
                <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Bài viết</span>
                        <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.posts.total }} bài</h2>
                        <p class="text-body-small text-outline">Tổng số bài viết trong hệ thống</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small text-primary">article</span>
                </div>

                <!-- Thẻ Tổng công thức -->
                <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Công thức</span>
                        <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.recipes.total }} công thức</h2>
                        <p class="text-body-small text-outline">Tổng số công thức trong hệ thống</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small text-primary">restaurant_menu</span>
                </div>

                <!-- Thẻ Tổng bàn -->
                <div class="bg-surface-container-low border border-outline-variant/30 p-5 rounded-2xl flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-label-medium text-on-surface-variant font-bold uppercase tracking-wider">Bàn</span>
                        <h2 class="text-headline-medium font-bold text-on-surface font-serif">{{ counters.tables.total }} bàn</h2>
                        <p class="text-body-small text-outline">Tổng số bàn trong quán</p>
                    </div>
                    <span class="material-symbols-outlined text-display-small text-primary">table_restaurant</span>
                </div>
            </div>
        </div>

        <!-- TẦNG 2: SƠ ĐỒ DOANH THU CỦA QUÁN TỪNG THÁNG VÀ TỪNG NĂM -->
        <RevenueChart 
            :chartData="chartData" 
            :selectedYear="selectedYear"
            :availableYears="availableYears"
            class="my-6" 
        />

        <!-- TẦNG 3: BÁO CÁO KHO NGUYÊN LIỆU & SẢN PHẨM -->
        <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!--Tiêu đề thẻ-->
                <div class="col-span-1 md:col-span-2 lg:col-span-4">
                    <h1 class="text-2xl font-bold text-on-surface text-primary"><span class="material-symbols-outlined">inventory_2</span> Báo cáo kho nguyên liệu & sản phẩm</h1>
                    <span class="text-body-small text-outline">Báo cáo tình trạng kho nguyên liệu và sản phẩm</span>
                </div>

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
                        <h3 class="font-sans text-title-large font-bold">Top 5 sản phẩm bán chạy nhất</h3>
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
                        <h3 class="font-sans text-title-large font-bold">Top 5 sản phẩm được yêu thích nhất</h3>
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
        </div>

        <!-- TẦNG 4: BÁO CÁO NGƯỜI DÙNG MUA HÀNG NHIỀU NHẤT -->
        <!-- BÁO CÁO TOP KHÁCH HÀNG THÂN THIẾT -->
        <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
            <!--Tiêu đề thẻ-->
            <div class="col-span-1 md:col-span-2 lg:col-span-4">
                <h1 class="text-2xl font-bold text-on-surface text-primary"><span class="material-symbols-outlined">person</span> Báo cáo người dùng</h1>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-primary">
                    <h3 class="font-sans text-title-large font-bold">Top 5 Khách hàng thân thiết</h3>
                </div>
                <span class="text-body-small text-outline">Tính trên đơn hàng hoàn tất</span>
            </div>
        
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans text-body-medium">
                    <thead>
                        <tr class="text-on-surface-variant border-b border-outline-variant/30 text-label-large">
                            <th class="py-3 pl-4">Khách hàng</th>
                            <th class="py-3 pl-4">Email</th>
                            <th class="py-3 pl-4">Số điện thoại</th>
                            <th class="py-3 text-center">Số đơn đã mua</th>
                            <th class="py-3 text-right pr-4">Tổng chi tiêu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="(customer, idx) in topCustomers" :key="customer.id" class="hover:bg-surface-container-high/50 transition-colors">
                            <td class="py-3 pl-4 font-medium flex items-center gap-3">
                                <span class="font-bold text-outline w-4">{{ idx + 1 }}.</span>
                                <div>
                                    <p class="font-bold text-on-surface">{{ customer.full_name }}</p>
                                </div>
                            </td>
                            <td class="py-3 pl-4 font-medium text-on-surface">{{ customer.email }}</td>
                            <td class="py-3 pl-4 font-medium text-on-surface">{{ customer.phone_number }}</td>
                            
                            <td class="py-3 text-center font-bold text-on-surface">
                                <span class="px-2.5 py-1 bg-secondary-container text-on-secondary-container text-label-small font-bold rounded-full">
                                    {{ customer.total_orders }} đơn
                                </span>
                            </td>
                            <td class="py-3 text-right pr-4 font-bold text-primary">
                                {{ formatCurrency(customer.total_spent) }}
                            </td>
                        </tr>
                        <tr v-if="topCustomers.length === 0">
                            <td colspan="3" class="py-6 text-center text-outline">Chưa có dữ liệu khách hàng mua hàng.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TẦNG 5: BẢNG XẾP HẠNG BÀI VIẾT NỘI DUNG -->
        <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
            <!--Tiêu đề thẻ-->
            <div class="col-span-1 md:col-span-2 lg:col-span-4">
                <h1 class="text-2xl font-bold text-on-surface text-primary"><span class="material-symbols-outlined text-primary">article</span> Báo cáo bài viết</h1>
            </div>

            <div class="flex items-center gap-2 text-on-surface">
                <h3 class="font-sans text-title-large font-bold text-on-surface text-primary">Top 5 bài viết được đánh giá cao nhất</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans text-body-medium">
                    <thead>
                        <tr class="text-on-surface-variant border-b border-outline-variant/30 text-label-large">
                            <th class="py-3 pl-4">Tiêu đề bài viết</th>
                            <th class="py-3 text-right pr-4">Đánh giá</th>
                            <th class="py-3 text-right pr-4">Tổng sao trung bình</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="post in topPosts" :key="post.id" class="hover:bg-surface-container-high/50 transition-colors">
                            <td class="py-3 pl-4 font-medium max-w-xs truncate">{{ post.title }}</td>
                            <td class="py-3 text-right pr-4 font-bold text-on-surface">{{ post.total_reviews }} lượt đánh giá</td>
                            <td class="py-3 text-right pr-4 font-bold text-on-surface">{{ post.avg_rating }} <span class="material-symbols-outlined text-yellow-500">star</span></td>
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