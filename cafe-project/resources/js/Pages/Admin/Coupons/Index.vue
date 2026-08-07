<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

import CouponFormModal from "./Components/CouponFormModal.vue";

const props = defineProps({
    coupons: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedCoupon = ref(null);

const searchFilters = ref({
    search: props.filters.search || "",
    discount_type: props.filters.discount_type || "",
    status: props.filters.status || "",
});

watch(searchFilters, (newFilters) => {
    router.get("/quan-tri/ma-giam-gia", newFilters, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

const clearFilters = () => {
    searchFilters.value.search = "";
    searchFilters.value.discount_type = "";
    searchFilters.value.status = "";
};

const openCreateModal = () => {
    isEditMode.value = false;
    selectedCoupon.value = null;
    isModalOpen.value = true;
};

const openEditModal = (coupon) => {
    isEditMode.value = true;
    selectedCoupon.value = coupon;
    isModalOpen.value = true;
};

// Cập nhật hàm XÓA MỀM
const deleteCoupon = (id, code) => {
    if (confirm(`Bạn có chắc chắn muốn chuyển mã "${code}" vào thùng rác không?`)) {
        router.delete(`/quan-tri/ma-giam-gia/${id}`);
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

const isExpired = (date) => {
    return new Date(date) < new Date();
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative font-sans">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl mt-2 mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">confirmation_number</span> 
                        MÃ GIẢM GIÁ
                    </h1>
                    <p class="text-body-medium text-on-surface-variant">Phát hành, quản lý và giám sát hiệu suất các chiến dịch thúc đẩy doanh số của quán.</p>
                </div>
                
                <div class="flex items-center gap-3 self-start sm:self-center">
                    <!-- Nút dẫn tới Thùng Rác -->
                    <Link
                        href="/quan-tri/ma-giam-gia/thung-rac"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-sans text-label-large rounded-full shadow-sm transition-all cursor-pointer text-red-600 hover:text-red-700"
                    >
                        <span class="material-symbols-outlined text-md">delete_sweep</span>
                        Thùng rác
                    </Link>

                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-md">add_card</span> Phát hành mã mới
                    </button>
                </div>
            </div>

            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-error-container/20 border border-error/20 text-on-error-container rounded-xl text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-error">error</span>
                {{ $page.props.flash.error }}
            </div>

            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm space-y-3">
                <div class="flex items-center gap-2 text-label-large text-outline font-bold uppercase tracking-wider select-none">
                    <span class="material-symbols-outlined text-lg">filter_alt</span>
                    <span>Bộ lọc coupon nâng cao</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-body-medium">
                    <div class="sm:col-span-5 flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                        <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                        <input v-model="searchFilters.search" type="text" placeholder="Gõ tìm ký tự mã giảm giá..." class="w-full bg-transparent focus:outline-none text-on-surface font-mono uppercase" />
                    </div>

                    <div class="sm:col-span-3">
                        <select v-model="searchFilters.discount_type" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Tất cả kiểu giảm --</option>
                            <option value="FIXED">Khấu trừ tiền mặt</option>
                            <option value="PERCENTAGE">Khấu trừ phần trăm %</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <select v-model="searchFilters.status" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Trạng thái mã --</option>
                            <option value="ACTIVE">ACTIVE (Đang mở)</option>
                            <option value="INACTIVE">INACTIVE (Đang ẩn)</option>
                            <option value="EXPIRED">EXPIRED (Hết hạn)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-1 text-right flex justify-end">
                        <button @click="clearFilters" v-if="searchFilters.search || searchFilters.discount_type || searchFilters.status" class="w-full h-full p-2 hover:bg-error-container/20 text-outline hover:text-error rounded-xl transition-colors flex items-center justify-center cursor-pointer" title="Xóa bộ lọc">
                            <span class="material-symbols-outlined">filter_alt_off</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 text-label-large text-on-surface-variant">
                                <th class="p-4 text-left">Mã ưu đãi</th>
                                <th class="p-4 text-left">Cấu hình chi tiết</th>
                                <th class="p-4">Yêu cầu đơn</th>
                                <th class="p-4">Hạn mức dùng</th>
                                <th class="p-4">Ngày hết hạn</th>
                                <th class="p-4">Trạng thái</th>
                                <th class="p-4 text-right w-32">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-for="coupon in coupons.data" :key="coupon.id" class="hover:bg-surface-container-low/50 transition-colors">
                                
                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <span class="font-mono font-bold bg-primary-container text-on-primary-container px-3 py-1 rounded-lg text-title-small tracking-wider">
                                            {{ coupon.code }}
                                        </span>
                                        
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all animate-fade-in">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                <span class="text-neutral-400">Ngày tạo:</span> 
                                                <span>{{ formatDateTime(coupon.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                                <span class="text-neutral-400">Sửa cuối:</span> 
                                                <span>{{ formatDateTime(coupon.updated_at) }}</span>
                                            </div>
                                            <div class="absolute top-full left-4 border-4 border-transparent border-t-neutral-900"></div>
                                        </div>
                                    </div>
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

                                <td class="p-4 font-mono font-bold">
                                    {{ Number(coupon.min_order_value) > 0 ? Number(coupon.min_order_value).toLocaleString('vi-VN') + 'đ' : '0đ (Tự do)' }}
                                </td>

                                <td class="p-4 font-mono text-body-small">
                                    <span class="text-primary font-bold">{{ coupon.used_count }}</span> / 
                                    <span class="text-outline">{{ coupon.usage_limit ?? '∞' }}</span> Lượt
                                </td>

                                <td class="p-4 font-mono text-body-small">
                                    <div class="flex items-center gap-2 justify-center">
                                        <span :class="isExpired(coupon.expiration_date) ? 'text-error font-medium' : 'text-on-surface-variant'">
                                            {{ formatDateTime(coupon.expiration_date) }}
                                        </span>
                                    
                                        <span v-if="isExpired(coupon.expiration_date)" 
                                                class="bg-red-100 text-red-800 px-2 py-0.5 rounded-lg font-medium">
                                            Hết hạn
                                        </span>
                                        <span v-else 
                                                class="bg-green-100 text-green-800 px-2 py-0.5 rounded-lg font-medium">
                                            Còn hạn
                                        </span>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span :class="[
                                        'px-2.5 py-0.5 rounded-full text-label-medium font-bold',
                                        coupon.status === 'ACTIVE' ? 'bg-primary-container text-on-primary-container' : 
                                        coupon.status === 'INACTIVE' ? 'bg-surface-container-high text-outline' : 'bg-error-container/30 text-error'
                                    ]">
                                        {{ coupon.status }}
                                    </span>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(coupon)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer" title="Chỉnh sửa">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteCoupon(coupon.id, coupon.code)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" title="Chuyển vào thùng rác">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="coupons.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block font-light">local_activity</span>
                                    Không tìm thấy mã ưu đãi nào trùng khớp với bộ lọc dữ liệu.
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

            <CouponFormModal :isOpen="isModalOpen" :editMode="isEditMode" :couponData="selectedCoupon" @close="isModalOpen = false" />
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.15s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(2px); } to { opacity: 1; transform: translateY(0); } }
</style>