<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import StatCards from './Partials/StatCards.vue';
import { toast } from "vue3-toastify";
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    initialOrders: Array,
    initialTables: Array,
});

// Quản lý trạng thái đơn hàng
const orders = ref(props.initialOrders || []);
const selectedOrder = ref(null);
const isOrderModalOpen = ref(false);
const showCancelConfirm = ref(false);
const showPaymentConfirm = ref(false);
const vnpayQrUrl = ref(null);

// Đồng bộ dữ liệu khi Inertia reload props (từ router.reload)
watch(() => props.initialOrders, (newVal) => {
    if (newVal) orders.value = newVal;
}, { deep: true });

watch(() => props.initialTables, (newVal) => {
    if (newVal) tables.value = newVal;
}, { deep: true });

const openOrderDetails = async (order) => {
    selectedOrder.value = order;
    isOrderModalOpen.value = true;

    // Nếu thanh toán chuyển khoản và đang chờ thanh toán
    if (order?.payment?.payment_method !== 'CASH' && order?.payment?.payment_status === 'PENDING') {
        try {
            vnpayQrUrl.value = null;
            const res = await axios.get(route('staff.orders.vnpay-url', order.id));
            vnpayQrUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(res.data.url)}`;
        } catch (error) {
            console.error("Lỗi lấy mã QR VNPay:", error);
        }
    } else {
        vnpayQrUrl.value = null;
    }
};

const closeOrderModal = () => {
    isOrderModalOpen.value = false;
    showCancelConfirm.value = false;
    showPaymentConfirm.value = false;
    vnpayQrUrl.value = null;
    setTimeout(() => selectedOrder.value = null, 300);
};

const acceptOrder = async (orderId) => {
    try {
        await axios.patch(route('staff.orders.accept', orderId));
        // Thay thế toàn bộ object để Vue phát hiện thay đổi chắc chắn
        const index = orders.value.findIndex(o => o.id === orderId);
        if (index !== -1) {
            orders.value[index] = { ...orders.value[index], status: 'PROCESSING' };
            // selectedOrder cũng trỏ sang object mới (giữ nguyên payment)
            if (selectedOrder.value?.id === orderId) {
                await nextTick();
                selectedOrder.value = orders.value[index];
            }
        }
        showCancelConfirm.value = false;
        toast.success(`Đã tiếp nhận đơn hàng #${orderId}`);
    } catch (error) {
        console.error('Lỗi tiếp nhận đơn:', error);
        toast.error('Không thể tiếp nhận đơn hàng này!');
    }
};


const completeOrder = (orderId) => {
    const order = orders.value.find(o => o.id === orderId);
    router.patch(route('staff.orders.complete', orderId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (order && order.order_type === 'DELIVERY') {
                order.status = 'READY';
                if (selectedOrder.value?.id === orderId) {
                    selectedOrder.value.status = 'READY';
                }
                toast.success(`Đơn hàng #${orderId} đã sẵn sàng giao!`);
                closeOrderModal();
            } else {
                orders.value = orders.value.filter(o => o.id !== orderId);
                closeOrderModal();
                toast.info(`Đơn hàng #${orderId} đã hoàn thành!`);
            }
        }
    });
};

const startDelivering = (orderId) => {
    router.patch(route('staff.orders.start-delivering', orderId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            orders.value = orders.value.filter(o => o.id !== orderId);
            closeOrderModal();
        }
    });
};

const cancelOrder = (orderId) => {
    router.patch(route('staff.orders.cancel', orderId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            orders.value = orders.value.filter(o => o.id !== orderId);

            // Cập nhật lại tables state
            tables.value.forEach(t => {
                if (t.orders) {
                    t.orders = t.orders.filter(o => o.id !== orderId);
                }
            });

            closeOrderModal();
            toast.success(`Đã hủy đơn hàng #${orderId} thành công!`);
        }
    });
};

const confirmPayment = (orderId) => {
    router.patch(route('staff.orders.confirm-payment', orderId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Cập nhật local state ngay lập tức cho orders
            const orderIndex = orders.value.findIndex(o => o.id === orderId);
            if (orderIndex !== -1 && orders.value[orderIndex].payment) {
                orders.value[orderIndex].payment.payment_status = 'PAID';
            }
            if (selectedOrder.value?.id === orderId && selectedOrder.value.payment) {
                selectedOrder.value.payment.payment_status = 'PAID';
            }

            // Cập nhật local state cho tables
            tables.value.forEach(t => {
                if (t.orders) {
                    const tOrder = t.orders.find(o => o.id === orderId);
                    if (tOrder && tOrder.payment) {
                        tOrder.payment.payment_status = 'PAID';
                    }
                }
            });

            closeOrderModal();
            toast.success('Đã xác nhận thanh toán!');
        }
    });
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};
// Hàm in hóa đơn
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
                <div style="text-align: center; margin-top: 15px;">
                    <p style="margin-bottom: 5px; font-weight: bold;">Quét mã để thanh toán</p>
                    <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1784969273/Qr_VietComBank_Nhat_Duy_yiygbz.jpg" alt="QR Code" style="width: 150px; height: 150px;" />
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

// Quản lý trạng thái bàn
const tables = ref(props.initialTables || []);
const selectedTable = ref(null);
const isTableModalOpen = ref(false);
const showCleanConfirm = ref(false);

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

const isTableParent = (table) => {
    return tables.value.some(t => t.parent_table_id === table.id);
};

// Gom nhóm bàn theo khu vực
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

const openTableDetails = (table) => {
    selectedTable.value = table;
    isTableModalOpen.value = true;
};

const closeTableModal = () => {
    isTableModalOpen.value = false;
    showCleanConfirm.value = false;
    setTimeout(() => selectedTable.value = null, 300);
};

// Cập nhật trạng thái bàn
const updateTableStatus = (tableId, newStatus) => {
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

// Gộp các món ăn trùng tên và kích thước
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

// Thống kê tổng quan
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


// WebSockets lắng nghe thay đổi thời gian thực
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
            });

        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => {
                new Audio('https://res.cloudinary.com/dltgjdf9t/video/upload/v1785330018/Chu%C3%B4ng_nh%E1%BA%AFc_nh%E1%BB%9F_nh%C3%A2n_vi%C3%AAn_lp3cpm.mp3').play().catch(() => {});
                router.reload({
                    only: ['initialOrders', 'initialTables'],
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success(`CÓ ĐƠN HÀNG MỚI! (#${e.order.id})`, {
                            position: "top-right",
                            autoClose: 4000,
                        });
                    }
                });
            });

        window.Echo.channel('staff-orders')
            .listen('.order.status-updated', (e) => {
                // Reload dữ liệu đầy đủ từ server
                router.reload({
                    only: ['initialOrders', 'initialTables'],
                    preserveScroll: true,
                    onSuccess: () => {
                        if (['COMPLETED', 'CANCELLED'].includes(e.order.status)) {
                            if (selectedOrder.value?.id === e.order.id) closeOrderModal();
                        }
                    }
                });
            });

        window.Echo.channel('staff-orders')
            .listen('.order.payment-confirmed', (e) => {
                const index = orders.value.findIndex(o => o.id === e.id);
                if (index !== -1 && orders.value[index].payment) {
                    orders.value[index].payment.payment_status = e.payment_status;
                    orders.value[index].status = e.status;
                    toast.success(`Đơn ${e.order_code} đã thanh toán xong!`);
                }

                // Đồng thời cập nhật trạng thái đơn trong sơ đồ bàn
                tables.value.forEach(t => {
                    if (t.orders) {
                        const tOrder = t.orders.find(o => o.id === e.id);
                        if (tOrder && tOrder.payment) {
                            tOrder.payment.payment_status = e.payment_status;
                        }
                    }
                });
            })
            .listen('.order.cancelled', (e) => {
                // Xoá khỏi danh sách orders
                orders.value = orders.value.filter(o => o.id !== e.id);
                // Xoá khỏi danh sách orders trong bàn
                tables.value.forEach(t => {
                    if (t.orders) {
                        t.orders = t.orders.filter(o => o.id !== e.id);
                    }
                });
                toast.info(`Đơn hàng #${e.id} đã bị hủy.`);
            })
            .listen('.barista.detail.updated', (e) => {
                if (e.barista_status === 'COMPLETED') {
                    new Audio('https://res.cloudinary.com/dltgjdf9t/video/upload/v1785330017/Chu%C3%B4ng_ho%C3%A0n_th%C3%A0nh_nh%C3%A2n_vi%C3%AAn_ixtcq2.mp3').play().catch(() => {});
                }
                const updateStatus = (orderList) => {
                    const oIndex = orderList.findIndex(o => o.id === e.order_id);
                    if (oIndex !== -1 && orderList[oIndex].details) {
                        const dIndex = orderList[oIndex].details.findIndex(d => d.id === e.id);
                        if (dIndex !== -1) {
                            orderList[oIndex].details[dIndex].barista_status = e.barista_status;
                        }
                    }
                };
                updateStatus(orders.value);
                if (selectedOrder.value?.id === e.order_id && selectedOrder.value.details) {
                    const dIndex = selectedOrder.value.details.findIndex(d => d.id === e.id);
                    if (dIndex !== -1) {
                        selectedOrder.value.details[dIndex].barista_status = e.barista_status;
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

        <!-- Thống kê tổng quan -->
        <div class="flex items-center gap-3 mb-6 flex-wrap">
            <div class="stat-chip">
                <div class="stat-chip-icon" style="background:#E8F5E9; color:#388E3C">
                    <span class="material-symbols-outlined text-[20px]">table_restaurant</span>
                </div>
                <div>
                    <p class="stat-num" style="color:#388E3C">{{ tables.filter(t => t.status === 'EMPTY' && t.parent_table_id === null).length }}</p>
                    <p class="stat-label">Bàn trống</p>
                </div>
            </div>
            <div class="stat-chip">
                <div class="stat-chip-icon" style="background:#FBE9E7; color:#BF360C">
                    <span class="material-symbols-outlined text-[20px]">people</span>
                </div>
                <div>
                    <p class="stat-num" style="color:#BF360C">{{ tables.filter(t => t.status === 'OCCUPIED').length }}</p>
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

        <!-- Nội dung chính: sơ đồ bàn + đơn hàng -->
        <div class="flex gap-5 h-[calc(100vh-240px)] min-h-[500px]">

            <!-- Sơ đồ bàn -->
            <div
                class="flex-1 min-w-0 flex flex-col relative rounded-2xl border bg-surface-container-low border-outline-variant/30 overflow-hidden">

                <!-- Ảnh nền quán Cafe -->
                <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783495774/background_nangcoffee_gdibni.png"
                    alt="Cafe Background"
                    class="absolute inset-0 w-full h-full object-cover opacity-[0.15] pointer-events-none" />

                <!-- Khung chứa sơ đồ -->
                <div class="floor-plan-wrap relative z-10 flex-1 overflow-y-auto hide-scrollbar">

                    <!-- Ghi chú trạng thái -->
                    <div
                        class="flex items-center justify-between px-6 py-3 border-b bg-surface/80 backdrop-blur-md border-outline-variant/20 sticky top-0 z-20">
                        <div
                            class="font-bold text-[13px] tracking-wider uppercase flex items-center gap-2 text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">info</span>
                            TRẠNG THÁI BÀN
                        </div>
                        <div class="flex items-center gap-4 text-[12px] font-medium text-on-surface-variant">
                            <span class="flex items-center gap-1.5"><span
                                    class="w-3 h-3 rounded-full bg-surface-container-lowest border border-outline-variant shadow-sm"></span>
                                Trống</span>
                            <span class="flex items-center gap-1.5"><span
                                    class="w-3 h-3 rounded-full shadow-sm bg-primary/10 border border-primary/30"></span>
                                Có khách</span>
                            <span class="flex items-center gap-1.5"><span
                                    class="w-3 h-3 rounded-full shadow-sm bg-error/10 border border-error/30"></span>
                                Cần xử lý</span>
                        </div>
                    </div>

                    <!-- Các khu vực bàn -->
                    <div class="p-6 space-y-8">
                        <div v-for="(areaTables, areaName) in groupedTables" :key="areaName">
                            <!-- Tên khu vực -->
                            <div class="flex items-center gap-2 mb-5">
                                <div
                                    class="area-pill text-on-surface-variant bg-surface-container-lowest/80 border border-outline-variant/20">
                                    <span class="material-symbols-outlined text-[13px]">location_on</span>
                                    {{ areaName }}
                                </div>
                                <div class="flex-1 border-t border-dashed border-outline-variant/40"></div>
                                <span class="text-[12px] font-medium text-on-surface-variant/70">
                                    {{areaTables.filter(t => t.status === 'OCCUPIED').length}} / {{ areaTables.length
                                    }} bàn
                                </span>
                            </div>

                            <!-- Lưới bàn -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                <button v-for="table in areaTables" :key="table.id" @click="openTableDetails(table)"
                                    draggable="true"
                                    @dragstart="handleDragStart($event, table)"
                                    @dragover.prevent
                                    @drop="handleDrop($event, table)"
                                    class="relative flex flex-col items-center justify-center p-4 rounded-2xl border transition-all duration-200 hover:-translate-y-1 hover:shadow-md cursor-grab active:cursor-grabbing"
                                    :class="[
                                        isTableParent(table) ? 'col-span-2 row-span-2' : '',
                                        table.status === 'OCCUPIED'
                                            ? (tableHasPendingOrder(table)
                                                ? 'bg-error/5 border-error/40 shadow-sm'
                                                : 'bg-primary/5 border-primary/30 shadow-sm')
                                            : 'bg-surface-container-lowest border-outline-variant/30 hover:border-primary/40'
                                    ]">

                                    <!-- Chuông thông báo -->
                                    <div v-if="tableHasPendingOrder(table)"
                                        class="absolute -top-2 -right-2 w-7 h-7 rounded-full text-on-error bg-error flex items-center justify-center shadow-lg animate-bounce">
                                        <span class="material-symbols-outlined text-[16px]">notifications</span>
                                    </div>

                                    <!-- Biểu tượng bàn -->
                                    <span class="material-symbols-outlined text-[36px] mb-2 transition-colors"
                                        :class="table.status === 'OCCUPIED' ? (tableHasPendingOrder(table) ? 'text-error' : 'text-primary') : 'text-on-surface-variant/30'">
                                        table_restaurant
                                    </span>

                                    <!-- Tên bàn -->
                                    <span
                                        class="font-bold text-[14px] mb-1.5 text-center leading-tight transition-colors"
                                        :class="table.status === 'OCCUPIED' ? (tableHasPendingOrder(table) ? 'text-error' : 'text-on-surface') : 'text-on-surface-variant'">
                                        {{ table.table_name }}
                                    </span>

                                    <!-- Chỉ báo số lượng khách/ghế -->
                                    <div class="flex items-center justify-center gap-1 mt-1 mb-1 text-[11px] text-on-surface-variant/70">
                                        <span class="material-symbols-outlined text-[12px]">group</span>
                                        {{ table.capacity }}
                                    </div>

                                    <!-- Ghế ngồi -->
                                    <div class="flex flex-wrap justify-center gap-0.5 mb-2 px-2">
                                        <span v-for="i in table.capacity" :key="i"
                                            class="material-symbols-outlined text-[15px] transition-colors"
                                            :class="table.status === 'OCCUPIED' ? (tableHasPendingOrder(table) ? 'text-error/40' : 'text-primary/40') : 'text-outline-variant/50'">
                                            chair
                                        </span>
                                    </div>

                                    <!-- Tổng tiền (nếu có khách) -->
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

                                    <!-- Chữ trống (khi chưa gọi món/trống) -->
                                    <div v-else
                                        class="mt-auto pt-2 border-t w-full text-center transition-colors border-outline-variant/20">
                                        <span class="font-medium text-[11px] text-on-surface-variant/50">Trống</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cây trang trí ở các góc -->
                    <div class="plant-tl">🌿</div>
                    <div class="plant-tr">🌿</div>
                    <div class="plant-bl">🌿</div>
                    <div class="plant-br">🌿</div>
                </div>
            </div>

            <!-- Danh sách đơn hàng hoạt động -->
            <div class="w-72 xl:w-80 flex-shrink-0 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[15px] font-bold text-on-surface">Đơn đang hoạt động</h3>
                    <span v-if="orders.length > 0"
                        class="text-[11px] font-bold px-2 py-0.5 rounded-full text-on-primary bg-primary">
                        {{ orders.length }}
                    </span>
                </div>

                <!-- Trạng thái trống -->
                <div v-if="orders.length === 0"
                    class="flex-1 rounded-2xl border flex flex-col items-center justify-center p-8 text-center bg-surface-container-low border-outline-variant/30">
                    <span class="material-symbols-outlined text-[40px] mb-3" style="color:#C8A97E">coffee</span>
                    <p class="text-[14px] font-medium" style="color:#8D6E63">Chưa có đơn hàng</p>
                    <p class="text-[12px] mt-1" style="color:#BCAAA4">Các đơn mới sẽ hiện ở đây</p>
                </div>

                <!-- Danh sách đơn hàng -->
                <TransitionGroup v-else tag="div" name="list" class="flex-1 overflow-y-auto hide-scrollbar space-y-3 pr-1">
                    <div v-for="order in orders" :key="order.id" @click="openOrderDetails(order)"
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
                                    <span
                                        class="material-symbols-outlined text-[12px] text-on-surface-variant">table_restaurant</span>
                                    <span class="text-[12px] text-on-surface-variant">
                                        {{ order.table ? order.table.table_name : (order.order_type === 'DELIVERY' ? 'Giao hàng' : 'Mang đi') }}
                                    </span>
                                </div>
                            </div>
                            <span
                                class="text-[10px] font-bold px-2 py-1 rounded-lg uppercase tracking-wider whitespace-nowrap flex-shrink-0 border"
                                :class="order.status === 'PENDING'
                                    ? 'bg-error/10 text-error border-error/20'
                                    : (order.status === 'READY' ? 'bg-green-500/10 text-green-600 border-green-500/20' : 'bg-primary/10 text-primary border-primary/20')">
                                {{ order.status === 'PENDING' ? 'Chờ xử lý' : (order.status === 'READY' ? 'Sẵn sàng giao' : 'Đang xử lý') }}
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
                            <span
                                class="text-[11px] text-right flex items-center gap-1 group-hover:gap-2 transition-all text-on-surface-variant hover:text-primary">
                                Chi tiết
                                <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                </TransitionGroup>

                <!-- View all button -->
                <a href="/nhan-vien/don-hang"
                    class="mt-3 flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-[13px] bg-primary text-on-primary transition-all hover:opacity-90 hover:shadow-md">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    Xem tất cả đơn hàng
                </a>
            </div>
        </div>

        <!-- Modal chi tiết đơn hàng -->
        <Transition name="fade">
            <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeOrderModal"></div>
                <Transition name="slide-up">
                    <div v-if="isOrderModalOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[90vh] border border-outline-variant/20">

                        <!-- Header -->
                        <div
                            class="px-6 py-4 border-b flex justify-between items-center bg-surface-container-low border-outline-variant/20">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-[13px] font-bold flex-shrink-0"
                                    :class="selectedOrder?.status === 'PENDING'
                                        ? 'bg-error/10 text-error'
                                        : 'bg-primary/10 text-primary'">
                                    #{{ selectedOrder?.id }}
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-bold text-on-surface">Chi tiết đơn hàng</h3>
                                    <p class="text-[11px] uppercase tracking-wider text-on-surface-variant">{{
                                        selectedOrder?.order_type }}</p>
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
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider mb-1 text-on-surface-variant">
                                        Vị trí / Khách</p>
                                    <p class="text-[15px] font-bold text-on-surface">
                                        {{ selectedOrder?.table ? selectedOrder.table.table_name : (selectedOrder?.order_type === 'DELIVERY' ? 'Khách giao hàng' : 'Khách mang đi') }}
                                    </p>
                                </div>
                                <div class="rounded-xl p-4 border text-right bg-surface border-outline-variant/20">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider mb-1 text-on-surface-variant">
                                        Trạng thái</p>
                                    <span v-if="selectedOrder?.status === 'PENDING'"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-error/10 text-error border border-error/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>Chờ xử lý
                                    </span>
                                    <span v-else-if="selectedOrder?.status === 'PROCESSING'"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-primary/10 text-primary border border-primary/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary/70"></span>Đang xử lý
                                    </span>
                                    <span v-else-if="selectedOrder?.status === 'READY'"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-green-500/10 text-green-600 border border-green-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500/70"></span>Sẵn sàng giao
                                    </span>
                                </div>
                            </div>

                            <!-- Ghi chú chung của đơn hàng -->
                            <div v-if="selectedOrder?.note" class="mb-4 p-3 bg-primary/5 rounded-lg border border-primary/20">
                                <div class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-primary mt-0.5">sticky_note_2</span>
                                    <div>
                                        <span class="text-[12px] font-bold text-primary block">Ghi chú tổng:</span>
                                        <span class="text-[13px] text-on-surface-variant">{{ selectedOrder.note }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border overflow-hidden border-outline-variant/20">
                                <div
                                    class="px-4 py-2.5 flex justify-between border-b bg-surface-container-low border-outline-variant/20">
                                    <span
                                        class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Danh
                                        sách món</span>
                                    <span
                                        class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Trạng
                                        thái</span>
                                </div>
                                <ul class="divide-y divide-outline-variant/10">
                                    <li v-for="detail in selectedOrder?.details" :key="detail.id"
                                        class="p-4 flex items-center justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[14px] text-on-surface">
                                                <span class="font-bold mr-1.5 text-primary">×{{ detail?.quantity
                                                }}</span>
                                                {{ detail?.product?.product_name }}
                                            </p>
                                            <p class="text-[11px] mt-0.5 text-on-surface-variant">
                                                Size {{ detail.variant?.size || '---' }}
                                            </p>
                                            <div v-if="detail?.note"
                                                class="mt-1.5 flex items-center gap-1 text-tertiary">
                                                <span class="material-symbols-outlined text-[13px]">edit_note</span>
                                                <span class="text-[11px] italic">{{ detail.note }}</span>
                                            </div>
                                        </div>
                                        <span
                                            class="flex-shrink-0 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg border whitespace-nowrap"
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

                            <!-- Mã QR Chuyển khoản -->
                            <div v-if="selectedOrder?.payment?.payment_method !== 'CASH' && selectedOrder?.payment?.payment_status === 'PENDING'"
                                class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-outline-variant/30 shadow-sm">
                                <p class="text-[13px] font-bold text-[#005BAA] mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">qr_code_scanner</span> Quét mã
                                    chuyển khoản
                                </p>
                                <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1784969273/Qr_VietComBank_Nhat_Duy_yiygbz.jpg" alt="QR Chuyển khoản"
                                    class="w-full max-w-[200px] h-auto object-contain border p-1 shadow-sm" />
                                <p class="text-[14px] text-error font-bold mt-3">Số tiền: {{
                                    formatCurrency(selectedOrder?.final_amount) }}</p>
                            </div>

                            <!-- Breakdown cho DELIVERY + CASH -->
                            <div v-if="selectedOrder?.order_type === 'DELIVERY' && selectedOrder?.payment?.payment_method === 'CASH'"
                                class="rounded-xl border border-outline-variant/20 overflow-hidden">
                                <div class="bg-surface-container-low px-4 py-2 border-b border-outline-variant/20">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Chi tiết thanh toán</span>
                                </div>
                                <div class="p-4 space-y-2.5">
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="text-on-surface-variant">Tiền hàng</span>
                                        <span class="font-medium text-on-surface">{{ formatCurrency(selectedOrder?.total_amount) }}</span>
                                    </div>
                                    <div v-if="selectedOrder?.discount_amount > 0" class="flex justify-between items-center text-[13px]">
                                        <span class="text-on-surface-variant">Giảm giá</span>
                                        <span class="font-medium text-green-600">-{{ formatCurrency(selectedOrder?.discount_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="flex items-center gap-1.5 text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[14px]">delivery_dining</span>
                                            Phí ship (shipper thu)
                                        </span>
                                        <span class="font-medium text-on-surface-variant">{{ formatCurrency(selectedOrder?.shipping_fee ?? 0) }}</span>
                                    </div>
                                    <div class="border-t border-outline-variant/20 pt-2.5 mt-1">
                                        <div class="flex justify-between items-center">
                                            <span class="text-[13px] font-bold text-on-surface">Nhân viên thu:</span>
                                            <span class="text-[18px] font-bold text-primary">
                                                {{ formatCurrency((selectedOrder?.total_amount ?? 0) - (selectedOrder?.discount_amount ?? 0)) }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-on-surface-variant/70 mt-1">
                                            Khách sẽ trả thêm {{ formatCurrency(selectedOrder?.shipping_fee ?? 0) }} phí ship cho shipper
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tổng thanh toán thông thường -->
                            <div v-else class="space-y-3">
                                <!-- Thông báo DELIVERY + VNPAY đã thanh toán: đưa tiền ship cho shipper -->
                                <div v-if="selectedOrder?.order_type === 'DELIVERY' && selectedOrder?.payment?.payment_method !== 'CASH' && (selectedOrder?.shipping_fee ?? 0) > 0"
                                    class="flex items-start gap-3 p-3.5 rounded-xl bg-amber-500/8 border border-amber-500/25">
                                    <span class="material-symbols-outlined text-[18px] text-amber-600 mt-0.5 flex-shrink-0">wallet</span>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700 mb-0.5">Lưu ý phí ship</p>
                                        <p class="text-[12px] text-amber-800">
                                            Khách đã thanh toán online toàn bộ.<br>
                                            Nhân viên cần đưa <span class="font-bold">{{ formatCurrency(selectedOrder?.shipping_fee ?? 0) }}</span> tiền ship cho shipper.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center p-4 rounded-xl border bg-surface-container-low border-outline-variant/20">
                                    <span class="text-[14px] text-on-surface-variant font-medium">Tổng thanh toán:</span>
                                    <span class="text-[18px] font-bold text-primary">{{
                                        formatCurrency(selectedOrder?.final_amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 py-4 border-t space-y-3 bg-surface-container-low border-outline-variant/20">

                            <!-- Xác nhận thanh toán -->
                            <template v-if="selectedOrder?.payment?.payment_status === 'PENDING'">
                                <button v-if="!showPaymentConfirm" @click="showPaymentConfirm = true"
                                    class="w-full px-5 py-2.5 rounded-xl font-bold text-[13px] flex items-center justify-center gap-2 transition-all border bg-secondary/10 text-secondary border-secondary/20 hover:bg-secondary/20">
                                    <span class="material-symbols-outlined text-[18px]">payments</span>
                                    <span v-if="selectedOrder?.payment?.payment_method === 'CASH' && selectedOrder?.order_type === 'DELIVERY'">
                                        Thu {{ formatCurrency((selectedOrder?.total_amount ?? 0) - (selectedOrder?.discount_amount ?? 0)) }}
                                    </span>
                                    <span v-else>
                                        {{ selectedOrder?.payment?.payment_method === 'CASH' ? 'Thu tiền mặt' : 'Xác nhận đã thanh toán' }}
                                    </span>
                                </button>
                                <div v-else
                                    class="rounded-xl border-2 border-secondary/30 bg-secondary/5 p-4 space-y-3">
                                    <div class="flex items-center gap-2 text-secondary">
                                        <span class="material-symbols-outlined text-[20px]">payments</span>
                                        <p class="text-[13px] font-bold">{{ selectedOrder?.payment?.payment_method ===
                                            'CASH' ? 'Xác nhận thu tiền mặt?' : 'Xác nhận đã thanh toán?' }}</p>
                                    </div>
                                    <p class="text-[12px] text-on-surface-variant">
                                        <span v-if="selectedOrder?.payment?.payment_method === 'CASH' && selectedOrder?.order_type === 'DELIVERY'">
                                            Nhân viên thu: {{ formatCurrency((selectedOrder?.total_amount ?? 0) - (selectedOrder?.discount_amount ?? 0)) }}
                                            (khách trả thêm {{ formatCurrency(selectedOrder?.shipping_fee ?? 0) }} ship cho shipper)
                                        </span>
                                        <span v-else>
                                            {{ selectedOrder?.payment?.payment_method === 'CASH' ? 'Khách đã đưa' : 'Đã nhận đủ' }}: {{ formatCurrency(selectedOrder?.final_amount) }}
                                        </span>
                                    </p>
                                    <div class="flex gap-2">
                                        <button @click="showPaymentConfirm = false"
                                            class="flex-1 py-2.5 rounded-xl border border-outline-variant/40 font-bold text-[13px] text-on-surface-variant hover:bg-surface-container transition-all">
                                            Huỷ
                                        </button>
                                        <button @click="confirmPayment(selectedOrder.id)"
                                            class="flex-1 py-2.5 rounded-xl bg-secondary text-on-secondary font-bold text-[13px] hover:bg-secondary/90 transition-all shadow-sm">
                                            <span class="flex items-center justify-center gap-1.5">
                                                <span class="material-symbols-outlined text-[16px]">check</span> Xác
                                                nhận
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Xác nhận huỷ đơn -->
                            <template v-if="selectedOrder?.status === 'PENDING'">
                                <div v-if="showCancelConfirm"
                                    class="rounded-xl border-2 border-error/30 bg-error/5 p-4 space-y-3">
                                    <div class="flex items-center gap-2 text-error">
                                        <span class="material-symbols-outlined text-[20px]">warning</span>
                                        <p class="text-[13px] font-bold">Xác nhận huỷ đơn?</p>
                                    </div>
                                    <p class="text-[12px] text-on-surface-variant">Hành động này sẽ huỷ đơn hàng #{{
                                        selectedOrder?.id }} và không thể hoàn tác.</p>
                                    <div class="flex gap-2">
                                        <button @click="showCancelConfirm = false"
                                            class="flex-1 py-2.5 rounded-xl border border-outline-variant/40 font-bold text-[13px] text-on-surface-variant hover:bg-surface-container transition-all">
                                            Quay lại
                                        </button>
                                        <button @click="cancelOrder(selectedOrder.id)"
                                            class="flex-1 py-2.5 rounded-xl bg-error text-on-error font-bold text-[13px] hover:bg-error/90 transition-all shadow-sm">
                                            <span class="flex items-center justify-center gap-1.5">
                                                <span class="material-symbols-outlined text-[16px]">check</span> Huỷ đơn
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Nút thao tác -->
                            <div v-if="!showCancelConfirm && !showPaymentConfirm"
                                class="flex gap-3 justify-end flex-wrap">
                                <button @click="closeOrderModal"
                                    class="px-5 py-2 rounded-xl text-[13px] font-bold hover:bg-surface-container text-on-surface-variant transition-colors">Đóng
                                    lại</button>
                                <button v-if="selectedOrder?.status === 'PENDING'" @click="showCancelConfirm = true"
                                    class="px-5 py-2 rounded-xl font-bold text-[13px] flex items-center gap-2 transition-all border bg-error/5 text-error border-error/20 hover:bg-error/10">
                                    <span class="material-symbols-outlined text-[18px]">cancel</span> Hủy đơn
                                </button>
                                <button v-if="selectedOrder?.status === 'READY' && selectedOrder?.order_type === 'DELIVERY'" @click="startDelivering(selectedOrder.id)"
                                    class="px-5 py-2 rounded-xl bg-primary text-on-primary font-bold text-[13px] hover:bg-primary/90 flex items-center gap-2 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">delivery_dining</span> Đưa shipper
                                </button>
                                <button v-if="selectedOrder?.status === 'PENDING'"
                                    @click="acceptOrder(selectedOrder.id)"
                                    class="px-6 py-2 rounded-xl font-bold text-[13px] text-on-primary flex items-center gap-2 transition-all shadow-sm hover:opacity-90 bg-primary">
                                    <span class="material-symbols-outlined text-[18px]">check_circle</span> Tiếp nhận
                                    đơn
                                </button>
                                <!-- Nút Hoàn thành: chỉ bấm được khi đã thanh toán VÀ tất cả món đã pha xong -->
                                <button v-else-if="selectedOrder?.status === 'PROCESSING'"
                                    @click="(() => {
                                        const allDone = selectedOrder?.details?.every(d => d.barista_status === 'COMPLETED');
                                        const paid = selectedOrder?.payment?.payment_status === 'PAID';
                                        if (!allDone) { toast.warning('Chưa pha xong hết các món, không thể hoàn thành!'); }
                                        else if (!paid) { toast.warning('Đơn chưa được thanh toán, không thể hoàn thành!'); }
                                        else { completeOrder(selectedOrder.id); }
                                    })()"
                                    :class="(selectedOrder?.payment?.payment_status === 'PAID' && selectedOrder?.details?.every(d => d.barista_status === 'COMPLETED'))
                                        ? 'bg-secondary text-on-secondary hover:bg-secondary/90 shadow-sm cursor-pointer'
                                        : 'bg-surface-container-high text-on-surface-variant/50 border border-outline-variant/30 cursor-not-allowed'"
                                    class="px-6 py-2 rounded-xl font-bold text-[13px] flex items-center gap-2 transition-all">
                                    <span class="material-symbols-outlined text-[18px]">task_alt</span> Hoàn thành
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Modal chi tiết bàn -->
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
                                    <h3 class="font-serif text-headline-sm font-bold text-on-surface truncate">{{
                                        selectedTable?.table_name }}</h3>
                                    <p
                                        class="text-[12px] text-on-surface-variant mt-0.5 uppercase tracking-wider truncate">
                                        {{ selectedTable?.area }} • {{ selectedTable?.capacity }} người
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider whitespace-nowrap"
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
                            <template
                                v-if="selectedTable?.status === 'OCCUPIED' && selectedTable?.orders && selectedTable.orders.length > 0">
                                <div class="rounded-xl border border-outline-variant/20 overflow-hidden">
                                    <div
                                        class="bg-surface-container-low px-4 py-2.5 flex justify-between items-center border-b border-outline-variant/20">
                                        <span
                                            class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
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
                                                    <p class="text-[14px] text-on-surface font-medium">{{
                                                        detail.product?.product_name }}</p>
                                                    <p class="text-[12px] text-on-surface-variant mt-0.5">
                                                        Size {{ detail.variant?.size || '---' }} • {{
                                                            formatCurrency(detail.unit_price) }}
                                                    </p>
                                                </div>
                                                <span class="font-bold text-on-surface text-[14px] flex-shrink-0">×{{
                                                    detail.quantity }}</span>
                                            </div>
                                        </div>

                                        <div
                                            class="flex justify-between items-center pt-2 border-t border-outline-variant/20">
                                            <span class="text-[13px] text-on-surface-variant font-medium">Tổng
                                                cộng:</span>
                                            <span class="font-bold text-primary text-label-lg">
                                                {{ formatCurrency(calculateTotalAmount(selectedTable.orders)) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between items-center">
                                            <span class="text-[13px] text-on-surface-variant">Thanh toán:</span>
                                            <span
                                                class="text-[11px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider"
                                                :class="selectedTable.orders.every(o => o.payment?.payment_status === 'PAID')
                                                    ? 'bg-secondary-container text-secondary'
                                                    : 'bg-error/10 text-error border border-error/20'">
                                                {{selectedTable.orders.every(o => o.payment?.payment_status === 'PAID')
                                                    ? '✓ Đã thanh toán'
                                                    : selectedTable.orders.some(o => o.payment?.payment_status === 'PAID')
                                                        ? '◑ Một phần'
                                                        : '○ Chưa thanh toán'}}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Trạng thái trống -->
                            <div v-if="selectedTable?.status === 'EMPTY'"
                                class="flex items-center gap-3 p-4 rounded-xl bg-surface-container-low border border-outline-variant/20">
                                <span
                                    class="material-symbols-outlined text-on-surface-variant/40 text-[24px]">chair</span>
                                <p class="text-[13px] text-on-surface-variant">Bàn đang trống, chưa có khách.</p>
                            </div>

                            <!-- Các nút thao tác -->
                            <div class="space-y-2.5">
                                <button v-if="isTableParent(selectedTable)" @click="unmergeTable(selectedTable)"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border border-error text-error font-bold text-label-md hover:bg-error-container/30 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">call_split</span> Tách bàn về như cũ
                                </button>
                                <button
                                    v-if="selectedTable?.status === 'OCCUPIED' && selectedTable?.orders && selectedTable.orders.length > 0"
                                    @click="printBill(selectedTable)"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-surface-container-high border border-outline-variant/30 text-on-surface font-bold text-label-md hover:bg-surface-container-highest transition-all">
                                    <span class="material-symbols-outlined text-[20px]">print</span> In hóa đơn
                                </button>
                                <button v-if="selectedTable?.status === 'EMPTY'"
                                    @click="updateTableStatus(selectedTable.id, 'OCCUPIED')"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-primary text-on-primary font-bold text-label-md hover:bg-primary/90 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">login</span> Khách vào bàn
                                </button>
                                <!-- Xác nhận dọn bàn: 2 bước -->
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
                                        <span class="material-symbols-outlined text-[18px]">cleaning_services</span>
                                        Khách về — Dọn bàn
                                    </button>
                                    <div v-else class="rounded-xl border-2 border-error/30 bg-error/5 p-4 space-y-3">
                                        <div class="flex items-center gap-2 text-error">
                                            <span class="material-symbols-outlined text-[20px]">warning</span>
                                            <p class="text-[13px] font-bold">Xác nhận dọn bàn?</p>
                                        </div>
                                        <p class="text-[12px] text-on-surface-variant">Hành động này sẽ chuyển bàn về
                                            trạng thái trống và xoá các đơn hàng liên kết.</p>
                                        <div class="flex gap-2">
                                            <button @click="showCleanConfirm = false"
                                                class="flex-1 py-2.5 rounded-xl border border-outline-variant/40 font-bold text-[13px] text-on-surface-variant hover:bg-surface-container transition-all">
                                                Huỷ
                                            </button>
                                            <button @click="updateTableStatus(selectedTable.id, 'EMPTY')"
                                                class="flex-1 py-2.5 rounded-xl bg-error text-on-error font-bold text-[13px] hover:bg-error/90 transition-all shadow-sm">
                                                <span class="flex items-center justify-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[16px]">check</span> Xác
                                                    nhận
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

    </StaffLayout>
</template>

<style scoped>
/* Thống kê tổng quan */
.stat-chip {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 20px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    min-width: 160px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
}

.stat-chip-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background-image: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 100%);
}

.stat-num {
    font-size: 24px;
    font-weight: 800;
    line-height: 1.1;
    color: #334155;
    /* Neutral dark color, can be overridden inline */
}

.stat-label {
    font-size: 13px;
    color: #64748B;
    margin-top: 4px;
    font-weight: 500;
    white-space: nowrap;
}

/* Floor plan */
.floor-plan-wrap {
    position: relative;
}

.coffee-bar-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #8D6E63;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.15em;
    padding: 8px 20px;
    text-transform: uppercase;
}

.area-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(4px);
    border: 1px solid #C8A97E50;
    border-radius: 99px;
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 700;
    color: #8D6E63;
    text-transform: uppercase;
    letter-spacing: .1em;
}

/* Plant corners */
.plant-tl,
.plant-tr,
.plant-bl,
.plant-br {
    position: absolute;
    font-size: 28px;
    opacity: 0.6;
    pointer-events: none;
}

.plant-tl {
    top: 40px;
    left: 12px;
}

.plant-tr {
    top: 40px;
    right: 12px;
    transform: scaleX(-1);
}

.plant-bl {
    bottom: 12px;
    left: 12px;
}

.plant-br {
    bottom: 12px;
    right: 12px;
    transform: scaleX(-1);
}



/* Legend */
.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #8D6E63;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Order card */
.order-card {
    background: #FAF6F0;
}

/* Transitions */
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

/* Hiệu ứng danh sách trượt mượt mà */
.list-move,
.list-enter-active,
.list-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.list-enter-from {
    opacity: 0;
    transform: translateY(30px) scale(0.98);
}
.list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
.list-leave-active {
    position: absolute;
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
