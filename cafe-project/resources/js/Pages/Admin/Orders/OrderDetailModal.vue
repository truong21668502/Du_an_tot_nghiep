<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    order: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const cancelReason = ref("");
const showCancelForm = ref(false);
const processing = ref(false);

watch(
    () => props.order?.id,
    () => {
        showCancelForm.value = false;
        cancelReason.value = "";
    },
);

watch(
    () => !!props.order,
    (open) => {
        document.body.style.overflow = open ? "hidden" : "";
    },
);

/* ---------------- LABELS (giữ nguyên như bản cũ) ---------------- */

const statusLabel = {
    PENDING: "Chờ duyệt",
    PROCESSING: "Đang pha chế",
    READY: "Sẵn sàng",
    DELIVERING: "Đang giao",
    COMPLETED: "Hoàn thành",
    CANCELLED: "Đã hủy",
};

const statusClass = {
    PENDING: "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20",
    PROCESSING: "bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20",
    READY: "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20",
    DELIVERING: "bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20",
    COMPLETED: "bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20",
    CANCELLED: "bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20",
};

const statusDotClass = {
    PENDING: "bg-amber-500",
    PROCESSING: "bg-blue-500",
    READY: "bg-emerald-500",
    DELIVERING: "bg-indigo-500",
    COMPLETED: "bg-green-500",
    CANCELLED: "bg-red-500",
};

const baristaStatusLabel = {
    PENDING: "Chờ pha",
    PREPARING: "Đang pha",
    COMPLETED: "Đã xong",
    CANCELLED: "Đã hủy",
};

const baristaStatusClass = {
    PENDING: "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20",
    PREPARING: "bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20",
    COMPLETED: "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20",
    CANCELLED: "bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20",
};

const orderTypeLabel = { DINE_IN: "Tại chỗ", TAKE_AWAY: "Mang đi", DELIVERY: "Giao hàng" };
const orderTypeIcon = { DINE_IN: "table_restaurant", TAKE_AWAY: "shopping_bag", DELIVERY: "delivery_dining" };

/* ---------------- COMPUTED ---------------- */

const isPaid = computed(() => props.order?.payment?.payment_status === "PAID");

const totalItems = computed(() =>
    (props.order?.details ?? []).reduce((t, d) => t + Number(d.quantity ?? 0), 0),
);

const baristaProgress = computed(() => {
    if (!props.order) return { done: 0, total: 0, is_complete: false, label: "0/0" };
    if (props.order.barista_progress) return props.order.barista_progress;

    const active = (props.order.details ?? []).filter((d) => d.barista_status !== "CANCELLED");
    const done = active.filter((d) => d.barista_status === "COMPLETED").length;

    return {
        done,
        total: active.length,
        is_complete: active.length > 0 && done === active.length,
        label: `${done}/${active.length}`,
    };
});

const progressPercentage = computed(() => {
    const p = baristaProgress.value;
    return p.total ? Math.round((p.done / p.total) * 100) : 0;
});

/* ---------------- ACTIONS ---------------- */

const getNextActions = (order) => {
    if (!order) return [];

    if (order.status === "PENDING") {
        return [
            { label: "Xác nhận đơn", value: "PROCESSING", icon: "check_circle" },
            { label: "Hủy đơn", value: "CANCELLED", icon: "cancel" },
        ];
    }

    if (order.status === "PROCESSING") {
        return [
            { label: "Sẵn sàng", value: "READY", icon: "task_alt" },
            { label: "Hủy đơn", value: "CANCELLED", icon: "cancel" },
        ];
    }

    if (order.status === "READY") {
        const canComplete = order.barista_progress?.is_complete ?? true;

        if (order.order_type === "DELIVERY") {
            return [
                { label: "Bắt đầu giao", value: "DELIVERING", icon: "delivery_dining", disabled: !canComplete },
                { label: "Hủy đơn", value: "CANCELLED", icon: "cancel" },
            ];
        }

        return [{ label: "Hoàn thành", value: "COMPLETED", icon: "check_circle", disabled: !canComplete }];
    }

    if (order.status === "DELIVERING") {
        const canComplete = order.barista_progress?.is_complete ?? true;
        return [{ label: "Hoàn thành", value: "COMPLETED", icon: "check_circle", disabled: !canComplete }];
    }

    return [];
};

const updateStatus = (value) => {
    if (value === "CANCELLED") {
        showCancelForm.value = true;
        return;
    }
    sendUpdate(value);
};

const confirmCancel = () => {
    if (!cancelReason.value.trim()) return;
    sendUpdate("CANCELLED", cancelReason.value);
};

const sendUpdate = (status, reason = null) => {
    if (!props.order) return;

    processing.value = true;

    router.patch(
        `/quan-tri/don-hang/${props.order.id}/status`,
        { status, cancel_reason: reason },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showCancelForm.value = false;
                cancelReason.value = "";
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

/* ---------------- HELPERS ---------------- */

const formatMoney = (v) => Number(v ?? 0).toLocaleString("vi-VN") + "đ";

const formatDate = (v) => {
    if (!v) return "—";
    return new Date(v).toLocaleString("vi-VN", {
        day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit",
    });
};

const getBaristaIcon = (status) => ({
    PENDING: "schedule", PREPARING: "coffee", COMPLETED: "check_circle", CANCELLED: "cancel",
}[status] ?? "help");

const close = () => emit("close");
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="order"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-2 backdrop-blur-sm sm:p-6"
                @click.self="close">
                <Transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-3 opacity-0 scale-[0.98]"
                    enter-to-class="translate-y-0 opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100"
                    leave-to-class="translate-y-3 opacity-0 scale-[0.98]">
                    <div v-if="order"
                        class="flex h-full max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                        <!-- HEADER -->
                        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <span class="material-symbols-outlined">receipt_long</span>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-base font-black text-slate-900">Đơn hàng #{{ order.id }}</h2>

                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                            :class="statusClass[order.status] ?? 'bg-slate-100 text-slate-700'">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                :class="statusDotClass[order.status] ?? 'bg-slate-400'"></span>
                                            {{ statusLabel[order.status] ?? order.status }}
                                        </span>
                                    </div>

                                    <p class="text-[11px] text-slate-400">{{ formatDate(order.created_at) }}</p>
                                </div>
                            </div>

                            <button @click="close"
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <!-- BODY (scrollable) -->
                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                            <!-- INFO CARDS -->
                            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="compact-card">
                                    <div class="compact-icon bg-violet-50 text-violet-600">
                                        <span class="material-symbols-outlined">person</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="compact-label">Khách hàng</p>
                                        <p class="compact-value truncate">{{ order.user?.name ?? "Khách vãng lai" }}</p>
                                        <p v-if="order.user?.phone" class="text-[11px] text-slate-400">{{
                                            order.user.phone }}</p>
                                    </div>
                                </div>

                                <div class="compact-card">
                                    <div class="compact-icon bg-blue-50 text-blue-600">
                                        <span class="material-symbols-outlined">table_restaurant</span>
                                    </div>
                                    <div>
                                        <p class="compact-label">Bàn</p>
                                        <p class="compact-value">{{ order.table?.table_name ?? "Không có bàn" }}</p>
                                    </div>
                                </div>

                                <div class="compact-card">
                                    <div class="compact-icon bg-emerald-50 text-emerald-600">
                                        <span class="material-symbols-outlined">
                                            {{ orderTypeIcon[order.order_type] ?? "receipt_long" }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="compact-label">Loại đơn</p>
                                        <p class="compact-value">{{ orderTypeLabel[order.order_type] ?? order.order_type
                                            ?? "—" }}</p>
                                    </div>
                                </div>

                                <div class="compact-card">
                                    <div class="compact-icon"
                                        :class="isPaid ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                                        <span class="material-symbols-outlined">payments</span>
                                    </div>
                                    <div>
                                        <p class="compact-label">Thanh toán</p>
                                        <p class="compact-value">{{ order.payment?.payment_method ?? "—" }}</p>
                                        <p class="text-[11px] font-semibold"
                                            :class="isPaid ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ isPaid ? "Đã thanh toán" : "Chưa thanh toán" }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- PROGRESS -->
                            <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">Tiến độ pha chế</h3>
                                        <p class="mt-0.5 text-[11px] text-slate-400">{{ totalItems }} sản phẩm</p>
                                    </div>
                                    <div class="text-lg font-black"
                                        :class="baristaProgress.is_complete ? 'text-emerald-600' : 'text-slate-900'">
                                        {{ baristaProgress.label }}
                                    </div>
                                </div>

                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-primary transition-all"
                                        :style="{ width: `${progressPercentage}%` }"></div>
                                </div>
                            </div>

                            <!-- ITEMS -->
                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="w-full">
                                    <thead class="bg-slate-50">
                                        <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                            <th class="px-4 py-2.5 text-left">Món</th>
                                            <th class="px-3 py-2.5 text-left">Size</th>
                                            <th class="px-3 py-2.5 text-center">SL</th>
                                            <th class="px-3 py-2.5 text-right">Giá</th>
                                            <th class="px-4 py-2.5 text-left">Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="detail in order.details" :key="detail.id" class="hover:bg-slate-50">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div
                                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                                                        <span
                                                            class="material-symbols-outlined text-[17px]">local_cafe</span>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-xs font-bold text-slate-800">
                                                            {{ detail.product?.product_name ?? "Sản phẩm" }}
                                                        </p>
                                                        <p v-if="detail.note"
                                                            class="max-w-[220px] truncate text-[10px] text-amber-600">
                                                            {{ detail.note }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 text-xs text-slate-500">{{ detail.variant?.size ?? "—"
                                                }}</td>
                                            <td class="px-3 py-3 text-center">
                                                <span
                                                    class="inline-flex h-6 min-w-6 items-center justify-center rounded-md bg-slate-100 px-1.5 text-xs font-bold">
                                                    {{ detail.quantity }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-right font-mono text-xs font-bold">{{
                                                formatMoney(detail.unit_price) }}</td>
                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-bold"
                                                    :class="baristaStatusClass[detail.barista_status]">
                                                    <span class="material-symbols-outlined text-[13px]">{{
                                                        getBaristaIcon(detail.barista_status) }}</span>
                                                    {{ baristaStatusLabel[detail.barista_status] ??
                                                    detail.barista_status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- CANCEL FORM -->
                            <div v-if="showCancelForm" class="mt-4 rounded-xl border border-red-100 bg-red-50 p-4">
                                <label class="mb-1.5 block text-xs font-bold text-red-700">Lý do hủy đơn</label>
                                <textarea v-model="cancelReason" rows="2"
                                    class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-red-300"
                                    placeholder="Nhập lý do..."></textarea>
                                <div class="mt-2 flex justify-end gap-2">
                                    <button @click="showCancelForm = false"
                                        class="rounded-lg px-3 py-1.5 text-xs font-bold text-slate-500 hover:bg-white">
                                        Đóng
                                    </button>
                                    <button :disabled="processing || !cancelReason.trim()" @click="confirmCancel"
                                        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700 disabled:opacity-40">
                                        Xác nhận hủy
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER: TOTAL + ACTIONS -->
                        <div
                            class="flex shrink-0 flex-col gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-[11px] text-slate-400">Tổng thanh toán</p>
                                <p class="font-mono text-xl font-black text-slate-900">{{
                                    formatMoney(order.final_amount) }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button v-for="action in getNextActions(order)" :key="action.value"
                                    :disabled="processing || action.disabled" @click="updateStatus(action.value)"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold transition disabled:cursor-not-allowed disabled:opacity-40"
                                    :class="action.value === 'CANCELLED' ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-primary text-white hover:opacity-90'">
                                    <span class="material-symbols-outlined text-[16px]">{{ action.icon }}</span>
                                    {{ action.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<!-- <style scoped>
.compact-card {
    @apply flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3;
}
.compact-icon {
    @apply flex h-9 w-9 shrink-0 items-center justify-center rounded-lg;
}
.compact-label {
    @apply text-[10px] font-semibold uppercase tracking-wide text-slate-400;
}
.compact-value {
    @apply mt-0.5 text-xs font-bold text-slate-800;
}
</style> -->