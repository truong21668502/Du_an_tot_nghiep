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
// hàm in hóa đơn
const printBill = (table) => {
    if (!table || !table.orders || table.orders.length === 0) {
        toast.error('Không có đơn hàng nào để in!');
        return;
    }
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    const total = calculateTotalAmount(table.orders);
    const items = getGroupedOrderDetails(table.orders);
    const now = new Date().toLocaleString('vi-VN');

    let itemsHtml = items.map(item => `
        <tr>
            <td style="padding: 6px 0; border-bottom: 1px dashed #ccc;">
                <div style="font-weight: bold;">${item.product?.product_name}</div>
                <div style="font-size: 12px; color: #555;">Size: ${item.variant?.size || '---'}</div>
            </td>
            <td style="padding: 6px 0; text-align: center; border-bottom: 1px dashed #ccc;">${item.quantity}</td>
            <td style="padding: 6px 0; text-align: right; border-bottom: 1px dashed #ccc;">${formatCurrency(item.unit_price * item.quantity)}</td>
        </tr>
    `).join('');

    const html = `
        <html>
            <head>
                <title>In hóa đơn - ${table.table_name}</title>
                <style>
                    body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 0 auto; padding: 20px 10px; color: #000; }
                    h2 { text-align: center; margin: 0 0 5px 0; font-size: 22px; text-transform: uppercase; }
                    p { text-align: center; margin: 0 0 10px 0; font-size: 13px; }
                    .divider { border-top: 1px dashed #000; margin: 12px 0; }
                    table { width: 100%; border-collapse: collapse; font-size: 14px; }
                    th { text-align: left; border-bottom: 1px dashed #000; padding-bottom: 6px; }
                    th.center { text-align: center; }
                    th.right { text-align: right; }
                    .total-row { font-weight: bold; font-size: 18px; margin-top: 10px; display: flex; justify-content: space-between; }
                    .footer { text-align: center; margin-top: 25px; font-size: 12px; font-style: italic; }
                </style>
            </head>
            <body>
                <h2>Nắng Coffee</h2>
                <p>137 Nguyễn Thị Thập, Liên Chiểu, Đà Nẵng</p>
                <div class="divider"></div>
                <p style="text-align: left; font-size: 14px;">
                    <strong>Bàn: ${table.table_name}</strong><br/>
                    Thời gian: ${now}
                </p>
                <div class="divider"></div>
                <table>
                    <thead>
                        <tr>
                            <th>Món</th>
                            <th class="center">SL</th>
                            <th class="right">TT</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="divider"></div>
                <div class="total-row">
                    <span>Tổng cộng:</span>
                    <span>${formatCurrency(total)}</span>
                </div>
                <div class="divider"></div>
                <div class="footer">
                    Cảm ơn quý khách và hẹn gặp lại!<br/>
                    Wifi: NangCoffee / Pass: 12345678
                </div>
            </body>
        </html>
    `;
    printWindow.document.write(html);
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
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

// cập nhật trạng thái bàn
const updateTableStatus = (tableId, newStatus) => {
    if (newStatus === 'EMPTY') {
        const table = tables.value.find(t => t.id === tableId);
        const hasUnpaidOrders = table?.orders?.some(o => o.payment?.payment_status !== 'PAID');
        if (hasUnpaidOrders) {
            toast.error('❌ KHÔNG THỂ DỌN BÀN: Bàn này vẫn còn đơn hàng chưa thanh toán!');
            return;
        }
        if (!confirm('Bạn có chắc chắn khách đã về và muốn dọn bàn này?')) {
            return;
        }
    }

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

// Thống kê Barista Hub
const emptyTablesCount = computed(() => tables.value.filter(t => t.status === 'EMPTY').length);
const occupiedTablesCount = computed(() => tables.value.filter(t => t.status === 'OCCUPIED').length);
const pendingOrdersCount = computed(() => orders.value.filter(o => o.status === 'PENDING').length);

const formatCurrencyShort = (value) => {
    if (!value) return '0đ';
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'tr';
    if (value >= 1000) return (value / 1000) + 'k';
    return value + 'đ';
};



const getTableNumber = (tableName) => {
    return tableName ? tableName.replace(/[^\d]/g, '') : '';
};

const tableHasPendingOrder = (table) => {
    if (!table || !table.orders) return false;
    return table.orders.some(o => o.status === 'PENDING');
};


// lắng nghe sự kiện real-time
onMounted(() => {
    if (window.Echo) {
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

        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => {
                if (e.order) {
                    const exists = orders.value.some(o => o.id === e.order.id);
                    if (!exists) {
                        orders.value.push(e.order);
                        toast.success(`🔔 CÓ ĐƠN HÀNG MỚI! (#${e.order.id})`, {
                            position: "top-right",
                            autoClose: 4000,
                        });
                    }
                    if (e.order.table_id) {
                        const tableIndex = tables.value.findIndex(t => t.id === e.order.table_id);
                        if (tableIndex !== -1) {
                            tables.value[tableIndex].status = 'OCCUPIED';
                            if (!tables.value[tableIndex].orders) tables.value[tableIndex].orders = [];
                            const orderExistsInTable = tables.value[tableIndex].orders.some(o => o.id === e.order.id);
                            if (!orderExistsInTable) tables.value[tableIndex].orders.push(e.order);
                        }
                    }
                }
            });

        window.Echo.channel('staff-orders')
            .listen('.order.payment-confirmed', (e) => {
                const index = orders.value.findIndex(o => o.id === e.id);
                if (index !== -1) {
                    orders.value[index].payment.payment_status = e.payment_status;
                    orders.value[index].status = e.status;
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
        <!-- ===== STAT CHIPS ROW ===== -->
        <div class="flex items-center gap-3 mb-6 flex-wrap">
            <div class="stat-chip">
                <div class="stat-chip-icon" style="background:#E8F5E9; color:#388E3C">
                    <span class="material-symbols-outlined text-[20px]">table_restaurant</span>
                </div>
                <div>
                    <p class="stat-num" style="color:#388E3C">{{ emptyTablesCount }}</p>
                    <p class="stat-label">Bàn trống</p>
                </div>
            </div>
            <div class="stat-chip">
                <div class="stat-chip-icon" style="background:#FBE9E7; color:#BF360C">
                    <span class="material-symbols-outlined text-[20px]">people</span>
                </div>
                <div>
                    <p class="stat-num" style="color:#BF360C">{{ occupiedTablesCount }}</p>
                    <p class="stat-label">Đang có khách</p>
                </div>
            </div>
            <div class="stat-chip">
                <div class="stat-chip-icon" style="background:#FFF8E1; color:#F57F17">
                    <span class="material-symbols-outlined text-[20px]">notifications_active</span>
                </div>
                <div>
                    <p class="stat-num" style="color:#F57F17">{{ pendingOrdersCount }}</p>
                    <p class="stat-label">Cần xử lý</p>
                </div>
            </div>
            <div class="stat-chip">
                <div class="stat-chip-icon" style="background:#E8EAF6; color:#3949AB">
                    <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                </div>
                <div>
                    <p class="stat-num" style="color:#3949AB">{{ orders.length }}</p>
                    <p class="stat-label">Đơn hoạt động</p>
                </div>
            </div>
        </div>

        <!-- ===== MAIN CONTENT: Floor Plan + Orders Panel ===== -->
        <div class="flex gap-5 h-[calc(100vh-240px)] min-h-[500px]">

            <!-- ===== FLOOR PLAN ===== -->
            <div class="flex-1 min-w-0 flex flex-col relative rounded-2xl border bg-surface-container-low border-outline-variant/30 overflow-hidden">
                
                <!-- Background Image Placeholder (Can be customized by user) -->
                <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=2000" 
                    alt="Cafe Background"
                    class="absolute inset-0 w-full h-full object-cover opacity-[0.03] pointer-events-none mix-blend-multiply" />

                <!-- Floor plan container -->
                <div class="floor-plan-wrap relative z-10 flex-1 overflow-y-auto hide-scrollbar">

                    <!-- Table Status Legend -->
                    <div class="flex items-center justify-between px-6 py-3 border-b bg-surface/80 backdrop-blur-md border-outline-variant/20 sticky top-0 z-20">
                        <div class="font-bold text-[13px] tracking-wider uppercase flex items-center gap-2 text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">info</span>
                            TRẠNG THÁI BÀN
                        </div>
                        <div class="flex items-center gap-4 text-[12px] font-medium text-on-surface-variant">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-surface-container-lowest border border-outline-variant shadow-sm"></span> Trống</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full shadow-sm bg-primary/10 border border-primary/30"></span> Có khách</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full shadow-sm bg-error/10 border border-error/30"></span> Cần xử lý</span>
                        </div>
                    </div>

                    <!-- Table areas -->
                    <div class="p-6 space-y-8">
                        <div v-for="(areaTables, areaName) in groupedTables" :key="areaName">
                            <!-- Area label -->
                            <div class="flex items-center gap-2 mb-5">
                                <div class="area-pill text-on-surface-variant bg-surface-container-lowest/80 border border-outline-variant/20">
                                    <span class="material-symbols-outlined text-[13px]">location_on</span>
                                    {{ areaName }}
                                </div>
                                <div class="flex-1 border-t border-dashed border-outline-variant/40"></div>
                                <span class="text-[12px] font-medium text-on-surface-variant/70">
                                    {{ areaTables.filter(t => t.status === 'OCCUPIED').length }} / {{ areaTables.length }} bàn
                                </span>
                            </div>

                            <!-- Tables grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                <button v-for="table in areaTables" :key="table.id"
                                    @click="openTableDetails(table)"
                                    class="relative flex flex-col items-center justify-center p-4 rounded-2xl border transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                                    :class="[
                                        table.status === 'OCCUPIED' 
                                            ? (tableHasPendingOrder(table) 
                                                ? 'bg-error/5 border-error/40 shadow-sm' 
                                                : 'bg-primary/5 border-primary/30 shadow-sm') 
                                            : 'bg-surface-container-lowest border-outline-variant/30 hover:border-primary/40'
                                    ]">
                                    
                                    <!-- Notification Bell -->
                                    <div v-if="tableHasPendingOrder(table)"
                                        class="absolute -top-2 -right-2 w-7 h-7 rounded-full text-on-error bg-error flex items-center justify-center shadow-lg animate-bounce">
                                        <span class="material-symbols-outlined text-[16px]">notifications</span>
                                    </div>

                                    <!-- Table Icon -->
                                    <span class="material-symbols-outlined text-[36px] mb-2 transition-colors"
                                        :class="table.status === 'OCCUPIED' ? (tableHasPendingOrder(table) ? 'text-error' : 'text-primary') : 'text-on-surface-variant/30'">
                                        table_restaurant
                                    </span>

                                    <!-- Table Name -->
                                    <span class="font-bold text-[14px] mb-1.5 text-center leading-tight transition-colors"
                                        :class="table.status === 'OCCUPIED' ? (tableHasPendingOrder(table) ? 'text-error' : 'text-on-surface') : 'text-on-surface-variant'">
                                        {{ table.table_name }}
                                    </span>

                                    <!-- Chairs -->
                                    <div class="flex flex-wrap justify-center gap-0.5 mb-2 px-2">
                                        <span v-for="i in table.capacity" :key="i" 
                                            class="material-symbols-outlined text-[15px] transition-colors"
                                            :class="table.status === 'OCCUPIED' ? (tableHasPendingOrder(table) ? 'text-error/40' : 'text-primary/40') : 'text-outline-variant/50'">
                                            chair
                                        </span>
                                    </div>

                                    <!-- Amount if occupied -->
                                    <div v-if="table.status === 'OCCUPIED' && table.orders?.length"
                                        class="mt-auto pt-2 border-t w-full text-center transition-colors"
                                        :class="tableHasPendingOrder(table) ? 'border-error/20' : 'border-primary/20'">
                                        <span class="font-bold text-[13px]"
                                            :class="tableHasPendingOrder(table) ? 'text-error' : 'text-primary'">
                                            {{ formatCurrencyShort(calculateTotalAmount(table.orders)) }}
                                        </span>
                                    </div>
                                    
                                    <div v-else-if="table.status === 'OCCUPIED'"
                                        class="mt-auto pt-2 border-t w-full text-center transition-colors border-primary/20">
                                        <span class="font-medium text-[11px] text-primary/60">Chưa gọi món</span>
                                    </div>

                                    <!-- Empty state placeholder -->
                                    <div v-else class="mt-auto pt-2 border-t w-full text-center transition-colors border-outline-variant/20">
                                        <span class="font-medium text-[11px] text-on-surface-variant/50">Trống</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Plants / decorative corners -->
                    <div class="plant-tl">🌿</div>
                    <div class="plant-tr">🌿</div>
                    <div class="plant-bl">🌿</div>
                    <div class="plant-br">🌿</div>
                </div>
            </div>

            <!-- ===== ACTIVE ORDERS PANEL ===== -->
            <div class="w-72 xl:w-80 flex-shrink-0 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[15px] font-bold text-on-surface">Đơn đang hoạt động</h3>
                    <span v-if="orders.length > 0"
                        class="text-[11px] font-bold px-2 py-0.5 rounded-full text-on-primary bg-primary">
                        {{ orders.length }}
                    </span>
                </div>

                <!-- Empty -->
                <div v-if="orders.length === 0"
                    class="flex-1 rounded-2xl border flex flex-col items-center justify-center p-8 text-center bg-surface-container-low border-outline-variant/30">
                    <span class="material-symbols-outlined text-[40px] mb-3" style="color:#C8A97E">coffee</span>
                    <p class="text-[14px] font-medium" style="color:#8D6E63">Chưa có đơn hàng</p>
                    <p class="text-[12px] mt-1" style="color:#BCAAA4">Các đơn mới sẽ hiện ở đây</p>
                </div>

                <!-- Orders list -->
                <div v-else class="flex-1 overflow-y-auto hide-scrollbar space-y-3 pr-1">
                    <div v-for="order in orders" :key="order.id"
                        @click="openOrderDetails(order)"
                        class="order-card group cursor-pointer rounded-xl border p-4 transition-all hover:shadow-md hover:-translate-y-0.5"
                        :class="order.status === 'PENDING'
                            ? 'bg-error/5 border-error/30 hover:border-error/50'
                            : 'bg-surface-container-low border-outline-variant/30 hover:border-primary/40'">

                        <!-- Order header -->
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <p class="text-[14px] font-bold text-on-surface">
                                    Đơn #{{ order.id }}
                                </p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="material-symbols-outlined text-[12px] text-on-surface-variant">table_restaurant</span>
                                    <span class="text-[12px] text-on-surface-variant">
                                        {{ order.table ? order.table.table_name : 'Mang đi' }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-lg uppercase tracking-wider whitespace-nowrap flex-shrink-0 border"
                                :class="order.status === 'PENDING'
                                    ? 'bg-error/10 text-error border-error/20'
                                    : 'bg-primary/10 text-primary border-primary/20'">
                                {{ order.status === 'PENDING' ? 'Chờ xử lý' : 'Đang xử lý' }}
                            </span>
                        </div>

                        <!-- Items preview -->
                        <ul class="space-y-0.5 mb-2">
                            <li v-for="detail in order.details?.slice(0, 3)" :key="detail.id"
                                class="text-[12px] flex items-center gap-1 text-on-surface-variant">
                                <span class="w-1 h-1 rounded-full flex-shrink-0 bg-outline-variant"></span>
                                {{ detail.product?.product_name }}
                            </li>
                            <li v-if="(order.details?.length || 0) > 3"
                                class="text-[11px] italic text-on-surface-variant/60">
                                +{{ order.details.length - 3 }} món khác...
                            </li>
                        </ul>

                        <!-- Footer -->
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/20">
                            <span class="text-[13px] font-bold text-on-surface">
                                {{ formatCurrency(order.final_amount) }}
                            </span>
                            <span class="text-[11px] text-right flex items-center gap-1 group-hover:gap-2 transition-all text-on-surface-variant hover:text-primary">
                                Chi tiết
                                <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- View all button -->
                <a href="/nhan-vien/don-hang"
                    class="mt-3 flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-[13px] bg-primary text-on-primary transition-all hover:opacity-90 hover:shadow-md">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    Xem tất cả đơn hàng
                </a>
            </div>
        </div>

        <!-- ===== ORDER DETAIL MODAL ===== -->
        <Transition name="fade">
            <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeOrderModal"></div>
                <Transition name="slide-up">
                    <div v-if="isOrderModalOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[90vh] border border-outline-variant/20">

                        <!-- Header -->
                        <div class="px-6 py-4 border-b flex justify-between items-center bg-surface-container-low border-outline-variant/20">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-[13px] font-bold flex-shrink-0"
                                    :class="selectedOrder?.status === 'PENDING'
                                        ? 'bg-error/10 text-error'
                                        : 'bg-primary/10 text-primary'">
                                    #{{ selectedOrder?.id }}
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-bold text-on-surface">Chi tiết đơn hàng</h3>
                                    <p class="text-[11px] uppercase tracking-wider text-on-surface-variant">{{ selectedOrder?.order_type }}</p>
                                </div>
                            </div>
                            <button @click="closeOrderModal"
                                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container text-on-surface-variant transition-all">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-5 overflow-y-auto flex-1 space-y-4 hide-scrollbar">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl p-4 border bg-surface border-outline-variant/20">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1 text-on-surface-variant">Vị trí / Khách</p>
                                    <p class="text-[15px] font-bold text-on-surface">
                                        {{ selectedOrder?.table ? selectedOrder.table.table_name : 'Khách mang đi' }}
                                    </p>
                                </div>
                                <div class="rounded-xl p-4 border text-right bg-surface border-outline-variant/20">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1 text-on-surface-variant">Trạng thái</p>
                                    <span v-if="selectedOrder?.status === 'PENDING'"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-error/10 text-error border border-error/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>Chờ xử lý
                                    </span>
                                    <span v-else-if="selectedOrder?.status === 'PROCESSING'"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-primary/10 text-primary border border-primary/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary/70"></span>Đang xử lý
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border overflow-hidden border-outline-variant/20">
                                <div class="px-4 py-2.5 flex justify-between border-b bg-surface-container-low border-outline-variant/20">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Danh sách món</span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Trạng thái</span>
                                </div>
                                <ul class="divide-y divide-outline-variant/10">
                                    <li v-for="detail in selectedOrder?.details" :key="detail.id"
                                        class="p-4 flex items-center justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[14px] text-on-surface">
                                                <span class="font-bold mr-1.5 text-primary">×{{ detail?.quantity }}</span>
                                                {{ detail?.product?.product_name }}
                                            </p>
                                            <p class="text-[11px] mt-0.5 text-on-surface-variant">
                                                Size {{ detail.variant?.size || '---' }}
                                            </p>
                                        </div>
                                        <span class="flex-shrink-0 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg border whitespace-nowrap"
                                            :class="{
                                                'bg-error/5 border-error/20 text-error': detail?.barista_status === 'PENDING',
                                                'bg-primary/5 border-primary/20 text-primary': detail?.barista_status === 'PREPARING',
                                                'bg-secondary/10 border-secondary/20 text-secondary': detail?.barista_status === 'COMPLETED',
                                                'bg-surface-container border-outline-variant/20 text-on-surface-variant': detail?.barista_status === 'CANCELLED'
                                            }">
                                            {{ detail?.barista_status === 'PENDING' ? '⏳ Chờ pha'
                                                : detail?.barista_status === 'PREPARING' ? '☕ Đang làm'
                                                : detail?.barista_status === 'COMPLETED' ? '✓ Đã xong'
                                                : '✕ Đã hủy' }}
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div class="flex justify-between items-center p-4 rounded-xl border bg-surface-container-low border-outline-variant/20">
                                <span class="text-[14px] text-on-surface-variant font-medium">Tổng thanh toán:</span>
                                <span class="text-[18px] font-bold text-primary">{{ formatCurrency(selectedOrder?.final_amount) }}</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 py-4 border-t flex gap-3 justify-end flex-wrap bg-surface-container-low border-outline-variant/20">
                            <button @click="closeOrderModal"
                                class="px-5 py-2 rounded-xl text-[13px] font-bold hover:bg-surface-container text-on-surface-variant transition-colors">Đóng lại</button>
                            <button v-if="selectedOrder?.payment?.payment_method === 'CASH' && selectedOrder?.payment?.payment_status === 'PENDING'"
                                @click="confirmPayment(selectedOrder.id)"
                                class="px-5 py-2 rounded-xl font-bold text-[13px] flex items-center gap-2 transition-all border bg-secondary/10 text-secondary border-secondary/20 hover:bg-secondary/20">
                                <span class="material-symbols-outlined text-[18px]">payments</span> Thu tiền mặt
                            </button>
                            <button v-if="selectedOrder?.status === 'PENDING'"
                                @click="cancelOrder(selectedOrder.id)"
                                class="px-5 py-2 rounded-xl font-bold text-[13px] flex items-center gap-2 transition-all border bg-error/5 text-error border-error/20 hover:bg-error/10">
                                <span class="material-symbols-outlined text-[18px]">cancel</span> Hủy đơn
                            </button>
                            <button v-if="selectedOrder?.status === 'PENDING'"
                                @click="acceptOrder(selectedOrder.id)"
                                class="px-6 py-2 rounded-xl font-bold text-[13px] text-on-primary flex items-center gap-2 transition-all shadow-sm hover:opacity-90 bg-primary">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span> Tiếp nhận đơn
                            </button>
                            <button v-else-if="selectedOrder?.status === 'PROCESSING'"
                                @click="completeOrder(selectedOrder.id)"
                                class="px-6 py-2 rounded-xl font-bold text-[13px] text-on-secondary flex items-center gap-2 transition-all shadow-sm hover:opacity-90 bg-secondary">
                                <span class="material-symbols-outlined text-[18px]">task_alt</span> Hoàn thành
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- ===== TABLE DETAIL MODAL ===== -->
        <Transition name="fade">
            <div v-if="isTableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeTableModal"></div>
                <Transition name="slide-up">
                    <div v-if="isTableModalOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col border border-outline-variant/20">

                        <!-- Header -->
                        <div class="px-6 py-5 flex justify-between items-center gap-3 border-b border-outline-variant/20"
                            :class="selectedTable?.status === 'OCCUPIED' ? 'bg-primary/8' : 'bg-surface'">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center border flex-shrink-0"
                                    :class="selectedTable?.status === 'OCCUPIED'
                                        ? 'bg-primary/15 border-primary/30'
                                        : 'bg-surface-container-high border-outline-variant/20'">
                                    <span class="material-symbols-outlined text-[20px]"
                                        :class="selectedTable?.status === 'OCCUPIED' ? 'text-primary' : 'text-on-surface-variant'">
                                        table_restaurant
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-serif text-headline-sm font-bold text-on-surface truncate">{{ selectedTable?.table_name }}</h3>
                                    <p class="text-[12px] text-on-surface-variant mt-0.5 uppercase tracking-wider truncate">
                                        {{ selectedTable?.area }} • {{ selectedTable?.capacity }} người
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider whitespace-nowrap"
                                    :class="selectedTable?.status === 'OCCUPIED'
                                        ? 'bg-primary text-on-primary'
                                        : 'bg-surface-container-high text-on-surface-variant border border-outline-variant/20'">
                                    {{ selectedTable?.status === 'OCCUPIED' ? '● Có khách' : '○ Trống' }}
                                </span>
                                <button @click="closeTableModal"
                                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container text-on-surface-variant transition-colors flex-shrink-0">
                                    <span class="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto hide-scrollbar">

                            <!-- Order details (if occupied) -->
                            <template v-if="selectedTable?.status === 'OCCUPIED' && selectedTable?.orders && selectedTable.orders.length > 0">
                                <div class="rounded-xl border border-outline-variant/20 overflow-hidden">
                                    <div class="bg-surface-container-low px-4 py-2.5 flex justify-between items-center border-b border-outline-variant/20">
                                        <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                                            Thông tin đơn hàng
                                        </span>
                                        <span v-if="selectedTable.orders.length > 1"
                                            class="text-[10px] bg-secondary text-on-secondary px-2 py-0.5 rounded-full font-bold">
                                            {{ selectedTable.orders.length }} đơn
                                        </span>
                                    </div>

                                    <div class="p-4 space-y-3">
                                        <div class="max-h-[28vh] overflow-y-auto space-y-2 hide-scrollbar">
                                            <div v-for="(detail, index) in getGroupedOrderDetails(selectedTable.orders)"
                                                :key="'grouped-' + index"
                                                class="flex justify-between items-start gap-3 py-2 border-b border-outline-variant/10 last:border-0">
                                                <div class="flex-1">
                                                    <p class="text-[14px] text-on-surface font-medium">{{ detail.product?.product_name }}</p>
                                                    <p class="text-[12px] text-on-surface-variant mt-0.5">
                                                        Size {{ detail.variant?.size || '---' }} • {{ formatCurrency(detail.unit_price) }}
                                                    </p>
                                                </div>
                                                <span class="font-bold text-on-surface text-[14px] flex-shrink-0">×{{ detail.quantity }}</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-between items-center pt-2 border-t border-outline-variant/20">
                                            <span class="text-[13px] text-on-surface-variant font-medium">Tổng cộng:</span>
                                            <span class="font-bold text-primary text-label-lg">
                                                {{ formatCurrency(calculateTotalAmount(selectedTable.orders)) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between items-center">
                                            <span class="text-[13px] text-on-surface-variant">Thanh toán:</span>
                                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider"
                                                :class="selectedTable.orders.every(o => o.payment?.payment_status === 'PAID')
                                                    ? 'bg-secondary-container text-secondary'
                                                    : 'bg-error/10 text-error border border-error/20'">
                                                {{ selectedTable.orders.every(o => o.payment?.payment_status === 'PAID')
                                                    ? '✓ Đã thanh toán'
                                                    : selectedTable.orders.some(o => o.payment?.payment_status === 'PAID')
                                                        ? '◑ Một phần'
                                                        : '○ Chưa thanh toán' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty table hint -->
                            <div v-if="selectedTable?.status === 'EMPTY'"
                                class="flex items-center gap-3 p-4 rounded-xl bg-surface-container-low border border-outline-variant/20">
                                <span class="material-symbols-outlined text-on-surface-variant/40 text-[24px]">chair</span>
                                <p class="text-[13px] text-on-surface-variant">Bàn đang trống, chưa có khách.</p>
                            </div>

                            <!-- Action buttons -->
                            <div class="space-y-2.5">
                                <button v-if="selectedTable?.status === 'OCCUPIED' && selectedTable?.orders && selectedTable.orders.length > 0"
                                    @click="printBill(selectedTable)"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-surface-container-high border border-outline-variant/30 text-on-surface font-bold text-label-md hover:bg-surface-container-highest transition-all">
                                    <span class="material-symbols-outlined text-[20px]">print</span> In hóa đơn
                                </button>
                                <button v-if="selectedTable?.status === 'EMPTY'"
                                    @click="updateTableStatus(selectedTable.id, 'OCCUPIED')"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-primary text-on-primary font-bold text-label-md hover:bg-primary/90 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">login</span> Khách vào bàn
                                </button>
                                <button v-if="selectedTable?.status === 'OCCUPIED'"
                                    @click="updateTableStatus(selectedTable.id, 'EMPTY')"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-outline-variant/40 font-bold text-on-surface hover:bg-surface-container transition-all text-label-md">
                                    <span class="material-symbols-outlined text-[18px]">cleaning_services</span> Khách về — Dọn bàn
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
/* ===== STAT CHIPS ===== */
.stat-chip {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    border-radius: 14px;
    background: #FAF6F0;
    border: 1px solid #E0D0BE;
    min-width: 140px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.stat-chip-icon {
    width: 40px; height: 40px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.stat-num {
    font-size: 22px; font-weight: 800; line-height: 1;
}
.stat-label {
    font-size: 11px; color: #8D6E63; margin-top: 2px; white-space: nowrap;
}

/* ===== FLOOR PLAN ===== */
.floor-plan-wrap {
    position: relative;
}
.coffee-bar-strip {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    background: #8D6E63; color: #fff;
    font-size: 11px; font-weight: 700; letter-spacing: 0.15em;
    padding: 8px 20px;
    text-transform: uppercase;
}
.area-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,0.75); backdrop-filter: blur(4px);
    border: 1px solid #C8A97E50; border-radius: 99px;
    padding: 4px 12px; font-size: 11px; font-weight: 700;
    color: #8D6E63; text-transform: uppercase; letter-spacing: .1em;
}

/* Plant corners */
.plant-tl, .plant-tr, .plant-bl, .plant-br {
    position: absolute; font-size: 28px; opacity: 0.6; pointer-events: none;
}
.plant-tl { top: 40px; left: 12px; }
.plant-tr { top: 40px; right: 12px; transform: scaleX(-1); }
.plant-bl { bottom: 12px; left: 12px; }
.plant-br { bottom: 12px; right: 12px; transform: scaleX(-1); }



/* Legend */
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #8D6E63; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

/* Order card */
.order-card { background: #FAF6F0; }

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(24px) scale(0.96); }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>
