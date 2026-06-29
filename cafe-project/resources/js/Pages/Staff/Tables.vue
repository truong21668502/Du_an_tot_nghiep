<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import { toast } from "vue3-toastify";
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    initialTables: Array,
});

const tables = ref(props.initialTables || []);

// xử lý mở đóng modal
const selectedTable = ref(null);
const isTableModalOpen = ref(false);

const openTableModal = (table) => {
    selectedTable.value = table;
    isTableModalOpen.value = true;
};

const closeTableModal = () => {
    isTableModalOpen.value = false;
    setTimeout(() => selectedTable.value = null, 300);
};

// hàm định dạng tiền tệ
const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

// hàm tính tổng tiền của tất cả các đơn trên một bàn
const calculateTotalAmount = (tableOrders) => {
    if (!tableOrders || tableOrders.length === 0) return 0;
    return tableOrders.reduce((sum, order) => sum + Number(order.final_amount || 0), 0);
};

// hàm gộp các món ăn trùng tên và trùng size
const getGroupedOrderDetails = (ordersList) => {
    if (!ordersList || ordersList.length === 0) return [];
    
    const grouped = {};
    
    ordersList.forEach(order => {
        if (!order.details) return;
        
        order.details.forEach(detail => {
            const key = `${detail.product_id}_${detail.variant_id || 'default'}`;
            
            if (!grouped[key]) {
                grouped[key] = { ...detail, quantity: Number(detail.quantity) };
            } else {
                grouped[key].quantity += Number(detail.quantity);
            }
        });
    });
    
    return Object.values(grouped);
};

// gom nhóm bàn theo khu vực
const groupedTables = computed(() => {
    return tables.value.reduce((acc, table) => {
        const area = table.area || 'Khu vực khác';
        if (!acc[area]) acc[area] = [];
        acc[area].push(table);
        return acc;
    }, {});
});

// cập nhật trạng thái bàn (trống hoặc có khách)
const updateTableStatus = (tableId, newStatus) => {
    router.patch(route('staff.tables.update-status', tableId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            // cập nhật lại ui nội bộ nhanh chóng
            const table = tables.value.find(t => t.id === tableId);
            if (table) {
                table.status = newStatus;
                // nếu dọn bàn, xóa mảng đơn hàng đi cho sạch
                if (newStatus === 'EMPTY') table.orders = [];
            }

            // hiện thông báo
            if (newStatus === 'OCCUPIED') {
                toast.success(`Đã xếp khách vào ${table.table_name}`);
            } else {
                toast.info(`Đã dọn dẹp ${table.table_name}`);
            }

            closeTableModal();
        }
    });
};

// lắng nghe sự kiện real-time
onMounted(() => {
    if (window.Echo) {
        // kênh cập nhật trạng thái bàn
        window.Echo.channel('cafe-tables')
            .listen('.TableUpdated', (e) => {
                const index = tables.value.findIndex(t => t.id === e.id);
                if (index !== -1) {
                    tables.value[index].status = e.status;
                    if (e.status === 'EMPTY') tables.value[index].orders = [];

                    // cập nhật luôn modal nếu nó đang mở trúng bàn vừa có thay đổi
                    if (isTableModalOpen.value && selectedTable.value?.id === e.id) {
                        selectedTable.value.status = e.status;
                    }
                }
            });

        // kênh lắng nghe đơn hàng (giúp sơ đồ bàn cũng nhận được data hóa đơn lập tức)
        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => { 
                if (e.order && e.order.table_id) {
                    const tableIndex = tables.value.findIndex(t => t.id === e.order.table_id);
                    if (tableIndex !== -1) {
                        tables.value[tableIndex].status = 'OCCUPIED';
                        
                        if (!tables.value[tableIndex].orders) {
                            tables.value[tableIndex].orders = [];
                        }
                        
                        const orderExists = tables.value[tableIndex].orders.some(o => o.id === e.order.id);
                        if (!orderExists) {
                            tables.value[tableIndex].orders.push(e.order);
                            // rung chuông thông báo nhẹ
                            toast.success(`đơn mới ở ${tables.value[tableIndex].table_name}!`, { autoClose: 3000 });
                        }
                    }
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel('cafe-tables');
        window.Echo.leaveChannel('staff-orders');
    }
});
</script>

<template>
    <Head title="Sơ đồ bàn - Nắng Coffee" />

    <StaffLayout>
        <div class="mb-10">
            <p class="text-label-md text-primary tracking-wider mb-2">QUẢN LÝ</p>
            <h2 class="text-display-lg-mobile md:text-display-lg text-on-background">Sơ đồ mặt bằng</h2>
        </div>

        <div v-for="(areaTables, areaName) in groupedTables" :key="areaName" class="mb-10">
            <h3 class="text-headline-sm text-outline border-b-2 border-outline-variant/30 pb-3 mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[24px]">apartment</span>
                {{ areaName }}
            </h3>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <div v-for="table in areaTables" :key="table.id" @click="openTableModal(table)"
                    class="aspect-square rounded-2xl flex flex-col items-center justify-between p-3 cursor-pointer transition-all duration-300 shadow-sm border-2 relative overflow-hidden"
                    :class="table.status === 'EMPTY'
                        ? 'bg-surface-container border-transparent hover:bg-surface-container-high'
                        : 'bg-primary-container/20 border-primary shadow-primary/20 hover:bg-primary-container/30'">

                    <div class="flex-1 flex flex-col items-center justify-center w-full px-1">
                        <span class="font-serif text-headline-md font-bold mb-1 text-center break-words line-clamp-3 w-full"
                            :class="table.status === 'EMPTY' ? 'text-on-surface-variant' : 'text-primary'">
                            {{ table.table_name.replace('Bàn ', '') }}
                        </span>

                        <span class="text-label-sm flex items-center gap-1 mt-1"
                            :class="table.status === 'EMPTY' ? 'text-on-surface-variant/70' : 'text-primary/80'">
                            <span class="material-symbols-outlined text-[14px]">group</span>
                            {{ table.capacity }}
                        </span>
                    </div>

                    <span class="text-[11px] font-bold uppercase tracking-wider mt-2 px-3 py-1 rounded-full text-center"
                        :class="table.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : 'bg-primary text-on-primary'">
                        {{ table.status === 'EMPTY' ? 'Trống' : 'Có Khách' }}
                    </span>
                </div>
            </div>
            
        </div>

        <Transition name="fade">
            <div v-if="isTableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeTableModal"></div>
                <Transition name="slide-up">
                    <div v-if="isTableModalOpen" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col">

                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-center"
                            :class="[
                                selectedTable?.status === 'EMPTY' ? 'bg-surface text-on-surface' : 'bg-primary-container text-on-primary-container'
                            ]">
                            <div>
                                <h3 class="font-serif text-headline-md font-bold">{{ selectedTable?.table_name }}</h3>
                                <p class="text-label-md mt-1 opacity-80 uppercase tracking-widest">
                                    {{ selectedTable?.area }} • {{ selectedTable?.capacity }} NGƯỜI
                                </p>
                            </div>
                            <button @click="closeTableModal" class="p-2 hover:bg-black/10 rounded-full transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-6 bg-surface-container-lowest max-h-[75vh] overflow-y-auto hide-scrollbar">

                            <template v-if="selectedTable?.status === 'OCCUPIED' && selectedTable?.orders && selectedTable.orders.length > 0">
                                <div class="mb-6 border border-outline-variant/30 p-4 rounded-xl">
                                    <div class="flex justify-between items-center mb-3">
                                        <p class="text-label-sm text-on-surface font-bold tracking-wider flex items-center gap-1 uppercase">
                                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                            THÔNG TIN ĐƠN HÀNG
                                        </p>
                                        <span v-if="selectedTable.orders.length > 1" class="text-[10px] bg-secondary text-on-secondary px-2 py-0.5 rounded-full font-bold">
                                            {{ selectedTable.orders.length }} đơn
                                        </span>
                                    </div>
                                    
                                    <div class="flex flex-col gap-2 text-body-md">
                                        <div class="border-b border-outline-variant/20 pb-3 space-y-3 max-h-[30vh] overflow-y-auto pr-1">
                                            <div v-for="(detail, index) in getGroupedOrderDetails(selectedTable.orders)" :key="'grouped-' + index" class="flex justify-between items-start gap-3">
                                                <div class="flex-1 flex flex-col">
                                                    <span class="text-on-surface text-[15px]">{{ detail.product?.product_name }}</span>
                                                    <span class="text-[12px] text-on-surface-variant mt-0.5">
                                                        Size: {{ detail.variant?.size || '---' }} • Đơn giá: {{ formatCurrency(detail.unit_price) }}
                                                    </span>
                                                </div>
                                                <span class="font-bold text-on-surface mt-0.5">x{{ detail.quantity }}</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-between border-b border-outline-variant/20 py-2 items-center">
                                            <span class="text-on-surface-variant">Tổng cộng:</span>
                                            <span class="font-bold text-on-surface text-label-lg">
                                                {{ formatCurrency(calculateTotalAmount(selectedTable.orders)) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between pt-1 items-center">
                                            <span class="text-on-surface-variant">Thanh toán:</span>
                                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider"
                                                    :class="selectedTable.orders.every(o => o.payment?.payment_status === 'PAID') ? 'bg-secondary-container text-secondary' : 'bg-error-container text-error'">
                                                {{ selectedTable.orders.every(o => o.payment?.payment_status === 'PAID') ? 'ĐÃ THANH TOÁN' : (selectedTable.orders.some(o => o.payment?.payment_status === 'PAID') ? 'ĐÃ TT MỘT PHẦN' : 'CHƯA THANH TOÁN') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="mb-6 bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/30 flex items-center justify-between">
                                <span class="text-body-md text-on-surface-variant font-medium">Trạng thái bàn:</span>
                                <span class="text-label-sm font-bold uppercase tracking-wider px-3 py-1.5 rounded-md"
                                    :class="selectedTable?.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : 'bg-[#5c4d40] text-white'">
                                    {{ selectedTable?.status === 'EMPTY' ? 'BÀN TRỐNG' : 'ĐANG CÓ KHÁCH' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <button v-if="selectedTable?.status === 'EMPTY'"
                                    @click="updateTableStatus(selectedTable.id, 'OCCUPIED')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-primary text-on-primary font-bold text-label-md hover:opacity-90 shadow-sm transition-all">
                                    <span class="material-symbols-outlined text-[20px]">login</span> Khách vào bàn
                                </button>

                                <button v-if="selectedTable?.status === 'OCCUPIED'"
                                    @click="updateTableStatus(selectedTable.id, 'EMPTY')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl border-2 border-outline-variant font-bold text-on-surface hover:bg-surface-container transition-colors text-label-md">
                                    <span class="material-symbols-outlined text-[20px]">cleaning_services</span> Khách về - Dọn bàn
                                </button>
                            </div>

                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </StaffLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>