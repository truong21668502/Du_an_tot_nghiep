<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import StatCards from './Partials/StatCards.vue';

const props = defineProps({
    initialOrders: Array,
    initialTables: Array,
});

// ================= LOGIC ĐƠN HÀNG =================
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
            if (selectedOrder.value && selectedOrder.value.id === orderId) {
                selectedOrder.value.status = 'PROCESSING';
            }
        }
    });
};

const completeOrder = (orderId) => {
    router.patch(route('staff.orders.complete', orderId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            orders.value = orders.value.filter(o => o.id !== orderId);
            closeOrderModal();
        }
    });
};

// ================= LOGIC QUẢN LÝ BÀN & ĐẶT BÀN =================
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

// Cập nhật trạng thái BÀN chung (Trống / Đang dùng)
const updateTableStatus = (tableId, newStatus) => {
    router.patch(route('staff.tables.update-status', tableId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            const table = tables.value.find(t => t.id === tableId);
            if (table) table.status = newStatus;
            closeTableModal();
        }
    });
};

// Cập nhật trạng thái PHIẾU ĐẶT BÀN (Xác nhận / Từ chối)
const updateReservationStatus = (reservationId, newStatus) => {
    router.patch(route('staff.reservations.update-status', reservationId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            // Cập nhật giao diện local ngay lập tức
            if (selectedTable.value && selectedTable.value.reservations) {
                // Cập nhật chữ trong phiếu
                const resIndex = selectedTable.value.reservations.findIndex(r => r.id === reservationId);
                if (resIndex !== -1) {
                    selectedTable.value.reservations[resIndex].status = newStatus;
                }
                
                // Cập nhật màu sắc của bàn ở sơ đồ bên ngoài
                if (newStatus === 'CANCELLED') {
                    selectedTable.value.status = 'EMPTY';
                } else if (newStatus === 'CONFIRMED') {
                    selectedTable.value.status = 'RESERVED';
                }
            }
            
            // Đóng Modal sau khi xử lý xong
            closeTableModal();
        }
    });
};

// Lọc phiếu đặt bàn đang hoạt động
const activeReservation = computed(() => {
    if (!selectedTable.value?.reservations) return null;
    return selectedTable.value.reservations.find(r => r.status === 'PENDING' || r.status === 'CONFIRMED') || selectedTable.value.reservations[0];
});

// Format hiển thị
const formatTime = (dateTimeString) => {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) + ' - ' + date.toLocaleDateString('vi-VN');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

// ================= Lắng nghe sự kiện real-time =================
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('staff-orders')
            .listen('.OrderCreated', (e) => {
                orders.value.push(e.order);
            });

        window.Echo.channel('cafe-tables')
            .listen('.TableUpdated', (e) => {
                const index = tables.value.findIndex(t => t.id === e.id);
                if (index !== -1) {
                    tables.value[index].status = e.status;
                    if (e.reservations) {
                        tables.value[index].reservations = e.reservations;
                    }
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel('staff-orders');
        window.Echo.leaveChannel('cafe-tables');
    }
});
</script>

<template>
    <Head title="Bảng điều khiển - Nắng Coffee" />

    <StaffLayout>
        <div class="mb-10">
        <p class="text-label-md text-primary tracking-wider mb-2">NẮNG COFFEE</p>
        <h2 class="text-display-lg-mobile md:text-display-lg text-on-background">Bảng điều khiển nhân viên</h2>
        </div>

        <!-- Truyền biến tables và orders vào component StatCards -->
        <StatCards :tables="tables" :orders="orders"/>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
            
            <!-- ================= DANH SÁCH ĐƠN HÀNG ================= -->
            <section class="xl:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-headline-sm text-on-background flex items-center gap-2">
                        Đơn Đang Hoạt Động 
                        <span v-if="orders.length > 0" class="bg-primary text-on-primary text-[12px] px-2 py-0.5 rounded-full font-sans">{{ orders.length }}</span>
                    </h3>
                </div>
                
                <div v-if="orders.length === 0" class="bg-surface-container-lowest rounded-xl p-8 shadow-soft flex flex-col items-center justify-center border border-outline-variant/30 text-center">
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
                                    <h4 class="text-label-md text-on-surface truncate">{{ order?.table ? order.table.table_name : 'Mang đi / Giao hàng' }}</h4>
                                    <p class="text-label-sm text-on-surface-variant mt-1">{{ order?.order_type }}</p>
                                </div>
                                <span v-if="order?.status === 'PENDING'" class="text-label-sm text-error bg-error-container text-on-error-container px-3 py-1 rounded-full whitespace-nowrap">Chờ xử lý</span>
                                <span v-else-if="order?.status === 'PROCESSING'" class="text-label-sm text-primary bg-primary-container text-on-primary-container px-3 py-1 rounded-full whitespace-nowrap">Đang xử lý</span>
                            </div>
                            <p class="text-body-md text-on-surface-variant truncate">
                                <span class="font-bold text-primary">{{ order?.order_details?.length || 0 }} món:</span>
                                <span v-for="(detail, index) in order?.order_details" :key="detail.id">
                                    {{ detail?.product?.product_name }}<span v-if="index < order.order_details.length - 1">, </span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ================= SƠ ĐỒ TRẠNG THÁI BÀN (4 CỘT) ================= -->
            <section class="xl:col-span-1">
                <h3 class="text-headline-sm text-on-background mb-6">Trạng Thái Bàn</h3>
                <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft border border-outline-variant/30">
                    <div v-for="(areaTables, areaName) in groupedTables" :key="areaName" class="mb-8 last:mb-0">
                        <h4 class="text-label-md text-outline mb-4 border-b border-outline-variant/30 pb-2">{{ areaName }}</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div v-for="table in areaTables" :key="table.id" @click="openTableDetails(table)"
                                :class="[
                                    'aspect-square rounded-xl flex flex-col items-center justify-center cursor-pointer transition-all border-2',
                                    table?.status === 'EMPTY' ? 'bg-surface border-transparent hover:bg-surface-container-high text-on-surface-variant' : '',
                                    table?.status === 'OCCUPIED' ? 'bg-primary-container/20 border-primary text-primary shadow-sm' : '',
                                    table?.status === 'RESERVED' ? 'bg-secondary-container border-transparent text-on-secondary-container shadow-sm font-bold' : ''
                                ]">
                                <span class="text-headline-sm font-bold">{{ table?.table_name?.replace('Bàn ', '') }}</span>
                                <span class="text-[10px] mt-1 uppercase tracking-wider font-semibold">
                                    {{ table?.status === 'EMPTY' ? 'Trống' : (table?.status === 'OCCUPIED' ? 'Đang dùng' : 'Đã đặt') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ================= MODAL CHI TIẾT ĐƠN HÀNG ================= -->
        <Transition name="fade">
            <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeOrderModal"></div>
                <Transition name="slide-up">
                    <div v-if="isOrderModalOpen" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                        
                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-start bg-surface">
                            <div>
                                <h3 class="text-headline-md text-on-background">Chi tiết đơn #{{ selectedOrder?.id }}</h3>
                                <p class="text-label-md text-on-surface-variant mt-1">{{ selectedOrder?.order_type }}</p>
                            </div>
                            <button @click="closeOrderModal" class="p-2 text-on-surface-variant hover:bg-surface-container hover:text-error rounded-full transition-colors"><span class="material-symbols-outlined">close</span></button>
                        </div>

                        <div class="p-6 overflow-y-auto flex-1 bg-surface-container-lowest hide-scrollbar">
                            <div class="flex justify-between mb-8 p-4 bg-surface rounded-xl border border-outline-variant/30">
                                <div>
                                    <p class="text-label-sm text-outline mb-1">VỊ TRÍ / KHÁCH</p>
                                    <p class="text-headline-sm font-bold text-on-surface">{{ selectedOrder?.table ? selectedOrder.table.table_name : 'Khách mang đi' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-label-sm text-outline mb-1">TRẠNG THÁI ĐƠN</p>
                                    <p v-if="selectedOrder?.status === 'PENDING'" class="text-label-md text-error bg-error-container/30 px-3 py-1 rounded-md inline-block font-bold">CHỜ XỬ LÝ</p>
                                    <p v-else-if="selectedOrder?.status === 'PROCESSING'" class="text-label-md text-primary bg-primary-container/30 px-3 py-1 rounded-md inline-block font-bold">ĐANG XỬ LÝ</p>
                                </div>
                            </div>

                            <div class="border border-outline-variant/30 rounded-xl overflow-hidden">
                                <div class="bg-surface-container-low px-5 py-3 border-b border-outline-variant/30 flex justify-between items-center">
                                    <p class="text-label-sm text-on-surface-variant">DANH SÁCH MÓN</p>
                                    <p class="text-label-sm text-on-surface-variant hidden sm:block">TRẠNG THÁI PHA CHẾ</p>
                                </div>
                                <ul class="divide-y divide-outline-variant/30">
                                    <li v-for="detail in selectedOrder?.order_details" :key="detail.id" class="p-5 flex flex-col sm:flex-row justify-between sm:items-center gap-4 hover:bg-surface-container-low/30">
                                        <div class="flex-1">
                                            <p class="text-body-lg text-on-surface"><span class="font-bold text-primary mr-2">{{ detail?.quantity }}x</span> {{ detail?.product?.product_name }}</p>
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
                                            <span class="text-label-md text-on-surface-variant w-20 text-right">{{ formatCurrency(detail?.unit_price * detail?.quantity) }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-8 flex justify-between items-center p-4 bg-surface rounded-xl border border-outline-variant/30">
                                <span class="text-body-lg text-on-surface-variant font-medium">Tổng thanh toán:</span>
                                <span class="text-headline-md text-primary font-bold">{{ formatCurrency(selectedOrder?.final_amount) }}</span>
                            </div>
                        </div>

                        <div class="px-6 py-5 bg-surface border-t border-outline-variant/30 flex gap-4 justify-end">
                            <button @click="closeOrderModal" class="px-6 py-2.5 rounded-full text-label-md text-on-surface-variant hover:bg-surface-container transition-colors">Đóng lại</button>
                            <button v-if="selectedOrder?.status === 'PENDING'" @click="acceptOrder(selectedOrder.id)" class="px-8 py-2.5 rounded-full bg-primary text-on-primary text-label-md shadow-soft hover:bg-primary/90 flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">task_alt</span> Tiếp nhận đơn</button>
                            <button v-else-if="selectedOrder?.status === 'PROCESSING'" @click="completeOrder(selectedOrder.id)" class="px-8 py-2.5 rounded-full bg-secondary text-on-secondary text-label-md shadow-soft hover:bg-secondary/90 flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">done_all</span> Đã hoàn thành</button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- ================= MODAL CHI TIẾT BÀN (TÍCH HỢP ĐẶT BÀN ĐẦY ĐỦ) ================= -->
        <Transition name="fade">
            <div v-if="isTableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeTableModal"></div>
                <Transition name="slide-up">
                    <div v-if="isTableModalOpen" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
                        
                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-center" :class="[
                            selectedTable?.status === 'EMPTY' ? 'bg-surface text-on-surface' : '',
                            selectedTable?.status === 'OCCUPIED' ? 'bg-primary-container text-on-primary-container' : '',
                            selectedTable?.status === 'RESERVED' ? 'bg-secondary-container text-on-secondary-container' : ''
                        ]">
                            <div>
                                <h3 class="text-headline-md font-bold">{{ selectedTable?.table_name }}</h3>
                                <p class="text-label-md mt-1 opacity-80">{{ selectedTable?.area }} • Sức chứa: {{ selectedTable?.capacity }} người</p>
                            </div>
                            <button @click="closeTableModal" class="p-2 hover:bg-black/10 rounded-full transition-colors"><span class="material-symbols-outlined">close</span></button>
                        </div>

                        <div class="p-6 bg-surface-container-lowest max-h-[75vh] overflow-y-auto hide-scrollbar">
                            
                            <!-- HIỂN THỊ THÔNG TIN KHÁCH ĐẶT BÀN -->
                            <div v-if="selectedTable?.status === 'RESERVED' && activeReservation" class="mb-6 space-y-4">
                                <div class="bg-secondary-container/10 border-2 border-dashed border-secondary/30 rounded-xl p-4">
                                    <p class="text-label-sm text-secondary font-bold tracking-wider mb-3 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">bookmark_heart</span> THÔNG TIN ĐẶT TRƯỚC
                                    </p>
                                    
                                    <div class="grid grid-cols-1 gap-3 text-body-md">
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Tên người đặt:</span>
                                            <span class="font-bold text-on-surface">{{ activeReservation?.user?.full_name || '---' }}</span>
                                        </div>
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Số điện thoại:</span>
                                            <span class="font-bold text-primary">{{ activeReservation?.phone_number || '---' }}</span>
                                        </div>
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Giờ đặt bàn:</span>
                                            <span class="font-bold text-error">{{ formatTime(activeReservation?.reservation_time) }}</span>
                                        </div>
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Trạng thái phiếu:</span>
                                            <span class="text-label-sm font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
                                                :class="activeReservation?.status === 'PENDING' ? 'bg-error text-on-error' : 'bg-secondary text-on-secondary'">
                                                {{ activeReservation?.status === 'PENDING' ? 'Chờ xác nhận' : 'Đã xác nhận' }}
                                            </span>
                                        </div>
                                        <div class="pt-1">
                                            <span class="text-on-surface-variant text-label-sm block mb-1">Ghi chú từ khách:</span>
                                            <p class="text-body-md italic text-on-surface bg-surface p-2.5 rounded-lg border border-outline-variant/30 min-h-[40px]">
                                                " {{ activeReservation?.note || 'Không có ghi chú.' }} "
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- HIỂN THỊ TRẠNG THÁI HIỆN TẠI NẾU KHÔNG CÓ ĐẶT BÀN -->
                            <div v-else class="mb-6 bg-surface p-4 rounded-xl border border-outline-variant/30 flex items-center justify-between">
                                <span class="text-body-md text-on-surface-variant">Trạng thái bàn hiện tại:</span>
                                <span class="text-label-md font-bold uppercase tracking-wider px-3 py-1 rounded-md"
                                    :class="selectedTable?.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : (selectedTable?.status === 'RESERVED' ? 'bg-secondary-container text-secondary' : 'bg-primary-container text-primary')">
                                    {{ selectedTable?.status === 'EMPTY' ? 'Bàn trống' : (selectedTable?.status === 'RESERVED' ? 'Đã đặt (Đang tải dữ liệu...)' : 'Đang có khách') }}
                                </span>
                            </div>

                            <!-- CÁC HÀNH ĐỘNG ĐIỀU KHIỂN TRẠNG THÁI -->
                            <div class="space-y-3">
                                
                                <!-- Bàn có phiếu đặt mới (Chờ xác nhận) -->
                                <div v-if="selectedTable?.status === 'RESERVED' && activeReservation?.status === 'PENDING'" class="flex gap-3">
                                    <button 
                                        @click="updateReservationStatus(activeReservation.id, 'CANCELLED')"
                                        class="flex-1 py-3.5 rounded-xl border-2 border-error text-error font-bold hover:bg-error-container transition-colors flex items-center justify-center gap-2"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">cancel</span> Từ chối
                                    </button>
                                    <button 
                                        @click="updateReservationStatus(activeReservation.id, 'CONFIRMED')"
                                        class="flex-1 py-3.5 rounded-xl bg-primary text-on-primary font-bold hover:opacity-90 transition-opacity shadow-sm flex items-center justify-center gap-2"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">check_circle</span> Xác nhận lịch
                                    </button>
                                </div>

                                <!-- Đã xác nhận -> Cho phép vào bàn -->
                                <button 
                                    v-else-if="selectedTable?.status === 'RESERVED' && activeReservation?.status === 'CONFIRMED'"
                                    @click="updateTableStatus(selectedTable.id, 'OCCUPIED')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-secondary text-on-secondary text-label-md hover:opacity-90 shadow-sm font-bold"
                                >
                                    <span class="material-symbols-outlined text-[20px]">how_to_reg</span> Khách đã đến (Vào bàn)
                                </button>

                                <!-- Bàn đang trống -> Khách vãng lai -->
                                <button 
                                    v-else-if="selectedTable?.status === 'EMPTY'"
                                    @click="updateTableStatus(selectedTable.id, 'OCCUPIED')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-primary text-on-primary text-label-md hover:opacity-90 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-[20px]">login</span> Khách vãng lai vào bàn
                                </button>

                                <!-- Dọn bàn (Cho cả bàn đang dùng hoặc muốn hủy trạng thái đặt chỗ) -->
                                <button 
                                    v-if="selectedTable?.status !== 'EMPTY'"
                                    @click="updateTableStatus(selectedTable.id, 'EMPTY')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl border-2 border-outline-variant text-on-surface hover:bg-surface-container transition-colors text-label-md mt-3"
                                >
                                    <span class="material-symbols-outlined text-[20px]">cleaning_services</span> 
                                    {{ selectedTable?.status === 'OCCUPIED' ? 'Khách về - Dọn bàn (Trống)' : 'Hủy trạng thái bàn' }}
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
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(20px) scale(0.95); }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>