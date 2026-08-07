<script setup>
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

defineProps({
    coupons: {
        type: Object,
        required: true,
    },
});

// Hàm Khôi Phục Mã Giảm Giá
const restoreCoupon = (id, code) => {
    if (confirm(`Bạn có chắc chắn muốn khôi phục mã giảm giá "${code}" không?`)) {
        router.post(`/quan-tri/ma-giam-gia/${id}/restore`);
    }
};

// Hàm Xóa Vĩnh Viễn Mã Giảm Giá
const forceDeleteCoupon = (id, code) => {
    if (confirm(`CẢNH BÁO: Mã giảm giá sẽ bị xóa vĩnh viễn khỏi hệ thống!\n\nBạn có chắc chắn muốn XÓA VĨNH VIỄN mã "${code}" không?`)) {
        router.delete(`/quan-tri/ma-giam-gia/${id}/force-delete`);
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
    return `${hours}:${minutes} — ${day}/${month}/${d.getFullYear()}`;
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative font-sans">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-error text-3xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-error">delete_sweep</span> 
                        THÙNG RÁC - MÃ GIẢM GIÁ
                    </h1>
                    <p class="text-body-medium text-on-surface-variant">Danh sách các mã ưu đãi/khuyến mãi đã bị xóa tạm thời.</p>
                </div>
                
                <Link
                    href="/quan-tri/ma-giam-gia"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center cursor-pointer"
                >
                    <span class="material-symbols-outlined text-md">arrow_back</span>
                    Quay lại danh sách
                </Link>
            </div>

            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 text-label-large text-on-surface-variant">
                                <th class="p-4 text-left">Mã ưu đãi</th>
                                <th class="p-4 text-left">Cấu hình giảm giá</th>
                                <th class="p-4 hidden md:table-cell">Thời gian xóa</th>
                                <th class="p-4 text-right w-36">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-for="coupon in coupons.data" :key="coupon.id" class="hover:bg-surface-container-low/50 transition-colors">
                                
                                <td class="p-4 text-left">
                                    <span class="font-mono font-bold bg-surface-container-high text-on-surface-variant px-3 py-1 rounded-lg text-title-small tracking-wider">
                                        {{ coupon.code }}
                                    </span>
                                </td>

                                <td class="p-4 text-left font-sans">
                                    <div v-if="coupon.discount_type === 'FIXED'" class="font-bold text-primary">
                                        Giảm {{ Number(coupon.discount_value).toLocaleString('vi-VN') }}đ
                                    </div>
                                    <div v-else class="space-y-0.5">
                                        <div class="font-bold text-secondary">Giảm {{ Number(coupon.discount_value) }}%</div>
                                        <div class="text-body-small text-on-surface-variant font-mono">Tối đa: {{ Number(coupon.max_discount_amount).toLocaleString('vi-VN') }}đ</div>
                                    </div>
                                </td>

                                <td class="p-4 hidden md:table-cell text-error font-mono text-body-small select-none">
                                    {{ formatDateTime(coupon.deleted_at) }}
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Nút Khôi phục -->
                                        <button 
                                            @click="restoreCoupon(coupon.id, coupon.code)" 
                                            class="p-2 hover:bg-primary-container/30 text-primary rounded-full transition-colors cursor-pointer" 
                                            title="Khôi phục mã"
                                        >
                                            <span class="material-symbols-outlined text-xl">restore_from_trash</span>
                                        </button>

                                        <!-- Nút Xóa vĩnh viễn -->
                                        <button 
                                            @click="forceDeleteCoupon(coupon.id, coupon.code)" 
                                            class="p-2 hover:bg-error-container/20 text-error rounded-full transition-colors cursor-pointer" 
                                            title="Xóa vĩnh viễn"
                                        >
                                            <span class="material-symbols-outlined text-xl">delete_forever</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="coupons.data.length === 0">
                                <td colspan="4" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block font-light">cleaning_services</span>
                                    Thùng rác trống! Không có mã giảm giá nào bị xóa.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="coupons.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-center gap-1">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in coupons.links"
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