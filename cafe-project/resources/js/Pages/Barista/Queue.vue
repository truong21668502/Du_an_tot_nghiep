<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

import BaristaLayout from '../../Layouts/BaristaLayout.vue';
import QueueCard from './Partials/QueueCard.vue';
import RecipePanel from './Partials/RecipePanel.vue';
import StatsBar from './Partials/StatsBar.vue';

// ===== Props từ Controller =====
const props = defineProps({
    initialQueue: { type: Array, default: () => [] },
    todayDone: { type: Number, default: 0 },
});

// ===== State =====
const queue = ref(props.initialQueue || []);
const doneSoFar = ref(props.todayDone || 0);
const selected = ref(null); // món đang chọn để xem công thức

// ===== Computed stats =====
const pendingCount = computed(() => queue.value.filter(d => d.barista_status === 'PENDING').length);
const preparingCount = computed(() => queue.value.filter(d => d.barista_status === 'PREPARING').length);

// Nhóm món theo Đơn hàng (Order)
const groupedOrders = computed(() => {
    const groups = {};
    queue.value.forEach(detail => {
        if (!groups[detail.order_id]) {
            groups[detail.order_id] = {
                order: detail.order,
                details: [],
            };
        }
        groups[detail.order_id].details.push(detail);
    });

    const arr = Object.values(groups);
    // Sắp xếp đơn cũ nhất lên trên
    arr.sort((a, b) => new Date(a.order.created_at) - new Date(b.order.created_at));

    // Sắp xếp món trong đơn: PREPARING -> PENDING -> COMPLETED
    arr.forEach(group => {
        group.details.sort((a, b) => {
            const statusOrder = { PREPARING: 0, PENDING: 1, COMPLETED: 2 };
            return (statusOrder[a.barista_status] ?? 3) - (statusOrder[b.barista_status] ?? 3);
        });
    });

    return arr;
});

// ===== Chọn món =====
const selectDetail = (detail) => {
    selected.value = selected.value?.id === detail.id ? null : detail;
};

const closePanel = () => { selected.value = null; };

// ===== Quản lý trạng thái mở/đóng (Dropdown) của từng đơn =====
const expandedOrders = ref({}); // object map order_id -> boolean

const isOrderExpanded = (orderId) => {
    // Mặc định đóng nếu chưa được set true
    return expandedOrders.value[orderId] === true;
};

const toggleOrder = (orderId) => {
    expandedOrders.value[orderId] = !isOrderExpanded(orderId);
};

// ===== Cập nhật trạng thái (PENDING → PREPARING → COMPLETED) =====
const advanceStatus = async (detail) => {
    try {
        const res = await axios.patch(route('barista.detail.update-status', detail.id));
        const newStatus = res.data.barista_status;

        const idx = queue.value.findIndex(d => d.id === detail.id);
        if (idx !== -1) {
            // Thay object để Vue nhận biết thay đổi chắc chắn
            queue.value[idx] = { ...queue.value[idx], barista_status: newStatus };

            // Cập nhật selected nếu đang chọn món này
            if (selected.value?.id === detail.id) {
                selected.value = queue.value[idx];
            }

            if (newStatus === 'COMPLETED') {
                doneSoFar.value++;
                toast.success(`Đã hoàn thành: ${detail.product?.product_name}`, { autoClose: 2500 });
                
                // Tìm món tiếp theo để tự động chuyển panel công thức
                const wasSelected = selected.value?.id === detail.id;
                
                setTimeout(() => {
                    queue.value = queue.value.filter(d => d.id !== detail.id);
                    
                    // Nếu đang xem công thức của món vừa hoàn thành → tự động chuyển sang món kế tiếp
                    if (wasSelected) {
                        const nextItem = queue.value.find(d => 
                            d.barista_status === 'PREPARING' || d.barista_status === 'PENDING'
                        );
                        selected.value = nextItem || null;
                    }
                }, 1500);
            } else {
                toast.info(`Đang pha: ${detail.product?.product_name}`, { autoClose: 2000 });
            }
        }
    } catch (err) {
        console.error('Lỗi cập nhật trạng thái:', err);
        const errorMsg = err.response?.data?.error || 'Không thể cập nhật trạng thái!';
        toast.error(errorMsg);
    }
};

// ===== Real-time: lắng nghe đơn mới + cập nhật trạng thái =====
onMounted(() => {
    if (!window.Echo) return;

    // Reload queue khi có đơn mới hoặc cập nhật trạng thái đơn (để lấy danh sách công thức đầy đủ từ controller)
    window.Echo.channel('staff-orders')
        .listen('.order.created', (e) => {
            // Pha chế chỉ nhận được đơn khi nhân viên thu ngân bấm "Tiếp nhận" (chuyển sang PROCESSING)
        })
        .listen('.order.status-updated', (e) => {
            router.reload({
                only: ['initialQueue'],
                preserveScroll: true,
                onSuccess: (page) => {
                    queue.value = page.props.initialQueue;
                    if (e.order?.status === 'PROCESSING') {
                        toast.info(`Đơn mới tiếp nhận! Bàn ${e.order.table?.table_name || 'mang đi'}`, {
                            position: 'top-right',
                            autoClose: 4000,
                        });
                    }
                }
            });
        });

    // Lắng nghe trạng thái đơn bị hủy
    window.Echo.channel('staff-orders')
        .listen('.order.cancelled', (e) => {
            queue.value = queue.value.filter(d => d.order_id !== e.id);
            if (selected.value && selected.value.order_id === e.id) {
                selected.value = null;
            }
        });
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel('staff-orders');
    }
});
</script>

<template>

    <Head title="Hàng đợi pha chế - Barista" />

    <BaristaLayout :pending-count="pendingCount">

        <div class="p-4 md:p-8 w-full">

            <!-- ===== Header ===== -->
            <div class="mb-6 flex items-end justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-block w-1.5 h-5 rounded-full bg-primary"></span>
                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-primary">Barista Station</p>
                    </div>
                    <h2 class="text-[28px] font-bold text-on-surface font-serif leading-tight">Hàng đợi pha chế</h2>
                </div>

                <!-- Live badge -->
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-error/10 border border-error/20 text-error text-[11px] font-bold"
                    v-if="pendingCount > 0">
                    <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                    {{ pendingCount }} món đang chờ
                </div>
                <div v-else
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-secondary/10 border border-secondary/20 text-secondary text-[13px] font-bold">
                    <span class="material-symbols-outlined text-[16px]"
                        style="font-variation-settings:'FILL' 1">check_circle</span>
                    Không có đơn chờ
                </div>
            </div>

            <!-- ===== Stats Bar ===== -->
            <div class="mb-6">
                <StatsBar :pending-count="pendingCount" :preparing-count="preparingCount" :today-done="doneSoFar" />
            </div>

            <!-- ===== Layout: Queue + Recipe Panel ===== -->
            <div class="flex gap-5 items-start">

                <!-- Danh sách Đơn hàng -->
                <div class="transition-all duration-500 ease-in-out"
                    :class="selected ? 'w-full lg:w-[420px] flex-shrink-0' : 'w-full'">

                    <!-- Trống -->
                    <div v-if="groupedOrders.length === 0"
                        class="flex flex-col items-center justify-center py-20 rounded-2xl border border-dashed border-outline-variant/30 bg-surface-container-lowest text-center">
                        <span class="material-symbols-outlined text-[52px] text-on-surface-variant/30 mb-3"
                            style="font-variation-settings:'FILL' 1">local_cafe</span>
                        <p class="text-[15px] font-bold text-on-surface-variant/60">Tuyệt vời! Không còn đơn nào chờ.
                        </p>
                        <p class="text-[12px] text-on-surface-variant/40 mt-1">Hàng đợi trống — thư giãn một chút nhé ☕
                        </p>
                    </div>

                    <!-- Nhóm theo đơn -->
                    <TransitionGroup v-else tag="div" name="list" class="flex flex-col gap-5">
                        <div v-for="group in groupedOrders" :key="group.order.id"
                            class="bg-surface rounded-2xl border border-outline-variant/30 overflow-hidden shadow-sm transition-all duration-300 hover:shadow-md">

                            <!-- Header Đơn hàng -->
                            <div @click="toggleOrder(group.order.id)"
                                class="px-5 py-3 bg-surface-container-lowest border-b border-outline-variant/20 flex items-center justify-between cursor-pointer hover:bg-surface-container-low transition-colors">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="material-symbols-outlined text-on-surface-variant transition-transform duration-300"
                                        :class="isOrderExpanded(group.order.id) ? 'rotate-180' : ''">expand_more</span>
                                    <div>
                                        <h3 class="text-[15px] font-bold text-on-surface flex items-center gap-1.5">
                                            Đơn #{{ group.order.id }} — {{ group.order.table?.table_name || 'Mang đi' }}
                                            <span v-if="group.details.some(d => d.barista_status === 'PENDING')" 
                                                  class="material-symbols-outlined text-error text-[18px] animate-wiggle"
                                                  title="Có món mới chờ pha">
                                                notifications_active
                                            </span>
                                        </h3>
                                        <p class="text-[12px] text-on-surface-variant mt-0.5">{{ new
                                            Date(group.order.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit',
                                            minute: '2-digit' }) }} • {{ group.details.length }} món</p>
                                    </div>
                                </div>
                                <div class="px-3 py-1 rounded-full bg-primary/10 text-primary text-[12px] font-bold">
                                    {{group.details.filter(d => d.barista_status === 'COMPLETED').length}} / {{
                                    group.details.length }} hoàn thành
                                </div>
                            </div>

                            <!-- Danh sách món trong đơn -->
                            <Transition name="dropdown">
                                <div v-show="isOrderExpanded(group.order.id)">
                                    <div class="p-4 grid grid-cols-1 gap-3 transition-all duration-500"
                                        :class="selected ? '' : 'sm:grid-cols-2 xl:grid-cols-3'">
                                        <QueueCard v-for="detail in group.details" :key="detail.id" :detail="detail"
                                            :is-selected="selected?.id === detail.id" @select="selectDetail"
                                            @advance="advanceStatus" />
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </TransitionGroup>
                </div>

                <!-- Recipe Panel (phải, khi có món được chọn) -->
                <Transition name="slide-in">
                    <div v-if="selected" class="flex-1 min-w-0 transition-all duration-500">
                        <div class="lg:sticky lg:top-24" style="height: calc(100vh - 120px);">
                            <RecipePanel :detail="selected" @advance="advanceStatus" @close="closePanel" />
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </BaristaLayout>
</template>

<style scoped>
.slide-in-enter-active,
.slide-in-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-in-enter-from,
.slide-in-leave-to {
    opacity: 0;
    transform: translateX(40px);
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

/* Hiệu ứng Dropdown (Accordion) */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    max-height: 2000px;
    /* Số đủ lớn để chứa nội dung */
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin-top: 0;
    margin-bottom: 0;
}
</style>