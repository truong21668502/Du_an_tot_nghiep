<script setup>
import { ref, watch, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';


const props = defineProps({
    initialOrders: Array,
});

const orders = ref(props.initialOrders || []);
const selectedOrder = ref(null);
const isOrderModalOpen = ref(false);
const filterStatus = ref('ALL');
const vnpayQrUrl = ref(null);

// Đồng bộ dữ liệu khi Inertia reload props
watch(() => props.initialOrders, (newVal) => {
    if (newVal) orders.value = newVal;
}, { deep: true });

const openOrderDetails = async (order) => {
    selectedOrder.value = order;
    isOrderModalOpen.value = true;

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
            if (selectedOrder.value?.id === orderId) {
                await nextTick();
                selectedOrder.value = orders.value[index];
            }
        }
        toast.success(`Đã tiếp nhận đơn hàng #${orderId}`);
    } catch (error) {
        console.error('Lỗi tiếp nhận đơn:', error);
        toast.error('Không thể tiếp nhận đơn hàng này!');
    }
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

const cancelOrder = (orderId) => {
    if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?')) {
        router.patch(route('staff.orders.cancel', orderId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                orders.value = orders.value.filter(o => o.id !== orderId);
                closeOrderModal();
            }
        });
    }
};

const confirmPayment = (orderId) => {
    if (confirm(selectedOrder.value?.payment?.payment_method === 'CASH' ? 'Khách đã thanh toán tiền mặt xong?' : 'Đã nhận đủ tiền chuyển khoản?')) {
        router.patch(route('staff.orders.confirm-payment', orderId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                const orderIndex = orders.value.findIndex(o => o.id === orderId);
                if (orderIndex !== -1 && orders.value[orderIndex].payment) {
                    orders.value[orderIndex].payment.payment_status = 'PAID';
                }
                if (selectedOrder.value?.id === orderId && selectedOrder.value.payment) {
                    selectedOrder.value.payment.payment_status = 'PAID';
                }
                closeOrderModal();
            }
        });
    }
}

// ===== ECHO (giữ nguyên, không xóa dù chưa xác nhận hoạt động) =====
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => {
                // Reload dữ liệu đầy đủ từ server (event chỉ gửi thông tin tối thiểu)
                router.reload({
                    only: ['initialOrders'],
                    preserveScroll: true,
                });
            })
            .listen('.order.status-updated', (e) => {
                // Reload dữ liệu đầy đủ từ server
                router.reload({
                    only: ['initialOrders'],
                    preserveScroll: true,
                    onSuccess: () => {
                        if (['COMPLETED', 'CANCELLED'].includes(e.order.status)) {
                            if (selectedOrder.value && selectedOrder.value.id === e.order.id) {
                                closeOrderModal();
                            }
                        }
                    }
                });
            })
            .listen('.order.payment-confirmed', (e) => {
                const index = orders.value.findIndex(o => o.id === e.id);
                if (index !== -1 && orders.value[index].payment) {
                    orders.value[index].payment.payment_status = e.payment_status;
                    orders.value[index].status = e.status;
                }
            })
            .listen('.order.cancelled', (e) => {
                orders.value = orders.value.filter(o => o.id !== e.id);
            })
            .listen('.barista.detail.updated', (e) => {
                const oIndex = orders.value.findIndex(o => o.id === e.order_id);
                if (oIndex !== -1 && orders.value[oIndex].details) {
                    const dIndex = orders.value[oIndex].details.findIndex(d => d.id === e.id);
                    if (dIndex !== -1) {
                        orders.value[oIndex].details[dIndex].barista_status = e.barista_status;
                    }
                }
                if (selectedOrder.value?.id === e.order_id && selectedOrder.value.details) {
                    const dIndex = selectedOrder.value.details.findIndex(d => d.id === e.id);
                    if (dIndex !== -1) {
                        selectedOrder.value.details[dIndex].barista_status = e.barista_status;
                    }
                }
            });
    }
});

// ===== POLLING — phương án chắc chắn hoạt động, không phụ thuộc Echo/Reverb =====
let pollInterval = null;

onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({
            only: ['initialOrders'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const freshOrders = page.props.initialOrders || [];
                orders.value = freshOrders;

                // Nếu đang mở modal xem chi tiết 1 đơn, đồng bộ lại theo dữ liệu mới
                if (selectedOrder.value) {
                    const stillExists = freshOrders.find(o => o.id === selectedOrder.value.id);
                    if (stillExists) {
                        selectedOrder.value = stillExists;
                    } else {
                        // Đơn không còn trong danh sách active (đã COMPLETED/CANCELLED) → đóng modal
                        closeOrderModal();
                    }
                }
            },
        });
    }, 4000);
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel('staff-orders');
    }
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

const filteredOrders = computed(() => {
    if (filterStatus.value === 'ALL') return orders.value;
    return orders.value.filter(o => o.status === filterStatus.value);
});

const pendingCount = computed(() => orders.value.filter(o => o.status === 'PENDING').length);
const processingCount = computed(() => orders.value.filter(o => o.status === 'PROCESSING').length);
</script>

<template>

    <Head title="Đơn hàng - Nắng Coffee" />

    <StaffLayout>
        <!-- Page Header -->
        <div class="mb-8 flex items-end justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-block w-1.5 h-5 rounded-full bg-primary"></span>
                    <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-primary">Quản lý</p>
                </div>
                <h2 class="text-display-lg-mobile md:text-display-lg text-on-background font-serif">Đơn Hàng</h2>
            </div>

            <div class="flex items-center gap-2 pb-1">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold bg-error/10 text-error border border-error/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-error"
                        :class="pendingCount > 0 ? 'animate-pulse' : ''"></span>
                    {{ pendingCount }} chờ xử lý
                </span>
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold bg-primary/10 text-primary border border-primary/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                    {{ processingCount }} đang xử lý
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 mb-6 flex-wrap">
            <button v-for="tab in [
                { label: 'Tất cả', value: 'ALL', count: orders.length },
                { label: 'Chờ xử lý', value: 'PENDING', count: pendingCount },
                { label: 'Đang xử lý', value: 'PROCESSING', count: processingCount }
            ]" :key="tab.value" @click="filterStatus = tab.value"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-label-sm font-bold transition-all duration-200 border"
                :class="filterStatus === tab.value
                    ? 'bg-primary text-on-primary border-primary shadow-sm'
                    : 'bg-surface-container-low text-on-surface-variant border-outline-variant/20 hover:border-outline-variant/50 hover:bg-surface-container-high'">
                {{ tab.label }}
                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold min-w-[18px] text-center"
                    :class="filterStatus === tab.value ? 'bg-white/20 text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <div v-if="filteredOrders.length === 0"
            class="rounded-2xl p-14 flex flex-col items-center justify-center text-center bg-surface-container-low border border-outline-variant/20">
            <div class="w-20 h-20 rounded-3xl bg-surface-container-high flex items-center justify-center mb-5">
                <span class="material-symbols-outlined text-on-surface-variant/30 text-[40px]">receipt_long</span>
            </div>
            <h3 class="text-headline-sm font-bold text-on-surface mb-2">Tuyệt vời!</h3>
            <p class="text-body-md text-on-surface-variant max-w-xs">
                {{ filterStatus === 'ALL' ? 'Hiện tại không có đơn hàng nào cần xử lý.' : `Không có đơn hàng nào với
                trạng thái này.` }}
            </p>
        </div>

        <TransitionGroup name="list" tag="div" v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="order in filteredOrders" :key="order.id" @click="openOrderDetails(order)"
                class="group relative overflow-hidden rounded-2xl border cursor-pointer transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
                :class="order.status === 'PENDING'
                    ? 'bg-surface-container-low border-error/30 hover:border-error/60'
                    : 'bg-surface-container-low border-outline-variant/20 hover:border-primary/40'">

                <div class="absolute top-0 left-0 right-0 h-0.5 rounded-t-2xl"
                    :class="order.status === 'PENDING' ? 'bg-error' : 'bg-primary'"></div>

                <div class="p-5">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold font-mono text-label-sm flex-shrink-0"
                            :class="order.status === 'PENDING' ? 'bg-error/10 text-error' : 'bg-primary/10 text-primary'">
                            #{{ order.id }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-label-md font-bold text-on-surface truncate">
                                {{ order.table ? order.table.table_name : 'Mang đi / Giao hàng' }}
                            </h4>
                            <p class="text-[11px] text-on-surface-variant mt-0.5 uppercase tracking-wider">{{
                                order.order_type }}</p>
                        </div>
                        <span v-if="order.status === 'PENDING'"
                            class="flex-shrink-0 inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full bg-error/10 text-error border border-error/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>Chờ xử lý
                        </span>
                        <span v-else-if="order.status === 'PROCESSING'"
                            class="flex-shrink-0 inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full bg-primary/10 text-primary border border-primary/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>Đang xử lý
                        </span>
                    </div>

                    <ul class="space-y-1.5 mb-4">
                        <li v-for="detail in order.details" :key="detail.id" class="flex items-start gap-2 text-[12px]">
                            <span class="font-bold text-primary mt-px">{{ detail.quantity }}×</span>
                            <span class="text-on-surface flex-1 truncate">{{ detail.product?.product_name }}</span>
                        </li>
                    </ul>

                    <div class="flex items-center justify-between pt-3 border-t border-outline-variant/15">
                        <span class="text-headline-sm font-bold text-on-surface">{{ formatCurrency(order.final_amount)
                            }}</span>
                        <span
                            class="text-[11px] text-on-surface-variant/60 flex items-center gap-1 group-hover:text-primary transition-colors">
                            Chi tiết
                            <span
                                class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </div>
        </TransitionGroup>

        <Transition name="fade">
            <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeOrderModal"></div>
                <Transition name="slide-up">
                    <div v-if="isOrderModalOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[90vh] border border-outline-variant/20">

                        <div
                            class="px-6 py-4 border-b border-outline-variant/20 flex justify-between items-center bg-surface">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-label-sm font-bold font-mono flex-shrink-0"
                                    :class="selectedOrder?.status === 'PENDING' ? 'bg-error/10 text-error' : 'bg-primary/10 text-primary'">
                                    #{{ selectedOrder?.id }}
                                </div>
                                <div>
                                    <h3 class="text-label-lg font-bold text-on-background">Chi tiết đơn hàng</h3>
                                    <p class="text-[11px] text-on-surface-variant uppercase tracking-wider">{{
                                        selectedOrder?.order_type }}</p>
                                </div>
                            </div>
                            <button @click="closeOrderModal"
                                class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container hover:text-error transition-all">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <div class="p-5 overflow-y-auto flex-1 space-y-4 hide-scrollbar">

                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-surface rounded-xl p-4 border border-outline-variant/20">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">
                                        Vị trí / Khách</p>
                                    <p class="text-label-lg font-bold text-on-surface">
                                        {{ selectedOrder?.table ? selectedOrder.table.table_name : 'Khách mang đi' }}
                                    </p>
                                </div>
                                <div
                                    class="bg-surface rounded-xl p-4 border border-outline-variant/20 flex flex-col items-end justify-between">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1 self-start">
                                        Trạng thái</p>
                                    <span v-if="selectedOrder?.status === 'PENDING'"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-error/10 text-error border border-error/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>Chờ xử lý
                                    </span>
                                    <span v-else-if="selectedOrder?.status === 'PROCESSING'"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-primary/10 text-primary border border-primary/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>Đang xử lý
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-outline-variant/20 overflow-hidden">
                                <div
                                    class="bg-surface-container-low px-4 py-2.5 flex justify-between items-center border-b border-outline-variant/20">
                                    <span
                                        class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Danh
                                        sách món</span>
                                    <span
                                        class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Trạng
                                        thái pha chế</span>
                                </div>
                                <ul class="divide-y divide-outline-variant/15">
                                    <li v-for="detail in selectedOrder?.details" :key="detail.id"
                                        class="p-4 flex items-center justify-between gap-4 hover:bg-surface-container-low/40 transition-colors">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-body-md text-on-surface">
                                                <span class="font-bold text-primary mr-1.5">{{ detail?.quantity
                                                    }}×</span>
                                                {{ detail?.product?.product_name }}
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
                                                'bg-surface-container-high border-outline/20 text-on-surface-variant': detail?.barista_status === 'PENDING',
                                                'bg-secondary-container border-secondary/20 text-on-secondary-container': detail?.barista_status === 'PREPARING',
                                                'bg-primary-container border-primary/20 text-on-primary-container': detail?.barista_status === 'COMPLETED',
                                                'bg-error-container border-error/20 text-on-error-container': detail?.barista_status === 'CANCELLED'
                                            }">
                                            {{ detail?.barista_status === 'PENDING' ? '⏳ Chờ pha'
                                                : detail?.barista_status === 'PREPARING' ? '☕ Đang làm'
                                                    : detail?.barista_status === 'COMPLETED' ? '✓ Đã xong'
                                                        : '✕ Đã hủy' }}
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="selectedOrder?.payment?.payment_method !== 'CASH' && selectedOrder?.payment?.payment_status === 'PENDING'"
                                class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-outline-variant/30 mt-3 shadow-sm">
                                <p class="text-[13px] font-bold text-[#005BAA] mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">qr_code_scanner</span> Quét mã
                                    thanh toán VNPay
                                </p>
                                <div v-if="vnpayQrUrl" class="relative">
                                    <img :src="vnpayQrUrl" alt="VNPay QR"
                                        class="w-full h-auto object-contain border p-1 shadow-sm" />
                                </div>
                                <div v-else
                                    class="w-40 h-40 flex items-center justify-center bg-gray-50 animate-pulse border border-outline-variant/30">
                                    <span class="material-symbols-outlined text-gray-300 text-[32px]">qr_code</span>
                                </div>
                                <p class="text-[14px] text-error font-bold mt-3">Số tiền: {{
                                    formatCurrency(selectedOrder?.final_amount) }}</p>
                            </div>

                            <div
                                class="flex justify-between items-center p-4 bg-primary/5 rounded-xl border border-primary/15">
                                <span class="text-body-md text-on-surface-variant font-medium">Tổng thanh toán:</span>
                                <span class="text-headline-sm text-primary font-bold">{{
                                    formatCurrency(selectedOrder?.final_amount) }}</span>
                            </div>
                        </div>

                        <div
                            class="px-5 py-4 bg-surface border-t border-outline-variant/20 flex gap-3 justify-end flex-wrap">
                            <button @click="closeOrderModal"
                                class="px-5 py-2 rounded-xl text-label-md font-bold text-on-surface-variant hover:bg-surface-container transition-colors">
                                Đóng lại
                            </button>
                            <button v-if="selectedOrder?.payment?.payment_status === 'PENDING'"
                                @click="confirmPayment(selectedOrder.id)"
                                class="px-5 py-2 rounded-xl bg-emerald-600/10 text-emerald-700 border border-emerald-600/30 font-bold text-label-md hover:bg-emerald-600/20 flex items-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-[18px]">payments</span>
                                {{
                                    selectedOrder?.payment?.payment_method === 'CASH'
                                        ? 'Thu tiền mặt' : 'Xác nhận đã thanh toán'
                                }}
                            </button>
                            <button v-if="selectedOrder?.status === 'PENDING'" @click="cancelOrder(selectedOrder.id)"
                                class="px-5 py-2 rounded-xl bg-error/10 text-error border border-error/30 font-bold text-label-md hover:bg-error/20 flex items-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-[18px]">cancel</span> Hủy đơn
                            </button>
                            <button v-if="selectedOrder?.status === 'PENDING'" @click="acceptOrder(selectedOrder.id)"
                                class="px-6 py-2 rounded-xl bg-primary text-on-primary font-bold text-label-md hover:bg-primary/90 flex items-center gap-2 transition-all shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span> Tiếp nhận đơn
                            </button>
                            <!-- Nút Hoàn thành: chỉ bấm được khi đã thanh toán -->
                            <button v-else-if="selectedOrder?.status === 'PROCESSING'"
                                @click="selectedOrder?.payment?.payment_status === 'PAID' ? completeOrder(selectedOrder.id) : toast.warning('⚠️ Đơn chưa được thanh toán, không thể hoàn thành!')"
                                :class="selectedOrder?.payment?.payment_status === 'PAID'
                                    ? 'bg-secondary text-on-secondary hover:bg-secondary/90 shadow-sm cursor-pointer'
                                    : 'bg-surface-container-high text-on-surface-variant/50 border border-outline-variant/30 cursor-not-allowed'"
                                class="px-6 py-2 rounded-xl bg-secondary text-on-secondary font-bold text-label-md flex items-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-[18px]">task_alt</span> Đã hoàn thành
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
    transform: translateX(-30px);
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