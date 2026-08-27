<script setup>
import { ref, watch, computed } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import GiftVoucherModal from "./Components/GiftVoucherModal.vue";
import { debounce } from "lodash-es";

const props = defineProps({
    vouchers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const showOnlyExpired = ref(false); // Mặc định là hiển thị tất cả

const filteredVouchers = computed(() => {
    // Lọc theo các bộ lọc cũ (search, is_used, coupon_id) trước
    let list = props.vouchers.data; 

    // Sau đó lọc thêm điều kiện "Hết hạn" nếu người dùng bấm nút
    if (showOnlyExpired.value) {
        list = list.filter(v => isExpired(v.coupon?.expiration_date));
    }
    return list;
});

// Trạng thái cụm bộ lọc tự động
const searchFilters = ref({
    search: props.filters.search || "",
    is_used: props.filters.is_used || "",
    coupon_id: props.filters.coupon_id || "",
});

// Bọc hàm router.get trong debounce 500ms
const performSearch = debounce((filters) => {
    router.get("/quan-tri/vi-voucher", filters, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 500); // 500ms debounce

// Trong watch chỉ cần gọi hàm đã debounce
watch(searchFilters, (newFilters) => {
    performSearch(newFilters);
}, { deep: true });

const clearFilters = () => {
    searchFilters.value.search = "";
    searchFilters.value.is_used = "";
    searchFilters.value.coupon_id = "";
};

const revokeVoucher = (id, clientName, code) => {
    if (confirm(`Bạn có chắc muốn thu hồi mã "${code}" khỏi ví của khách hàng "${clientName}" không?`)) {
        router.delete(`/quan-tri/vi-voucher/${id}`, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return "---";
    const d = new Date(dateStr);
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${hours}:${minutes} — ${day}/${month}/${d.getFullYear()}`;
};

const isExpired = (date) => {
    return new Date(date) < new Date();
};

const isGiftModalOpen = ref(false);


</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative font-sans">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-headline-md text-on-surface font-sans text-primary text-3xl mt-2 mb-2"><span class="material-symbols-outlined text-primary">wallet</span> QUẢN LÝ VÍ VOUCHER</h1>
                    <p class="text-body-medium text-on-surface-variant">Giám sát danh sách phân phối mã giảm giá và lịch sử áp dụng voucher của từng khách hàng.</p>
                </div>

                <button @click="showOnlyExpired = !showOnlyExpired" 
                    :class="showOnlyExpired ? 'bg-error text-white' : 'bg-red-50 text-error hover:bg-red-100'"
                    class="inline-flex gap-2 px-5 py-2.5 rounded-full text-label-medium transition border border-error/20 cursor-pointer">
                        <span class="material-symbols-outlined text-md">
                            {{ showOnlyExpired ? 'visibility' : 'warning' }}
                        </span>
                        {{ showOnlyExpired ? 'Xem tất cả mã' : 'Chỉ hiện mã hết hạn' }}
                </button>

                <button 
                    @click="isGiftModalOpen = true" 
                    class="inline-flex gap-2 items-center px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm cursor-pointer"
                >
                    <span class="material-symbols-outlined text-md">redeem</span>
                    Phát hành / Tặng Voucher
                </button>
            </div>
            

            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>

            <GiftVoucherModal 
                :isOpen="isGiftModalOpen" 
                :coupons="$page.props.all_coupons"  
                :users="$page.props.all_customers"  
                @close="isGiftModalOpen = false" 
            />

            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm space-y-3">
                <!-- Thêm tiêu đề cho bộ lọc -->
                <div class="flex items-center gap-2 text-label-large text-outline font-bold uppercase tracking-wider select-none">
                    <span class="material-symbols-outlined text-lg">filter_list</span>
                    <span>Bộ lọc</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-body-medium">
                    <div class="sm:col-span-4 flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                        <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                        <input v-model="searchFilters.search" type="text" placeholder="Tìm theo tên khách, email, số điện thoại hoặc mã code..." class="w-full bg-transparent focus:outline-none text-on-surface" />
                    </div>

                    <div class="sm:col-span-3">
                        <select v-model="searchFilters.is_used" class="px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer focus:outline-none focus:border-primary">
                            <option value="">-- Tất cả trạng thái túi ví --</option>
                            <option value="0">Chưa sử dụng (Còn trong ví khách)</option>
                            <option value="1">Đã áp dụng hóa đơn thành công</option>
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <select v-model="searchFilters.coupon_id" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer focus:outline-none focus:border-primary">
                            <option value="">-- Lọc theo chương trình ưu đãi --</option>
                            <option v-for="c in $page.props.all_coupons" :key="c.id" :value="c.id">
                                {{ c.code }} (Giảm {{ c.discount_type === 'FIXED' ? Number(c.discount_value).toLocaleString('vi-VN') + 'đ' : c.discount_value + '%' }})
                            </option>
                        </select>
                    </div>

                    <div class="sm:col-span-1 text-right flex justify-end">
                        <button @click="clearFilters" v-if="searchFilters.search || searchFilters.is_used !== '' || searchFilters.coupon_id !== ''" class="w-full h-full p-2 hover:bg-error-container/20 text-outline hover:text-error rounded-xl transition-colors flex items-center justify-center cursor-pointer" title="Xóa bộ lọc">
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
                                <th class="p-4 w-16">ID ví</th>
                                <th class="p-4 text-left">Chủ sở hữu (Khách hàng)</th>
                                <th class="p-4 text-left">Mã ưu đãi phân phối</th>
                                <th class="p-4 text-left hidden md:table-cell">Giá trị chiết khấu</th>
                                <th class="p-4">Đơn tối thiểu</th>
                                <th class="p-4">Sức chứa (Lượt dùng)</th>
                                <th class="p-4">Tình trạng dùng</th>
                                <th class="p-4">Thời hạn</th>
                                <th class="p-4 text-right w-28">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-for="v in filteredVouchers" :key="v.id" 
                            :class="{'opacity-50 grayscale': isExpired(v.coupon?.expiration_date)}"
                            class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 font-mono text-body-small text-outline">#{{ v.id }}</td>
                                
                                <td class="p-4 text-left">
                                    <div class="font-bold text-on-surface">{{ v.user?.full_name || 'Tài khoản đã xóa' }}</div>
                                    <div class="text-body-small text-on-surface-variant font-mono">
                                        {{ v.user?.phone_number || v.user?.email || '---' }}
                                    </div>
                                </td>

                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <span class="font-mono font-bold bg-secondary-container text-on-secondary-container px-2.5 py-1 rounded-lg text-body-medium tracking-wide">
                                            {{ v.coupon?.code || 'COUPON_DELETED' }}
                                        </span>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all animate-fade-in">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                <span class="text-neutral-400">Thời điểm nhận mã:</span> 
                                                <span>{{ formatDateTime(v.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="v.is_used ? 'bg-blue-400' : 'bg-gray-400'"></span>
                                                <span class="text-neutral-400">Thời điểm quét dùng:</span> 
                                                <span>{{ v.is_used ? formatDateTime(v.used_at) : 'Chưa sử dụng' }}</span>
                                            </div>
                                            <div class="absolute top-full left-4 border-4 border-transparent border-t-neutral-900"></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 text-left font-sans">
                                    <div v-if="v.coupon?.discount_type === 'FIXED'" class="font-bold text-primary">
                                        Giảm {{ Number(v.coupon.discount_value).toLocaleString('vi-VN') }}đ
                                    </div>
                                    <div v-else-if="v.coupon" class="space-y-0.5">
                                        <div class="font-bold text-secondary">Giảm {{ Number(v.coupon.discount_value) }}%</div>
                                        <div class="text-body-small text-on-surface-variant font-mono">
                                            Tối đa: {{ Number(v.coupon.max_discount_amount).toLocaleString('vi-VN') }}đ
                                        </div>
                                    </div>
                                    <span v-else class="text-outline text-body-small">---</span>
                                </td>

                                <td class="p-4 font-mono font-bold">
                                    <template v-if="v.coupon">
                                        {{ Number(v.coupon.min_order_value) > 0 ? Number(v.coupon.min_order_value).toLocaleString('vi-VN') + 'đ' : '0đ (Tự do)' }}
                                    </template>
                                    <span v-else class="text-outline text-body-small">---</span>
                                </td>
                                
                                <td class="p-4 font-mono text-body-small">
                                    <template v-if="v.coupon">
                                        <span class="text-primary font-bold">{{ v.coupon.used_count }}</span> / 
                                        <span class="text-outline">{{ v.coupon.usage_limit ?? '∞' }}</span> Lượt
                                    </template>
                                    <span v-else class="text-outline text-body-small">---</span>
                                </td>

                                <td class="p-4">
                                    <span :class="[
                                        'px-3 py-0.5 rounded-full text-label-medium font-bold',
                                        v.is_used ? 'bg-surface-container-high text-outline line-through' : 'bg-primary-container text-on-primary-container'
                                    ]">
                                        {{ v.is_used ? 'Đã dùng' : 'Chưa dùng' }}
                                    </span>
                                </td>

                                <td>
                                    <span v-if="v.coupon && isExpired(v.coupon.expiration_date)" 
                                            class="bg-red-100 text-red-800 px-2 py-0.5 rounded-lg font-medium">
                                        Hết hạn
                                    </span>
                                    <span v-else-if="v.coupon" 
                                            class="bg-green-100 text-green-800 px-2 py-0.5 rounded-lg font-medium">
                                        Còn hạn
                                    </span>
                                </td>

                                <td class="p-4 text-right">
                                    <button 
                                        @click="revokeVoucher(v.id, v.user?.full_name, v.coupon?.code)" 
                                        class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" 
                                        title="Thu hồi voucher khỏi ví của khách"
                                    >
                                        <span class="material-symbols-outlined text-xl">delete</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="vouchers.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block font-light">style</span>
                                    Không có dữ liệu phân phối voucher nào trùng khớp với bộ lọc.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="vouchers.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-center gap-1">
                    <Component :is="link.url ? Link : 'span'" v-for="(link, index) in vouchers.links" :key="index" :href="link.url" v-html="link.label" :preserve-scroll="true" :class="[ 'px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' ]" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.15s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(2px); } to { opacity: 1; transform: translateY(0); } }
</style>