<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({ order: Object });

// State cục bộ để có thể cập nhật realtime, khác với prop gốc chỉ đọc
const localOrder = ref(props.order);

const cancelReason = ref("");
const showCancelModal = ref(false);
const processing = ref(false);

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('staff-orders')
            .listen('.order.status-updated', (e) => {
                if (e.order.id === localOrder.value.id) {
                    localOrder.value = e.order;
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) window.Echo.leaveChannel('staff-orders');
});

const getNextActions = (order) => {
    if (order.status === "PENDING") {
        return [
            { label: "Xác nhận", value: "PROCESSING" },
            { label: "Hủy đơn", value: "CANCELLED" },
        ];
    }
    if (order.status === "PROCESSING") {
        return [
            { label: "Sẵn sàng", value: "READY" },
            { label: "Hủy đơn", value: "CANCELLED" },
        ];
    }
    if (order.status === "READY") {
        if (order.order_type === "DELIVERY") {
            return [
                { label: "Bắt đầu giao", value: "DELIVERING" },
                { label: "Hủy đơn", value: "CANCELLED" },
            ];
        }
        return [{ label: "Hoàn thành", value: "COMPLETED" }];
    }
    if (order.status === "DELIVERING") {
        return [{ label: "Hoàn thành", value: "COMPLETED" }];
    }
    return [];
};

const updateStatus = (newStatus) => {
    if (newStatus === "CANCELLED") {
        showCancelModal.value = true;
        return;
    }
    sendUpdate(newStatus);
};

const confirmCancel = () => {
    if (!cancelReason.value.trim()) return;
    sendUpdate("CANCELLED", cancelReason.value);
};

const sendUpdate = (status, reason = null) => {
    processing.value = true;
    router.patch(
        `/quan-tri/don-hang/${localOrder.value.id}/status`,
        { status, cancel_reason: reason },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCancelModal.value = false;
                cancelReason.value = "";
            },
            onFinish: () => (processing.value = false),
        }
    );
};

const formatMoney = (value) => Number(value).toLocaleString("vi-VN") + "đ";
const formatDate = (value) => new Date(value).toLocaleString("vi-VN");
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 max-w-3xl">
            <button
                class="inline-flex items-center gap-1 font-sans text-body-medium text-on-surface-variant hover:text-primary"
                @click="router.visit('/quan-tri/don-hang')">
                <span class="material-symbols-outlined text-lg">arrow_back</span>Quay lại
            </button>

            <div class="flex items-start justify-between">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Đơn hàng #{{ localOrder.id }}</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">{{ formatDate(localOrder.created_at)
                    }}</p>
                </div>
                <div class="flex gap-2">
                    <button v-for="action in getNextActions(localOrder)" :key="action.value" :disabled="processing"
                        @click="updateStatus(action.value)"
                        class="px-5 py-2.5 rounded-full font-sans text-label-large transition-colors" :class="action.value === 'CANCELLED'
                            ? 'bg-error-container text-error hover:bg-error-container/80'
                            : 'bg-primary text-on-primary hover:bg-primary/90'
                            ">
                        {{ action.label }}
                    </button>
                </div>
            </div>

            <div
                class="bg-surface rounded-2xl border border-outline-variant/20 p-6 grid grid-cols-2 gap-4 font-sans text-body-medium shadow-sm">
                <div><span class="text-on-surface-variant">Khách hàng:</span>
                    {{
                        localOrder.user?.name ?? "Khách vãng lai"
                    }}
                </div>
                <div><span class="text-on-surface-variant">Bàn:</span> {{ localOrder.table?.table_name ?? "—" }}</div>
                <div><span class="text-on-surface-variant">Loại đơn:</span>
                    {{ localOrder.order_type === "DINE_IN" ? "Tại chỗ" : "Mang đi" }}</div>
                <div>
                    <span class="text-on-surface-variant">Thanh toán:</span>
                    {{ localOrder.payment?.payment_method ?? "—" }} ({{ localOrder.payment?.payment_status ?? "chưa có"
                    }})
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                            <th class="p-4">Món</th>
                            <th class="p-4">Phân loại</th>
                            <th class="p-4 text-center">SL</th>
                            <th class="p-4 text-right">Đơn giá</th>
                            <th class="p-4">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                        <tr v-for="d in localOrder.details" :key="d.id">
                            <td class="p-4">{{ d.product?.product_name }}</td>
                            <td class="p-4 text-on-surface-variant">{{ d.variant?.size ?? "—" }}</td>
                            <td class="p-4 text-center">{{ d.quantity }}</td>
                            <td class="p-4 text-right font-mono">{{ formatMoney(d.unit_price) }}</td>
                            <td class="p-4 text-on-surface-variant">{{ d.note ?? "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="bg-surface rounded-2xl border border-outline-variant/20 p-6 space-y-1 text-right font-sans text-body-medium shadow-sm">
                <div class="text-on-surface-variant">Tạm tính: {{ formatMoney(localOrder.total_amount) }}</div>
                <div class="text-on-surface-variant">Giảm giá: -{{ formatMoney(localOrder.discount_amount) }}</div>
                <div class="text-headline-small font-bold text-on-surface">Thành tiền: {{
                    formatMoney(localOrder.final_amount) }}</div>
            </div>

            <div v-if="localOrder.cancel_reason"
                class="bg-error-container/20 rounded-2xl p-4 font-sans text-body-medium text-error">
                Lý do hủy: {{ localOrder.cancel_reason }}
            </div>

            <div v-if="showCancelModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                <div class="bg-surface w-full max-w-lg rounded-2xl shadow-xl flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between p-6 border-b border-outline-variant/20">
                        <h3 class="font-serif text-headline-small">Lý do hủy đơn</h3>
                        <button @click="showCancelModal = false"
                            class="p-1 hover:bg-surface-container-high rounded-full">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <textarea v-model="cancelReason" rows="3"
                            class="w-full px-4 py-2 rounded-xl border border-outline-variant bg-surface font-sans text-body-medium"
                            placeholder="Nhập lý do hủy đơn..."></textarea>
                        <div class="flex justify-end gap-3 pt-2 border-t border-outline-variant/20">
                            <button type="button" @click="showCancelModal = false"
                                class="px-5 py-2.5 rounded-full hover:bg-surface-container-high">
                                Đóng
                            </button>
                            <button type="button" :disabled="processing" @click="confirmCancel"
                                class="px-5 py-2.5 bg-error-container text-error rounded-full font-bold hover:bg-error-container/80">
                                Xác nhận hủy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>