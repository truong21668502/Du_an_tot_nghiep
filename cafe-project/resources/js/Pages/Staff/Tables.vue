<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import { toast } from "vue3-toastify";
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    initialTables: Array,
});

const tables = ref(props.initialTables || []);

watch(() => props.initialTables, (newVal) => {
    if (newVal) tables.value = newVal;
}, { deep: true });

const selectedTable = ref(null);
const isTableModalOpen = ref(false);
const showCleanConfirm = ref(false);

const openTableModal = (table) => {
    selectedTable.value = table;
    isTableModalOpen.value = true;
};

const closeTableModal = () => {
    isTableModalOpen.value = false;
    showCleanConfirm.value = false;
    setTimeout(() => selectedTable.value = null, 300);
};

const draggedTable = ref(null);
const targetTable = ref(null);
const showMergeConfirm = ref(false);

const handleDragStart = (e, table) => {
    draggedTable.value = table;
    e.dataTransfer.effectAllowed = 'move';
};

const handleDrop = (e, table) => {
    if (draggedTable.value && draggedTable.value.id !== table.id) {
        targetTable.value = table;
        showMergeConfirm.value = true;
    }
};

const cancelMerge = () => {
    showMergeConfirm.value = false;
    draggedTable.value = null;
    targetTable.value = null;
};

const confirmMerge = () => {
    if (draggedTable.value && targetTable.value) {
        router.post(route('staff.tables.merge', {
            fromTable: draggedTable.value.id,
            toTable: targetTable.value.id
        }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Đã gộp ${draggedTable.value.table_name} vào ${targetTable.value.table_name}`);
                cancelMerge();
            },
            onError: () => {
                toast.error('Có lỗi xảy ra khi gộp bàn');
                cancelMerge();
            }
        });
    }
};

const unmergeTable = (table) => {
    const canUnmerge = !table.orders || table.orders.every(o => 
        o.status === 'CANCELLED' || 
        (o.status === 'COMPLETED' && o.payment?.payment_status === 'PAID')
    );

    if (!canUnmerge) {
        toast.warning('Bàn đang có đơn chưa hoàn thành hoặc chưa thanh toán, không thể tách!');
        return;
    }

    if (confirm(`Bạn có chắc chắn muốn tách bàn ${table.table_name} trở lại như cũ?`)) {
        router.patch(route('staff.tables.unmerge', table.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Đã tách bàn ${table.table_name} thành công`);
                closeTableModal();
            },
            onError: (errors) => {
                if (errors.error) toast.error(errors.error);
                else toast.error('Lỗi khi tách bàn');
            }
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

const printBill = (table) => {
    if (!table || !table.orders || table.orders.length === 0) {
        toast.warning('Không có đơn hàng nào để in!');
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

const calculateTotalAmount = (tableOrders) => {
    if (!tableOrders || tableOrders.length === 0) return 0;
    return tableOrders.reduce((sum, order) => sum + Number(order.final_amount || 0), 0);
};

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

const isTableParent = (table) => {
    return tables.value.some(t => t.parent_table_id === table.id);
};

const groupedTables = computed(() => {
    return tables.value
        .filter(t => t.parent_table_id === null)
        .reduce((acc, table) => {
            const area = table.area || 'Khu vực khác';
            if (!acc[area]) acc[area] = [];
            acc[area].push(table);
            return acc;
        }, {});
});

const totalTables = computed(() => tables.value.length);
const occupiedTables = computed(() => tables.value.filter(t => t.status === 'OCCUPIED').length);
const formatCurrencyShort = (value) => {
    if (!value) return '0đ';
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'tr';
    if (value >= 1000) return (value / 1000) + 'k';
    return value + 'đ';
};

const tableHasPendingOrder = (table) => {
    if (!table || !table.orders) return false;
    return table.orders.some(o => o.status === 'PENDING');
};

const emptyTables = computed(() => tables.value.filter(t => t.status === 'EMPTY').length);

const updateTableStatus = (tableId, newStatus) => {
    router.patch(route('staff.tables.update-status', tableId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            const table = tables.value.find(t => t.id === tableId);
            if (table) {
                table.status = newStatus;
                if (newStatus === 'EMPTY') table.orders = [];
            }
            if (newStatus === 'OCCUPIED') {
                toast.success(`Đã xếp khách vào ${table.table_name}`);
            } else {
                toast.info(`Đã dọn dẹp ${table.table_name}`);
            }
            closeTableModal();
        }
    });
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('cafe-tables')
            .listen('.TableUpdated', (e) => {
                const index = tables.value.findIndex(t => t.id === e.id);
                if (index !== -1) {
                    tables.value[index].status = e.status;
                    tables.value[index].capacity = e.capacity;
                    tables.value[index].parent_table_id = e.parent_table_id;
                    if (e.status === 'EMPTY') tables.value[index].orders = [];
                    if (isTableModalOpen.value && selectedTable.value?.id === e.id) {
                        selectedTable.value.status = e.status;
                        selectedTable.value.capacity = e.capacity;
                        selectedTable.value.parent_table_id = e.parent_table_id;
                    }
                }
            })
            .listen('.TableListUpdated', () => {
                router.reload({
                    only: ['initialTables'],
                    preserveScroll: true
                });
            });

        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => {
                new Audio('https://res.cloudinary.com/dltgjdf9t/video/upload/v1785330018/Chu%C3%B4ng_nh%E1%BA%AFc_nh%E1%BB%9F_nh%C3%A2n_vi%C3%AAn_lp3cpm.mp3').play().catch(() => {});
                if (e.order && e.order.table_id) {
                    const tableIndex = tables.value.findIndex(t => t.id === e.order.table_id);
                    if (tableIndex !== -1) {
                        tables.value[tableIndex].status = 'OCCUPIED';
                        if (!tables.value[tableIndex].orders) tables.value[tableIndex].orders = [];
                        const orderExists = tables.value[tableIndex].orders.some(o => o.id === e.order.id);
                        if (!orderExists) {
                            tables.value[tableIndex].orders.push(e.order);
                            toast.success(`Đơn mới ở ${tables.value[tableIndex].table_name}!`, { autoClose: 3000 });
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
    <Head title="Sơ đồ bàn" />

    <StaffLayout>
        <!-- Page Header -->
        <div class="mb-8 flex items-end justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-block w-1.5 h-5 rounded-full bg-primary"></span>
                    <p class="text-[12px] font-bold uppercase tracking-[0.15em] text-primary">Quản lý</p>
                </div>
                <h2 class="text-display-lg-mobile md:text-display-lg text-on-background font-serif">Sơ đồ mặt bằng</h2>
            </div>

            <!-- Stats row -->
            <div class="flex items-center gap-2 pb-1 flex-wrap">
                <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-surface-container-low border border-outline-variant/20 text-[13px] font-medium text-on-surface-variant">
                    <span class="material-symbols-outlined text-[17px] text-on-surface-variant/60">table_restaurant</span>
                    <span>Tổng: <strong class="text-on-surface">{{ tables.filter(t => t.parent_table_id === null).length }}</strong></span>
                </div>
                <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-primary/8 border border-primary/20 text-[13px] font-medium text-primary">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    <span>Có khách: <strong>{{ occupiedTables }}</strong></span>
                </div>
                <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-surface-container-low border border-outline-variant/20 text-[13px] font-medium text-on-surface-variant">
                    <span class="w-2 h-2 rounded-full bg-on-surface-variant/30"></span>
                    <span>Trống: <strong class="text-on-surface">{{ emptyTables }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Floor plan canvas -->
        <div class="relative rounded-2xl border bg-surface-container-low border-outline-variant/30 overflow-hidden">
            
            <!-- Background Image Placeholder (Can be customized by user) -->
            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783495774/background_nangcoffee_gdibni.png" 
                alt="Cafe Background"
                class="absolute inset-0 w-full h-full object-cover opacity-[0.15] pointer-events-none" />

            <!-- Floor plan container -->
            <div class="floor-plan-wrap relative z-10 overflow-y-auto hide-scrollbar">

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
                                draggable="true"
                                @dragstart="handleDragStart($event, table)"
                                @dragover.prevent
                                @drop="handleDrop($event, table)"
                                @click="openTableModal(table)"
                                class="relative flex flex-col items-center justify-center p-4 rounded-2xl border transition-all duration-200 hover:-translate-y-1 hover:shadow-md cursor-grab active:cursor-grabbing"
                                :class="[
                                    isTableParent(table) ? 'col-span-2 row-span-2' : '',
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

                                <!-- Capacity indicator based on standard (4) or merged -->
                                <div class="flex items-center justify-center gap-1 mt-1 text-[11px] text-on-surface-variant/70">
                                    <span class="material-symbols-outlined text-[12px]">group</span>
                                    {{ table.capacity }}
                                </div>

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

        <!-- Table detail modal -->
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
                                <button v-if="isTableParent(selectedTable)" @click="unmergeTable(selectedTable)"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border border-error text-error font-bold text-label-md hover:bg-error-container/30 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">call_split</span> Tách bàn về như cũ
                                </button>
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
                                <!-- Clean table: 2-step confirmation -->
                                <template v-if="selectedTable?.status === 'OCCUPIED'">
                                    <button v-if="!showCleanConfirm"
                                        @click="(() => {
                                            const canClean = !selectedTable.orders || selectedTable.orders.every(o => 
                                                o.status === 'CANCELLED' || 
                                                (o.status === 'COMPLETED' && o.payment?.payment_status === 'PAID')
                                            );
                                            if (!canClean) {
                                                toast.warning('Bàn còn đơn chưa hoàn thành hoặc chưa thanh toán, không thể dọn!');
                                            } else {
                                                showCleanConfirm = true;
                                            }
                                        })()"
                                        class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-outline-variant/40 font-bold text-on-surface hover:bg-surface-container transition-all text-label-md">
                                        <span class="material-symbols-outlined text-[18px]">cleaning_services</span> Khách về — Dọn bàn
                                    </button>
                                    <div v-else class="rounded-xl border-2 border-error/30 bg-error/5 p-4 space-y-3">
                                        <div class="flex items-center gap-2 text-error">
                                            <span class="material-symbols-outlined text-[20px]">warning</span>
                                            <p class="text-[13px] font-bold">Xác nhận dọn bàn?</p>
                                        </div>
                                        <p class="text-[12px] text-on-surface-variant">Hành động này sẽ chuyển bàn về trạng thái trống và xoá các đơn hàng liên kết.</p>
                                        <div class="flex gap-2">
                                            <button @click="showCleanConfirm = false"
                                                class="flex-1 py-2.5 rounded-xl border border-outline-variant/40 font-bold text-[13px] text-on-surface-variant hover:bg-surface-container transition-all">
                                                Huỷ
                                            </button>
                                            <button @click="updateTableStatus(selectedTable.id, 'EMPTY')"
                                                class="flex-1 py-2.5 rounded-xl bg-error text-on-error font-bold text-[13px] hover:bg-error/90 transition-all shadow-sm">
                                                <span class="flex items-center justify-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[16px]">check</span> Xác nhận
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Merge Confirm Modal -->
        <Transition name="fade">
            <div v-if="showMergeConfirm" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="cancelMerge"></div>
                <Transition name="slide-up">
                    <div v-if="showMergeConfirm" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden text-center p-6 border-2 border-primary/20">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-primary text-[32px]">merge</span>
                        </div>
                        <h3 class="text-title-lg font-bold text-on-surface mb-2">Gộp bàn?</h3>
                        <p class="text-body-md text-on-surface-variant mb-6">
                            Bạn có chắc chắn muốn gộp <strong>{{ draggedTable?.table_name }}</strong> vào <strong>{{ targetTable?.table_name }}</strong>?
                        </p>
                        <div class="flex gap-3">
                            <button @click="cancelMerge" class="flex-1 py-2.5 rounded-xl border border-outline-variant/50 font-bold text-on-surface-variant hover:bg-surface-container transition-colors">
                                Hủy
                            </button>
                            <button @click="confirmMerge" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-bold shadow-sm hover:bg-primary/90 transition-colors">
                                Xác nhận
                            </button>
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
    transform: translateY(24px) scale(0.96);
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Floor plan */
.floor-plan-wrap {
    position: relative;
}
.area-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,0.75); backdrop-filter: blur(4px);
    border-radius: 99px;
    padding: 4px 12px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
}

/* Plant corners */
.plant-tl, .plant-tr, .plant-bl, .plant-br {
    position: absolute; font-size: 28px; opacity: 0.6; pointer-events: none;
}
.plant-tl { top: 40px; left: 12px; }
.plant-tr { top: 40px; right: 12px; transform: scaleX(-1); }
.plant-bl { bottom: 12px; left: 12px; }
.plant-br { bottom: 12px; right: 12px; transform: scaleX(-1); }
</style>