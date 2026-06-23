<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    reservations: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    status: { type: Object, required: true }
});

// Trạng thái cụm bộ lọc
const searchFilters = ref({
    search: props.filters.search || "",
    status: props.filters.status || "",
    date_range: props.filters.date_range || "this_month",
});

// Watcher tự động trigger dữ liệu lọc lên server
watch(searchFilters, (newFilters) => {
    router.get("/quan-tri/dat-ban", newFilters, {
        preserveState: true,
        replace: true
    });
}, { deep: true });

const clearFilters = () => {
    searchFilters.value.search = "";
    searchFilters.value.status = "";
    searchFilters.value.date_range = "this_month";
};

// Trạng thái đóng/mở Modal ghi đè và lưu trữ dữ liệu đơn đang chọn
const isOverrideModalOpen = ref(false);
const selectedReservationId = ref(null);
const overrideStatusValue = ref("");

// Hàm kích hoạt khi bấm nút Admin ở cuối hàng
const openOverrideModal = (id, currentStatus) => {
    selectedReservationId.value = id;
    overrideStatusValue.value = currentStatus; // Gán trạng thái hiện tại làm mặc định trong thẻ select
    isOverrideModalOpen.value = true;
};

// Hàm gửi request cập nhật trạng thái lên Laravel
const submitOverrideStatus = () => {
    router.put(`/quan-tri/dat-ban/ghi-de/${selectedReservationId.value}`, { 
        status: overrideStatusValue.value 
    }, {
        onSuccess: () => {
            isOverrideModalOpen.value = false;
        },
        onError: () => {
            toast.error("Có lỗi xảy ra khi cập nhật!");
        }
    });
};

// Định dạng thời gian hiển thị thân thiện
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

const getStatusBadge = (status) => {
    switch (status) {
        case 'PENDING': return 'bg-warning-container text-on-warning-container';
        case 'CONFIRMED': return 'bg-secondary-container text-on-secondary-container';
        case 'ARRIVED': return 'bg-primary-container text-on-primary-container font-bold';
        case 'CANCELLED': return 'bg-error-container/30 text-error';
        default: return 'bg-surface-container text-outline';
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative font-sans">
            <div>
                <h1 class="text-headline-md text-on-surface">Giám sát đặt bàn ăn / cà phê</h1>
                <p class="text-body-medium text-on-surface-variant">Báo cáo biến động, log vết dữ liệu đặt chỗ theo thời gian thực tại Nắng Coffee.</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface p-4 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-warning bg-warning-container/20 p-2 rounded-xl">hourglass_empty</span>
                    <div>
                        <div class="text-title-large font-mono font-bold">{{ status.total_pending }}</div>
                        <div class="text-body-small text-on-surface-variant">Đơn chờ duyệt hệ thống</div>
                    </div>
                </div>
                <div class="bg-surface p-4 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-secondary bg-secondary-container/20 p-2 rounded-xl">verified</span>
                    <div>
                        <div class="text-title-large font-mono font-bold">{{ status.total_confirmed }}</div>
                        <div class="text-body-small text-on-surface-variant">Đơn đã giữ chỗ thành công</div>
                    </div>
                </div>
                <div class="bg-surface p-4 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-primary bg-primary-container/20 p-2 rounded-xl">how_to_reg</span>
                    <div>
                        <div class="text-title-large font-mono font-bold">{{ status.total_arrived }}</div>
                        <div class="text-body-small text-on-surface-variant">Khách đã đến hôm nay</div>
                    </div>
                </div>
                <div class="bg-surface p-4 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-error bg-error-container/10 p-2 rounded-xl">cancel</span>
                    <div>
                        <div class="text-title-large font-mono font-bold">{{ status.total_cancelled }}</div>
                        <div class="text-body-small text-on-surface-variant">Lượt hủy hôm nay</div>
                    </div>
                </div>
            </div>

            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm space-y-3">
                <div class="flex items-center gap-2 text-label-large text-outline font-bold uppercase tracking-wider select-none">
                    <span class="material-symbols-outlined text-lg">filter_list</span>
                    <span>Bộ lọc đặt bàn</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-body-medium">

                    <div class="sm:col-span-5 flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                        <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                        <input v-model="searchFilters.search" type="text" placeholder="Tìm tên khách, số điện thoại, số bàn..." class="w-full bg-transparent focus:outline-none text-on-surface" />
                    </div>

                    <div class="sm:col-span-3">
                        <select v-model="searchFilters.date_range" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="today">Hôm nay</option>
                            <option value="tomorrow">Ngày mai</option>
                            <option value="this_week">Tuần này</option>
                            <option value="this_month">Tháng này (Toàn bộ)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <select v-model="searchFilters.status" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Tất cả trạng thái đơn --</option>
                            <option value="PENDING">PENDING (Chờ duyệt)</option>
                            <option value="CONFIRMED">CONFIRMED (Đã giữ chỗ)</option>
                            <option value="ARRIVED">ARRIVED (Khách đã đến)</option>
                            <option value="CANCELLED">CANCELLED (Đã hủy)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-1 text-right flex justify-end">
                        <button @click="clearFilters" v-if="searchFilters.search || searchFilters.status || searchFilters.date_range !== 'this_month'" class="w-full h-full p-2 hover:bg-error-container/20 text-outline hover:text-error rounded-xl transition-colors flex items-center justify-center cursor-pointer" title="Xóa bộ lọc">
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
                                <th class="p-4 w-12">STT</th>
                                <th class="p-4 text-left">Khách đặt chỗ</th>
                                <th class="p-4">Thời gian hẹn</th>
                                <th class="p-4">Vị trí bàn xếp</th>
                                <th class="p-4 w-24">Số khách</th>
                                <th class="p-4 text-left hidden lg:table-cell">Ghi chú</th>
                                <th class="p-4">Trạng thái</th>
                                <th class="p-4 text-right w-20">Lệnh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-for="(res, index) in reservations.data" :key="res.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 font-mono text-body-small text-outline">{{ index + 1 }}</td>
                                
                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <div class="font-bold text-primary">{{ res.user?.full_name || 'Khách vãng lai' }}</div>
                                        <div class="font-mono text-body-small text-on-surface-variant">{{ res.phone_number }}</div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all animate-fade-in">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                <span class="text-neutral-400">Khách gửi đơn:</span> 
                                                <span>{{ formatDateTime(res.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                                <span class="text-neutral-400">Cập nhật cuối:</span> 
                                                <span>{{ formatDateTime(res.updated_at) }}</span>
                                            </div>
                                            <div class="absolute top-full left-4 border-4 border-transparent border-t-neutral-900"></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 font-mono text-body-small font-bold text-on-surface-variant">
                                    {{ formatDateTime(res.reservation_time) }}
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-secondary">{{ res.table?.table_name || 'Chưa điều bàn' }}</div>
                                    <div class="text-body-small text-outline font-sans">{{ res.table?.area || '---' }}</div>
                                </td>
                                <td class="p-4 font-bold font-mono">{{ res.guest_count }} người</td>
                                <td class="p-4 text-left hidden lg:table-cell text-on-surface-variant truncate max-w-xs" :title="res.note">
                                    {{ res.note || '---' }}
                                </td>
                                <td class="p-4">
                                    <span :class="['px-3 py-1 rounded-full text-label-medium font-bold', getStatusBadge(res.status)]">
                                        {{ res.status }}
                                    </span>
                                </td>
                                <button 
                                    @click="openOverrideModal(res.id, res.status)" 
                                    class="p-4 hover:bg-surface-container-high text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer mt-2" 
                                    title="Ghi đè trạng thái (Quyền cao nhất)"
                                >
                                    <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                                </button>
                            </tr>
                            <tr v-if="reservations.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block font-light">calendar_today</span>
                                    Không có lượt đặt chỗ nào được ghi nhận trong khoảng thời gian giám sát này.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="reservations.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-center gap-1">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in reservations.links"
                        :key="index"
                        :href="link.url"
                        v-html="link.label"
                        :preserve-scroll="true" 
                        :class="[ 'px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' ]"
                    />
                </div>
            </div>
        </div>
        <div v-if="isOverrideModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
                <div class="bg-surface w-full max-w-sm rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-4 animate-scale-up">
                    
                    <div class="flex items-center justify-between border-b border-outline-variant/10 pb-2">
                        <h3 class="text-title-medium font-bold text-error flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-xl">security</span>
                            Đặc quyền Admin
                        </h3>
                        <button @click="isOverrideModalOpen = false" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="space-y-3 font-sans">
                        <p class="text-body-medium text-on-surface-variant">
                            Ép thay đổi trạng thái đơn đặt bàn này (Hành động này sẽ bỏ qua các ràng buộc logic thông thường):
                        </p>
                        
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-outline font-bold">Chọn trạng thái mới</label>
                            <select 
                                v-model="overrideStatusValue" 
                                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface font-bold focus:outline-none focus:border-primary transition-all cursor-pointer"
                            >
                                <option value="PENDING">PENDING — Chờ duyệt</option>
                                <option value="CONFIRMED">CONFIRMED — Đã giữ chỗ</option>
                                <option value="ARRIVED">ARRIVED — Khách đã đến</option>
                                <option value="CANCELLED">CANCELLED — Đã hủy đơn</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button 
                            type="button" 
                            @click="isOverrideModalOpen = false" 
                            class="px-4 py-2 hover:bg-surface-container-high text-primary text-label-large rounded-full transition-colors cursor-pointer"
                        >
                            Hủy bỏ
                        </button>
                        <button 
                            type="button" 
                            @click="submitOverrideStatus" 
                            class="px-5 py-2 bg-error text-on-error hover:bg-error/90 text-label-large font-bold rounded-full shadow-sm transition-all cursor-pointer"
                        >
                            Xác nhận ghi đè
                        </button>
                    </div>

                </div>
            </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.15s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(2px); } to { opacity: 1; transform: translateY(0); } }
</style>