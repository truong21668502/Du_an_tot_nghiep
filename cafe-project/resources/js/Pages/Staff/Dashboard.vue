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

// logic đơn hàng
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

const cancelOrder = (orderId) => {
    if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?')) {
        router.patch(route('staff.orders.cancel', orderId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Xóa đơn khỏi danh sách hiển thị
                orders.value = orders.value.filter(o => o.id !== orderId);
                closeOrderModal();
                toast.success(`Đã hủy đơn hàng #${orderId} thành công!`);
            }
        });
    }
};

const confirmPayment = (orderId) => {
    if (confirm('Khách đã thanh toán tiền mặt xong?')) {
        router.patch(route('staff.orders.confirm-payment', orderId), {}, {
            preserveScroll: true
        });
    }
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

// hàm tính tổng tiền của tất cả các đơn trên một bàn
const calculateTotalAmount = (tableOrders) => {
    if (!tableOrders || tableOrders.length === 0) return 0;
    return tableOrders.reduce((sum, order) => sum + Number(order.final_amount || 0), 0);
};

// logic quản lý bàn
const tables = ref(props.initialTables || []);
const selectedTable = ref(null);
const isTableModalOpen = ref(false);

// gom nhóm bàn theo khu vực
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

// cập nhật trạng thái bàn trống hoặc có khách
const updateTableStatus = (tableId, newStatus) => {
    // Nếu nhân viên định dọn bàn (chuyển về EMPTY)
    if (newStatus === 'EMPTY') {
        const table = tables.value.find(t => t.id === tableId);
        
        // Kiểm tra xem bàn này có đơn nào chưa thanh toán (PENDING) không
        const hasUnpaidOrders = table?.orders?.some(o => o.payment?.payment_status !== 'PAID');
        
        if (hasUnpaidOrders) {
            toast.error('❌ KHÔNG THỂ DỌN BÀN: Bàn này vẫn còn đơn hàng chưa thanh toán!');
            return;
        }
        
        if (!confirm('Bạn có chắc chắn khách đã về và muốn dọn bàn này?')) {
            return;
        }
    }

    // Qua được các kiểm tra, tiến hành gọi API để cập nhật trạng thái bàn
    router.patch(route('staff.tables.update-status', tableId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            const table = tables.value.find(t => t.id === tableId);
            if (table) {
                table.status = newStatus;
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
                    
                    if (isTableModalOpen.value && selectedTable.value?.id === e.id) {
                        selectedTable.value.status = e.status;
                    }
                }
            });

        // kênh lắng nghe khi có đơn hàng mới
        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => { 
                if (e.order) {
                    // thêm vào danh sách đơn đang hoạt động bên trái
                    const exists = orders.value.some(o => o.id === e.order.id);
                    if (!exists) {
                        orders.value.push(e.order);
                        toast.success(`🔔 CÓ ĐƠN HÀNG MỚI! (#${e.order.id})`, {
                            position: "top-right",
                            autoClose: 4000, 
                        });
                    }

                    // đẩy dồn đơn hàng vào sơ đồ bàn bên phải
                    if (e.order.table_id) {
                        const tableIndex = tables.value.findIndex(t => t.id === e.order.table_id);
                        if (tableIndex !== -1) {
                            tables.value[tableIndex].status = 'OCCUPIED';
                            
                            if (!tables.value[tableIndex].orders) {
                                tables.value[tableIndex].orders = [];
                            }
                            const orderExistsInTable = tables.value[tableIndex].orders.some(o => o.id === e.order.id);
                            if (!orderExistsInTable) {
                                tables.value[tableIndex].orders.push(e.order);
                            }
                        }
                    }
                }
            });

        // kênh lắng nghe sự kiện khi có đơn hàng được thanh toán
        window.Echo.channel('staff-orders')
            .listen('.order.payment-confirmed', (e) => {
                // Tìm đơn hàng trong danh sách đang hiển thị
                const index = orders.value.findIndex(o => o.id === e.id);
                if (index !== -1) {
                    orders.value[index].payment.payment_status = e.payment_status;
                    orders.value[index].status = e.status;
                    
                    // Hoặc nếu bạn muốn ẩn luôn đơn đó đi vì đã xong:
                    // orders.value.splice(index, 1);
                    
                    toast.success(`Đơn ${e.order_code} đã thanh toán xong!`);
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
                                <span v-if="order?.status === 'PENDING'" class="text-label-sm font-bold text-error bg-error-container text-on-error-container px-3 py-1 rounded-full whitespace-nowrap">Chờ xử lý</span>
                                <span v-else-if="order?.status === 'PROCESSING'" class="text-label-sm font-bold text-primary bg-primary-container text-on-primary-container px-3 py-1 rounded-full whitespace-nowrap">Đang xử lý</span>
                            </div>
                            <p class="text-body-md text-on-surface-variant truncate">
                                <span class="font-bold text-primary">{{ order?.details?.length || 0 }} món:</span>
                                <span v-for="(detail, index) in order?.details" :key="detail.id">
                                    {{ detail?.product?.product_name }}<span v-if="index < order.details.length - 1">, </span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="xl:col-span-1">
                <h3 class="text-headline-sm font-bold text-on-background mb-6">Trạng Thái Bàn</h3>
                <div class="bg-surface-container-lowest rounded-xl p-5 lg:p-6 shadow-soft border border-outline-variant/30">
                    
                    <div v-for="(areaTables, areaName) in groupedTables" :key="areaName" class="mb-8 last:mb-0">
                        <h4 class="text-label-sm font-bold text-on-surface-variant uppercase tracking-widest border-b border-outline-variant/20 pb-2 mb-4">
                            {{ areaName }}
                        </h4>
                        
                        <div class="grid grid-cols-4 gap-2 lg:gap-3">
                            <div v-for="table in areaTables" :key="table.id" @click="openTableDetails(table)"
                                class="aspect-square p-2 rounded-xl flex flex-col justify-between items-center cursor-pointer transition-all border relative shadow-sm overflow-hidden"
                                :class="table?.status === 'OCCUPIED' 
                                    ? 'bg-primary-container/20 border-primary text-primary hover:bg-primary-container/30' 
                                    : 'bg-surface-container-lowest border-outline-variant/30 hover:bg-surface-variant text-on-surface-variant'">

                                <div class="flex-1 flex items-center justify-center w-full px-1">
                                    <span class="font-serif font-bold text-[13px] xl:text-[15px] leading-tight text-center break-words line-clamp-3"
                                            :class="table?.status === 'EMPTY' ? 'text-on-surface' : 'text-primary'">
                                        {{ table?.table_name?.replace('Bàn ', '') }}
                                    </span>
                                </div>
                                
                                <span class="text-[9px] xl:text-[10px] uppercase tracking-wider font-bold w-full text-center mt-1 pb-1"
                                        :class="table?.status === 'EMPTY' ? 'text-on-surface-variant' : 'text-primary'">
                                    {{ table?.status === 'OCCUPIED' ? 'Có khách' : 'Trống' }}
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
                    <div v-if="isOrderModalOpen" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">

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
                                    <p class="text-headline-sm font-bold text-on-surface">{{ selectedOrder?.table ? selectedOrder.table.table_name : 'Khách mang đi' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-label-sm text-outline mb-1 font-bold">TRẠNG THÁI ĐƠN</p>
                                    <p v-if="selectedOrder?.status === 'PENDING'" class="text-label-md text-error bg-error-container/30 px-3 py-1 rounded-md inline-block font-bold">CHỜ XỬ LÝ</p>
                                    <p v-else-if="selectedOrder?.status === 'PROCESSING'" class="text-label-md text-primary bg-primary-container/30 px-3 py-1 rounded-md inline-block font-bold">ĐANG XỬ LÝ</p>
                                </div>
                            </div>

                            <div class="border border-outline-variant/30 rounded-xl overflow-hidden">
                                <div class="bg-surface-container-low px-5 py-3 border-b border-outline-variant/30 flex justify-between items-center">
                                    <p class="text-label-sm text-on-surface-variant font-bold">DANH SÁCH MÓN</p>
                                </div>
                                <ul class="divide-y divide-outline-variant/30">
                                    <li v-for="detail in selectedOrder?.details" :key="detail.id" class="p-5 flex flex-col sm:flex-row justify-between sm:items-center gap-4 hover:bg-surface-container-low/30">
                                        <div class="flex-1">
                                            <p class="text-body-lg text-on-surface">
                                                <span class="font-bold text-primary mr-2">{{ detail?.quantity }}x</span>
                                                {{ detail?.product?.product_name }}
                                                <span class="text-body-sm text-on-surface-variant ml-1" v-if="detail.variant?.size">(Size {{ detail.variant.size }})</span>
                                            </p>
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
                                <span class="text-headline-md text-primary font-bold">{{ formatCurrency(selectedOrder?.final_amount) }}</span>
                            </div>
                        </div>

                        <div class="px-6 py-5 bg-surface border-t border-outline-variant/30 flex gap-4 justify-end">
                            <button @click="closeOrderModal" class="px-6 py-2.5 rounded-full text-label-md font-bold text-on-surface-variant hover:bg-surface-container transition-colors">Đóng lại</button>
                            
                            <button v-if="selectedOrder?.payment?.payment_method === 'CASH' && selectedOrder?.payment?.payment_status === 'PENDING'" @click="confirmPayment(selectedOrder.id)" class="px-6 py-2.5 rounded-full bg-green-600 text-white font-bold text-label-md shadow-soft hover:bg-green-700 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">payments</span> Xác nhận thu tiền
                            </button>
                            
                            <button v-if="selectedOrder?.status === 'PENDING'" @click="cancelOrder(selectedOrder.id)" class="px-6 py-2.5 rounded-full bg-error-container text-error font-bold text-label-md shadow-soft hover:bg-error/20 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">cancel</span> Hủy đơn
                            </button>

                            <button v-if="selectedOrder?.status === 'PENDING'" @click="acceptOrder(selectedOrder.id)" class="px-8 py-2.5 rounded-full bg-primary text-on-primary font-bold text-label-md shadow-soft hover:bg-primary/90 flex items-center gap-2">Tiếp nhận đơn</button>
                            <button v-else-if="selectedOrder?.status === 'PROCESSING'" @click="completeOrder(selectedOrder.id)" class="px-8 py-2.5 rounded-full bg-secondary text-on-secondary font-bold text-label-md shadow-soft hover:bg-secondary/90 flex items-center gap-2">Đã hoàn thành</button>
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
                                <h3 class="font-serif text-headline-md font-bold">{{ selectedTable?.table_name }}</h3>
                                <p class="text-label-md mt-1 opacity-80 uppercase tracking-widest">
                                    {{ selectedTable?.area }} • {{ selectedTable?.capacity }} người
                                </p>
                            </div>
                            <button @click="closeTableModal" class="p-2 hover:bg-black/10 rounded-full transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-6 bg-surface-container-lowest">
                            
                            <template v-if="selectedTable?.status === 'OCCUPIED' && selectedTable?.orders && selectedTable.orders.length > 0">
                                <div class="mb-6 bg-secondary-container/10 border border-secondary/30 p-4 rounded-xl">
                                    <div class="flex justify-between items-center mb-3">
                                        <p class="text-label-sm text-secondary font-bold tracking-wider flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px]">receipt_long</span>
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
                                                    <span class="text-on-surface font-medium">{{ detail.product?.product_name }}</span>
                                                    <span class="text-[12px] text-on-surface-variant mt-0.5">
                                                        Size: {{ detail.variant?.size || '---' }} • Đơn giá: {{ formatCurrency(detail.unit_price) }}
                                                    </span>
                                                </div>
                                                <span class="font-bold text-on-surface mt-0.5">x{{ detail.quantity }}</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-between border-b border-outline-variant/20 py-2">
                                            <span class="text-on-surface-variant">Tổng cộng:</span>
                                            <span class="font-bold text-primary text-label-lg">
                                                {{ formatCurrency(calculateTotalAmount(selectedTable.orders)) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between pt-1 items-center">
                                            <span class="text-on-surface-variant">Thanh toán:</span>
                                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider"
                                                    :class="selectedTable.orders.every(o => o.payment?.payment_status === 'PAID') ? 'bg-secondary-container text-secondary' : 'bg-error-container text-error'">
                                                {{ selectedTable.orders.every(o => o.payment?.payment_status === 'PAID') ? 'Đã TT toàn bộ' : (selectedTable.orders.some(o => o.payment?.payment_status === 'PAID') ? 'Đã TT một phần' : 'Chưa thanh toán') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="mb-6 bg-surface p-4 rounded-xl border border-outline-variant/30 flex items-center justify-between">
                                <span class="text-body-md text-on-surface-variant font-medium">Trạng thái bàn:</span>
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