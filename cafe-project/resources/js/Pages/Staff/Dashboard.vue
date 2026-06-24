<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import StatCards from './Partials/StatCards.vue';
import { toast } from "vue3-toastify";
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    initialOrders: Array,
    initialTables: Array,
});

// ================= Xử lí đơn hàng =================
const orders = ref(props.initialOrders || []);
const selectedOrder = ref(null);
const isOrderModalOpen = ref(false);

const openOrderDetails = (order) => {
    selectedOrder.value = order;
    isOrderModalOpen.value = true;
};

const closeOrderModal = () => {
    isOrderModalOpen.value = false;
    setTimeout(() => selectedOrder.value = null, 300);
};

const acceptOrder = (orderId) => {
    router.patch(route('staff.orders.accept', orderId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const index = orders.value.findIndex(o => o.id === orderId);
            if (index !== -1) orders.value[index].status = 'PROCESSING';
            if (selectedOrder.value?.id === orderId) {
                selectedOrder.value.status = 'PROCESSING';
            }
            toast.success(`Đã tiếp nhận đơn hàng #${orderId}`);
        }
    });
};

const completeOrder = (orderId) => {
    router.patch(route('staff.orders.complete', orderId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            orders.value = orders.value.filter(o => o.id !== orderId);
            closeOrderModal();
            toast.info(`Đơn hàng #${orderId} đã hoàn thành!`);
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

// ================= Xử lí quản lý bàn =================
const tables = ref(props.initialTables || []);
const selectedTable = ref(null);
const isTableModalOpen = ref(false);

const groupedTables = computed(() => {
    return tables.value.reduce((acc, table) => {
        const area = table.area || 'Khu vực khác';
        if (!acc[area]) acc[area] = [];
        acc[area].push(table);
        return acc;
    }, {});
});

const openTableDetails = (table) => {
    selectedTable.value = table;
    isTableModalOpen.value = true;
};

const closeTableModal = () => {
    isTableModalOpen.value = false;
    setTimeout(() => selectedTable.value = null, 300);
};

const updateTableStatus = (tableId, newStatus) => {
    router.patch(route('staff.tables.update-status', tableId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            const table = tables.value.find(t => t.id === tableId);
            if (table) {
                table.status = newStatus;
                // Nếu dọn bàn trống, tự động xóa mảng đơn hàng ảo trên giao diện cho sạch
                if (newStatus === 'EMPTY') table.orders = [];
            }
            closeTableModal();
            
            if (newStatus === 'OCCUPIED') {
                toast.success(`Đã xếp khách vào ${table.table_name}`);
            } else {
                toast.info(`Đã dọn dẹp ${table.table_name}`);
            }
        }
    });
};

// ================= Lắng nghe sự kiện real-time =================
onMounted(() => {
    if (window.Echo) {
        // Kênh đổi màu Bàn
        window.Echo.channel('cafe-tables')
            .listen('.TableUpdated', (e) => {
                const index = tables.value.findIndex(t => t.id === e.id);
                if (index !== -1) {
                    tables.value[index].status = e.status;
                    // Xóa thông tin đơn nếu dọn bàn
                    if (e.status === 'EMPTY') tables.value[index].orders = [];
                    
                    if (isTableModalOpen.value && selectedTable.value?.id === e.id) {
                        selectedTable.value.status = e.status;
                    }
                }
            });

        // Kênh Đơn hàng mới
        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => { 
                if (e.order) {
                    // Đẩy vào danh sách đơn bên trái
                    const exists = orders.value.some(o => o.id === e.order.id);
                    if (!exists) {
                        orders.value.push(e.order);
                        toast.success(`🔔 CÓ ĐƠN HÀNG MỚI! (#${e.order.id})`, {
                            position: "top-right",
                            autoClose: 4000, 
                        });
                    }

                    // Gắn luôn thông tin đơn vào thẻ Bàn bên phải (Cập nhật giao diện lập tức)
                    if (e.order.table_id) {
                        const tableIndex = tables.value.findIndex(t => t.id === e.order.table_id);
                        if (tableIndex !== -1) {
                            tables.value[tableIndex].status = 'OCCUPIED';
                            tables.value[tableIndex].orders = [e.order];
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
    <Head title="Bảng điều khiển - Nắng Coffee" />

    <StaffLayout>
        <div class="mb-10">
            <p class="text-label-md text-primary tracking-wider mb-2 uppercase font-bold">NẮNG COFFEE</p>
            <h2 class="text-display-lg-mobile md:text-display-lg text-on-background">Bảng điều khiển nhân viên</h2>
        </div>

        <StatCards :tables="tables" :orders="orders" />

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-10 mt-10">

            <section class="xl:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-headline-sm font-bold text-on-background flex items-center gap-2">
                        Đơn Đang Hoạt Động
                        <span v-if="orders.length > 0"
                            class="bg-primary text-on-primary text-[12px] px-2 py-0.5 rounded-full font-sans">
                            {{ orders.length }}
                        </span>
                    </h3>
                </div>

                <div v-if="orders.length === 0"
                    class="bg-surface-container-lowest rounded-xl p-8 shadow-soft flex flex-col items-center justify-center border border-outline-variant/30 text-center min-h-[250px]">
                    <span class="material-symbols-outlined text-outline text-[48px] mb-3">coffee</span>
                    <p class="text-body-md text-on-surface-variant">Không có đơn hàng nào cần xử lý.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="order in orders" :key="order.id" @click="openOrderDetails(order)"
                        class="bg-surface-container-lowest rounded-xl p-5 shadow-soft flex flex-col justify-between border-t-4 hover:bg-surface-container-low transition-colors cursor-pointer"
                        :class="order?.status === 'PENDING' ? 'border-error' : 'border-primary'">
                        <div>
                            <div class="flex items-center gap-4 mb-3 border-b border-outline-variant/30 pb-3">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-headline-sm font-bold"
                                    :class="order?.status === 'PENDING' ? 'bg-error-container/30 text-error' : 'bg-primary-container/30 text-primary'">
                                    #{{ order?.id }}
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-label-md font-bold text-on-surface truncate">
                                        {{ order?.table ? order.table.table_name : 'Mang đi / Giao hàng' }}
                                    </h4>
                                    <p class="text-label-sm text-on-surface-variant mt-1">{{ order?.order_type }}</p>
                                </div>
                                <span v-if="order?.status === 'PENDING'"
                                    class="text-label-sm font-bold text-error bg-error-container text-on-error-container px-3 py-1 rounded-full whitespace-nowrap">
                                    Chờ xử lý
                                </span>
                                <span v-else-if="order?.status === 'PROCESSING'"
                                    class="text-label-sm font-bold text-primary bg-primary-container text-on-primary-container px-3 py-1 rounded-full whitespace-nowrap">
                                    Đang xử lý
                                </span>
                            </div>
                            <p class="text-body-md text-on-surface-variant truncate">
                                <span class="font-bold text-primary">{{ order?.order_details?.length || 0 }} món:</span>
                                <span v-for="(detail, index) in order?.order_details" :key="detail.id">
                                    {{ detail?.product?.product_name }}
                                    <span v-if="index < order.order_details.length - 1">, </span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="xl:col-span-1">
                <h3 class="text-headline-sm font-bold text-on-background mb-6">Trạng Thái Bàn</h3>
                <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft border border-outline-variant/30">
                    
                    <div v-for="(areaTables, areaName) in groupedTables" :key="areaName" class="mb-8 last:mb-0">
                        <h4 class="text-label-sm font-bold text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/20 pb-2 mb-4">
                            {{ areaName }}
                        </h4>
                        
                        <div class="grid grid-cols-4 gap-3">
                            <div v-for="table in areaTables" :key="table.id" @click="openTableDetails(table)"
                                class="aspect-square p-3 rounded-2xl flex flex-col justify-between items-start cursor-pointer transition-all border relative shadow-sm"
                                :class="table?.status === 'OCCUPIED' 
                                    ? 'bg-primary-container/20 border-primary text-primary hover:bg-primary-container/30' 
                                    : 'bg-surface-container-lowest border-outline-variant/30 hover:bg-surface-variant text-on-surface-variant'">

                                <span class="font-serif font-bold text-[15px] xl:text-[16px] leading-snug text-left"
                                        :class="table?.status === 'EMPTY' ? 'text-on-surface' : 'text-primary'">
                                    {{ table?.table_name?.replace('Bàn ', '') }}
                                </span>
                                
                                <span class="text-[9px] lg:text-[10px] uppercase tracking-wider font-bold w-full text-center mt-2"
                                        :class="table?.status === 'EMPTY' ? 'text-on-surface-variant' : 'text-primary'">
                                    {{ table?.status === 'OCCUPIED' ? 'Đang dùng' : 'Trống' }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>

        <Transition name="fade">
            <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeOrderModal"></div>
                <Transition name="slide-up">
                    <div v-if="isOrderModalOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">

                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-start bg-surface">
                            <div>
                                <h3 class="text-headline-md font-bold text-on-background">Chi tiết đơn #{{ selectedOrder?.id }}</h3>
                                <p class="text-label-md text-on-surface-variant mt-1">{{ selectedOrder?.order_type }}</p>
                            </div>
                            <button @click="closeOrderModal" class="p-2 text-on-surface-variant hover:bg-surface-container hover:text-error rounded-full transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto flex-1 bg-surface-container-lowest hide-scrollbar">
                            <div class="flex justify-between mb-8 p-4 bg-surface rounded-xl border border-outline-variant/30">
                                <div>
                                    <p class="text-label-sm text-outline mb-1 font-bold">VỊ TRÍ / KHÁCH</p>
                                    <p class="text-headline-sm font-bold text-on-surface">
                                        {{ selectedOrder?.table ? selectedOrder.table.table_name : 'Khách mang đi' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-label-sm text-outline mb-1 font-bold">TRẠNG THÁI ĐƠN</p>
                                    <p v-if="selectedOrder?.status === 'PENDING'"
                                        class="text-label-md text-error bg-error-container/30 px-3 py-1 rounded-md inline-block font-bold">
                                        CHỜ XỬ LÝ
                                    </p>
                                    <p v-else-if="selectedOrder?.status === 'PROCESSING'"
                                        class="text-label-md text-primary bg-primary-container/30 px-3 py-1 rounded-md inline-block font-bold">
                                        ĐANG XỬ LÝ
                                    </p>
                                </div>
                            </div>

                            <div class="border border-outline-variant/30 rounded-xl overflow-hidden">
                                <div class="bg-surface-container-low px-5 py-3 border-b border-outline-variant/30 flex justify-between items-center">
                                    <p class="text-label-sm text-on-surface-variant font-bold">DANH SÁCH MÓN</p>
                                    <p class="text-label-sm text-on-surface-variant hidden sm:block font-bold">TRẠNG THÁI PHA CHẾ</p>
                                </div>
                                <ul class="divide-y divide-outline-variant/30">
                                    <li v-for="detail in selectedOrder?.order_details" :key="detail.id"
                                        class="p-5 flex flex-col sm:flex-row justify-between sm:items-center gap-4 hover:bg-surface-container-low/30">
                                        <div class="flex-1">
                                            <p class="text-body-lg text-on-surface">
                                                <span class="font-bold text-primary mr-2">{{ detail?.quantity }}x</span>
                                                {{ detail?.product?.product_name }}
                                            </p>
                                            <div v-if="detail?.note" class="mt-2 flex items-start gap-1 text-tertiary bg-surface-container px-3 py-1.5 rounded-lg border border-outline-variant/20 inline-block">
                                                <span class="material-symbols-outlined text-[16px] mt-0.5">edit_note</span>
                                                <span class="text-label-sm italic">{{ detail.note }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-5 justify-between sm:justify-end">
                                            <span class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border"
                                                :class="{
                                                    'bg-surface-dim border-outline/20 text-on-surface-variant': detail?.barista_status === 'PENDING',
                                                    'bg-secondary-container border-secondary/20 text-on-secondary-container': detail?.barista_status === 'PREPARING',
                                                    'bg-primary-container border-primary/20 text-on-primary-container': detail?.barista_status === 'COMPLETED',
                                                    'bg-error-container border-error/20 text-on-error-container': detail?.barista_status === 'CANCELLED'
                                                }">
                                                {{ detail?.barista_status === 'PENDING' ? 'Chờ pha' : detail?.barista_status === 'PREPARING' ? 'Đang làm' : detail?.barista_status === 'COMPLETED' ? 'Đã xong' : 'Đã hủy' }}
                                            </span>
                                            <span class="text-label-md font-bold text-on-surface-variant w-20 text-right">
                                                {{ formatCurrency(detail?.unit_price * detail?.quantity) }}
                                            </span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-8 flex justify-between items-center p-4 bg-surface rounded-xl border border-outline-variant/30">
                                <span class="text-body-lg text-on-surface-variant font-medium">Tổng thanh toán:</span>
                                <span class="text-headline-md text-primary font-bold">
                                    {{ formatCurrency(selectedOrder?.final_amount) }}
                                </span>
                            </div>
                        </div>

                        <div class="px-6 py-5 bg-surface border-t border-outline-variant/30 flex gap-4 justify-end">
                            <button @click="closeOrderModal" class="px-6 py-2.5 rounded-full text-label-md font-bold text-on-surface-variant hover:bg-surface-container transition-colors">
                                Đóng lại
                            </button>
                            <button v-if="selectedOrder?.status === 'PENDING'" @click="acceptOrder(selectedOrder.id)" class="px-8 py-2.5 rounded-full bg-primary text-on-primary font-bold text-label-md shadow-soft hover:bg-primary/90 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">task_alt</span> Tiếp nhận đơn
                            </button>
                            <button v-else-if="selectedOrder?.status === 'PROCESSING'" @click="completeOrder(selectedOrder.id)" class="px-8 py-2.5 rounded-full bg-secondary text-on-secondary font-bold text-label-md shadow-soft hover:bg-secondary/90 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">done_all</span> Đã hoàn thành
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="isTableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeTableModal"></div>
                <Transition name="slide-up">
                    <div v-if="isTableModalOpen" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col">

                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-center"
                            :class="selectedTable?.status === 'OCCUPIED' ? 'bg-primary-container text-on-primary-container' : 'bg-surface text-on-surface'">
                            <div>
                                <h3 class="text-headline-md font-bold">{{ selectedTable?.table_name }}</h3>
                                <p class="text-label-md mt-1 opacity-80">
                                    {{ selectedTable?.area }} • Sức chứa: {{ selectedTable?.capacity }} người
                                </p>
                            </div>
                            <button @click="closeTableModal" class="p-2 hover:bg-black/10 rounded-full transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-6 bg-surface-container-lowest">

                            <div class="mb-6 bg-surface p-4 rounded-xl border border-outline-variant/30 flex items-center justify-between">
                                <span class="text-body-md text-on-surface-variant font-medium">Tình trạng:</span>
                                <span class="text-label-md font-bold uppercase tracking-wider px-3 py-1 rounded-md"
                                    :class="selectedTable?.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : 'bg-primary text-on-primary'">
                                    {{ selectedTable?.status === 'EMPTY' ? 'Bàn trống' : 'Đang có khách' }}
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