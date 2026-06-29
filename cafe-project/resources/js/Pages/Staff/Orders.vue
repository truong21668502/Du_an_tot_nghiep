<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';

const props = defineProps({
    initialOrders: Array,
});

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

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('staff-orders')
            .listen('.order.created', (e) => {
                const exists = orders.value.some(order => order.id === e.order.id);

                if (!exists) {
                    orders.value.push(e.order);
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel('staff-orders');
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};
</script>

<template>
    <Head title="Đơn hàng - Nắng Coffee" />

    <StaffLayout>
        <div class="mb-10">
        <p class="text-label-md text-primary tracking-wider mb-2">QUẢN LÝ</p>
        <h2 class="text-display-lg-mobile md:text-display-lg text-on-background">Đơn Hàng Đang Hoạt Động</h2>
        </div>

        <div v-if="orders.length === 0" class="bg-surface-container-lowest rounded-xl p-10 shadow-soft flex flex-col items-center justify-center border border-outline-variant/30 text-center h-[400px]">
            <span class="material-symbols-outlined text-outline text-[64px] mb-4">receipt_long</span>
            <h3 class="text-headline-sm text-on-surface mb-2">Tuyệt vời!</h3>
            <p class="text-body-md text-on-surface-variant">Hiện tại không có đơn hàng nào đang tồn đọng cần xử lý.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div v-for="order in orders" :key="order.id" @click="openOrderDetails(order)" 
                class="bg-surface-container-lowest rounded-xl p-5 shadow-soft flex flex-col justify-between border-t-4 hover:bg-surface-container-low transition-colors cursor-pointer"
                :class="order.status === 'PENDING' ? 'border-error' : 'border-primary'">
                <div>
                    <div class="flex items-center gap-4 mb-4 border-b border-outline-variant/30 pb-4">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center text-headline-sm font-bold"
                            :class="order.status === 'PENDING' ? 'bg-error-container/30 text-error' : 'bg-primary-container/30 text-primary'">
                            #{{ order.id }}
                        </div>
                        <div class="flex-1">
                            <h4 class="text-headline-sm text-on-surface truncate">{{ order.table ? order.table.table_name : 'Mang đi / Giao hàng' }}</h4>
                            <p class="text-label-md text-on-surface-variant mt-1">{{ order.order_type }}</p>
                        </div>
                        <span v-if="order.status === 'PENDING'" class="text-label-sm text-error bg-error-container text-on-error-container px-3 py-1.5 rounded-full whitespace-nowrap">Chờ xử lý</span>
                        <span v-else-if="order.status === 'PROCESSING'" class="text-label-sm text-primary bg-primary-container text-on-primary-container px-3 py-1.5 rounded-full whitespace-nowrap">Đang xử lý</span>
                    </div>
                    
                    <ul class="space-y-2 mb-4">
                        <li v-for="detail in order.details" :key="detail.id" class="flex justify-between items-start text-body-md">
                            <div class="flex-1 pr-4">
                                <span class="font-bold text-primary">{{ detail.quantity }}x</span> 
                                <span class="text-on-surface ml-1">{{ detail.product?.product_name }}</span>
                                <div v-if="detail.note" class="text-label-sm text-tertiary italic mt-1 pl-5">
                                    * {{ detail.note }}
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="flex items-center justify-between mt-2 pt-4 border-t border-outline-variant/30">
                    <span class="text-label-md text-on-surface-variant">Tổng cộng:</span>
                    <span class="text-headline-sm font-bold text-primary">{{ formatCurrency(order.final_amount) }}</span>
                </div>
            </div>
        </div>

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
                            <button @click="closeOrderModal" class="p-2 text-on-surface-variant hover:bg-surface-container hover:text-error rounded-full"><span class="material-symbols-outlined">close</span></button>
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
                                    <p class="text-label-sm text-on-surface-variant hidden sm:block font-bold">TRẠNG THÁI PHA CHẾ</p>
                                </div>
                                <ul class="divide-y divide-outline-variant/30">
                                    <li v-for="detail in selectedOrder?.details" :key="detail.id" class="p-5 flex flex-col sm:flex-row justify-between sm:items-center gap-4 hover:bg-surface-container-low/30">
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
                            <button @click="closeOrderModal" class="px-6 py-2.5 rounded-full text-label-md font-bold text-on-surface-variant hover:bg-surface-container transition-colors">Đóng lại</button>
                            
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
    </StaffLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(20px) scale(0.95); }
</style>