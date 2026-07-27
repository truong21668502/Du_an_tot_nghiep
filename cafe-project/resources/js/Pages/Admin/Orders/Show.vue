```vue
<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    order: Object,
});

/*
|--------------------------------------------------------------------------
| LOCAL ORDER
|--------------------------------------------------------------------------
*/

const localOrder = ref(props.order);

watch(
    () => props.order,
    (newOrder) => {
        localOrder.value = newOrder;
    },
);

/*
|--------------------------------------------------------------------------
| STATE
|--------------------------------------------------------------------------
*/

const cancelReason = ref("");
const showCancelModal = ref(false);
const processing = ref(false);
const realtimeConnected = ref(false);

/*
|--------------------------------------------------------------------------
| LABELS
|--------------------------------------------------------------------------
*/

const statusLabel = {
    PENDING: "Chờ duyệt",
    PROCESSING: "Đang pha chế",
    READY: "Sẵn sàng",
    DELIVERING: "Đang giao",
    COMPLETED: "Hoàn thành",
    CANCELLED: "Đã hủy",
};

const statusClass = {
    PENDING: "bg-amber-50 text-amber-700 ring-amber-600/20",
    PROCESSING: "bg-blue-50 text-blue-700 ring-blue-600/20",
    READY: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
    DELIVERING: "bg-indigo-50 text-indigo-700 ring-indigo-600/20",
    COMPLETED: "bg-green-50 text-green-700 ring-green-600/20",
    CANCELLED: "bg-red-50 text-red-700 ring-red-600/20",
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
    PENDING: "bg-amber-50 text-amber-700 ring-amber-600/20",
    PREPARING: "bg-blue-50 text-blue-700 ring-blue-600/20",
    COMPLETED: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
    CANCELLED: "bg-red-50 text-red-700 ring-red-600/20",
};

/*
|--------------------------------------------------------------------------
| REALTIME
|--------------------------------------------------------------------------
*/

onMounted(() => {
    if (!window.Echo) {
        console.warn("Laravel Echo chưa được khởi tạo");
        return;
    }

    realtimeConnected.value = true;

    window.Echo.channel("staff-orders")

        .listen(".order.status-updated", (e) => {
            if (e.order.id !== localOrder.value.id) return;

            localOrder.value = {
                ...localOrder.value,
                status: e.order.status,
                table_id: e.order.table_id,
                table: e.order.table,
            };
        })

        .listen(".barista.detail.updated", (e) => {
            if (e.order_id !== localOrder.value.id) return;

            const detail = localOrder.value.details?.find((d) => d.id === e.id);

            if (detail) {
                detail.barista_status = e.barista_status;

                localOrder.value.barista_progress = computeBaristaProgress(
                    localOrder.value.details,
                );
            }
        })

        .listen(".order.payment-confirmed", (e) => {
            if (e.id !== localOrder.value.id) return;

            if (localOrder.value.payment) {
                localOrder.value.payment.payment_status = e.payment_status;
            }

            localOrder.value.status = e.status;
        });
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel("staff-orders");
    }
});

/*
|--------------------------------------------------------------------------
| COMPUTED
|--------------------------------------------------------------------------
*/

const currentStatus = computed(() => {
    return localOrder.value.status;
});

const currentStatusLabel = computed(() => {
    return statusLabel[localOrder.value.status] ?? localOrder.value.status;
});

const currentStatusClass = computed(() => {
    return (
        statusClass[localOrder.value.status] ?? "bg-slate-100 text-slate-700"
    );
});

const currentStatusDot = computed(() => {
    return statusDotClass[localOrder.value.status] ?? "bg-slate-400";
});

const orderTypeLabel = computed(() => {
    const types = {
        DINE_IN: "Tại chỗ",
        TAKE_AWAY: "Mang đi",
        DELIVERY: "Giao hàng",
    };

    return (
        types[localOrder.value.order_type] ?? localOrder.value.order_type ?? "—"
    );
});

const orderTypeIcon = computed(() => {
    const icons = {
        DINE_IN: "table_restaurant",
        TAKE_AWAY: "shopping_bag",
        DELIVERY: "delivery_dining",
    };

    return icons[localOrder.value.order_type] ?? "receipt_long";
});

const isPaid = computed(() => {
    return localOrder.value.payment?.payment_status === "PAID";
});

const totalItems = computed(() => {
    return (localOrder.value.details ?? []).reduce(
        (total, detail) => total + Number(detail.quantity ?? 0),
        0,
    );
});

const baristaProgress = computed(() => {
    if (localOrder.value.barista_progress) {
        return localOrder.value.barista_progress;
    }

    return computeBaristaProgress(localOrder.value.details ?? []);
});

const progressPercentage = computed(() => {
    const progress = baristaProgress.value;

    if (!progress?.total) return 0;

    return Math.round((progress.done / progress.total) * 100);
});

/*
|--------------------------------------------------------------------------
| ACTIONS
|--------------------------------------------------------------------------
*/

const getNextActions = (order) => {
    if (order.status === "PENDING") {
        return [
            {
                label: "Xác nhận đơn",
                value: "PROCESSING",
                icon: "check_circle",
            },
            {
                label: "Hủy đơn",
                value: "CANCELLED",
                icon: "cancel",
            },
        ];
    }

    if (order.status === "PROCESSING") {
        return [
            {
                label: "Sẵn sàng",
                value: "READY",
                icon: "task_alt",
            },
            {
                label: "Hủy đơn",
                value: "CANCELLED",
                icon: "cancel",
            },
        ];
    }

    if (order.status === "READY") {
        const canComplete = order.barista_progress?.is_complete ?? true;

        if (order.order_type === "DELIVERY") {
            return [
                {
                    label: "Bắt đầu giao",
                    value: "DELIVERING",
                    icon: "delivery_dining",
                    disabled: !canComplete,
                },
                {
                    label: "Hủy đơn",
                    value: "CANCELLED",
                    icon: "cancel",
                },
            ];
        }

        return [
            {
                label: "Hoàn thành",
                value: "COMPLETED",
                icon: "check_circle",
                disabled: !canComplete,
            },
        ];
    }

    if (order.status === "DELIVERING") {
        const canComplete = order.barista_progress?.is_complete ?? true;

        return [
            {
                label: "Hoàn thành",
                value: "COMPLETED",
                icon: "check_circle",
                disabled: !canComplete,
            },
        ];
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
        {
            status,
            cancel_reason: reason,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showCancelModal.value = false;
                cancelReason.value = "";
            },

            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const goBack = () => {
    router.visit("/quan-tri/don-hang");
};

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

const formatMoney = (value) => {
    return Number(value ?? 0).toLocaleString("vi-VN") + "đ";
};

const formatDate = (value) => {
    if (!value) return "—";

    return new Date(value).toLocaleString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const computeBaristaProgress = (details = []) => {
    const active = details.filter((d) => d.barista_status !== "CANCELLED");

    const total = active.length;

    const done = active.filter((d) => d.barista_status === "COMPLETED").length;

    return {
        done,
        total,
        is_complete: total > 0 && done === total,
        label: `${done}/${total}`,
    };
};

const getBaristaIcon = (status) => {
    const icons = {
        PENDING: "schedule",
        PREPARING: "coffee",
        COMPLETED: "check_circle",
        CANCELLED: "cancel",
    };

    return icons[status] ?? "help";
};
</script>

<template>
    <AdminLayout>
        <div class="h-[calc(100vh-64px)] overflow-hidden bg-slate-50 p-3">

            <!-- HEADER -->
            <div class="mb-3 flex h-12 items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="goBack" class="flex h-9 w-9 items-center justify-center rounded-lg
                               bg-white text-slate-500 shadow-sm
                               hover:bg-slate-100">
                        <span class="material-symbols-outlined">
                            arrow_back
                        </span>
                    </button>

                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-bold text-slate-900">
                                Đơn hàng #{{ localOrder.id }}
                            </h1>

                            <span class="inline-flex items-center gap-1.5 rounded-full
                                       px-2.5 py-1 text-[11px] font-bold" :class="currentStatusClass">
                                <span class="h-1.5 w-1.5 rounded-full" :class="currentStatusDot"></span>

                                {{ currentStatusLabel }}
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-400">
                            {{ formatDate(localOrder.created_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="hidden items-center gap-1.5 rounded-full
                               bg-emerald-50 px-2.5 py-1.5 text-[11px]
                               font-semibold text-emerald-600 sm:flex">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        Realtime
                    </span>

                    <button v-for="action in getNextActions(localOrder)" :key="action.value"
                        :disabled="processing || action.disabled" @click="updateStatus(action.value)" class="inline-flex items-center gap-1.5 rounded-lg
                               px-3 py-2 text-xs font-bold transition
                               disabled:cursor-not-allowed disabled:opacity-40" :class="action.value === 'CANCELLED'
                                ? 'bg-red-50 text-red-600 hover:bg-red-100'
                                : 'bg-primary text-white hover:opacity-90'
                                ">
                        <span class="material-symbols-outlined text-[16px]">
                            {{ action.icon }}
                        </span>

                        {{ action.label }}
                    </button>
                </div>
            </div>

            <!-- ORDER INFO -->
            <div class="mb-3 grid grid-cols-4 gap-3">

                <!-- CUSTOMER -->
                <div class="compact-card">
                    <div class="compact-icon bg-violet-50 text-violet-600">
                        <span class="material-symbols-outlined">
                            person
                        </span>
                    </div>

                    <div class="min-w-0">
                        <p class="compact-label">
                            Khách hàng
                        </p>

                        <p class="compact-value truncate">
                            {{ localOrder.user?.name ?? "Khách vãng lai" }}
                        </p>

                        <p v-if="localOrder.user?.phone" class="text-[11px] text-slate-400">
                            {{ localOrder.user.phone }}
                        </p>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="compact-card">
                    <div class="compact-icon bg-blue-50 text-blue-600">
                        <span class="material-symbols-outlined">
                            table_restaurant
                        </span>
                    </div>

                    <div>
                        <p class="compact-label">
                            Bàn
                        </p>

                        <p class="compact-value">
                            {{ localOrder.table?.table_name ?? "Không có bàn" }}
                        </p>
                    </div>
                </div>

                <!-- ORDER TYPE -->
                <div class="compact-card">
                    <div class="compact-icon bg-emerald-50 text-emerald-600">
                        <span class="material-symbols-outlined">
                            {{ orderTypeIcon }}
                        </span>
                    </div>

                    <div>
                        <p class="compact-label">
                            Loại đơn
                        </p>

                        <p class="compact-value">
                            {{ orderTypeLabel }}
                        </p>
                    </div>
                </div>

                <!-- PAYMENT -->
                <div class="compact-card">
                    <div class="compact-icon" :class="isPaid
                        ? 'bg-emerald-50 text-emerald-600'
                        : 'bg-amber-50 text-amber-600'
                        ">
                        <span class="material-symbols-outlined">
                            payments
                        </span>
                    </div>

                    <div>
                        <p class="compact-label">
                            Thanh toán
                        </p>

                        <p class="compact-value">
                            {{ localOrder.payment?.payment_method ?? "—" }}
                        </p>

                        <p class="text-[11px] font-semibold" :class="isPaid
                            ? 'text-emerald-600'
                            : 'text-amber-600'
                            ">
                            {{ isPaid ? "Đã thanh toán" : "Chưa thanh toán" }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="grid h-[calc(100%-132px)] min-h-0 grid-cols-12 gap-3">

                <!-- ORDER ITEMS -->
                <div class="col-span-8 flex min-h-0 flex-col
                           overflow-hidden rounded-xl border
                           border-slate-200 bg-white shadow-sm">
                    <div class="flex h-12 shrink-0 items-center justify-between
                               border-b border-slate-100 px-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Chi tiết món
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                {{ totalItems }} sản phẩm
                            </p>
                        </div>

                        <span class="material-symbols-outlined text-slate-300">
                            restaurant_menu
                        </span>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto">

                        <table class="w-full">
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr class="text-[10px] font-bold uppercase
                                           tracking-wider text-slate-400">
                                    <th class="px-4 py-2.5 text-left">
                                        Món
                                    </th>

                                    <th class="px-3 py-2.5 text-left">
                                        Size
                                    </th>

                                    <th class="px-3 py-2.5 text-center">
                                        SL
                                    </th>

                                    <th class="px-3 py-2.5 text-right">
                                        Giá
                                    </th>

                                    <th class="px-4 py-2.5 text-left">
                                        Trạng thái
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="detail in localOrder.details" :key="detail.id" class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0
                                                       items-center justify-center
                                                       rounded-lg bg-orange-50
                                                       text-orange-600">
                                                <span class="material-symbols-outlined
                                                           text-[17px]">
                                                    local_cafe
                                                </span>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="truncate text-xs
                                                           font-bold text-slate-800">
                                                    {{
                                                        detail.product?.product_name
                                                        ?? "Sản phẩm"
                                                    }}
                                                </p>

                                                <p v-if="detail.note" class="max-w-[220px]
                                                           truncate text-[10px]
                                                           text-amber-600">
                                                    {{ detail.note }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-3 text-xs
                                               text-slate-500">
                                        {{ detail.variant?.size ?? "—" }}
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-flex h-6 min-w-6
                                                   items-center justify-center
                                                   rounded-md bg-slate-100
                                                   px-1.5 text-xs font-bold">
                                            {{ detail.quantity }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 text-right
                                               font-mono text-xs font-bold">
                                        {{ formatMoney(detail.unit_price) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center
                                                   gap-1 rounded-full px-2 py-1
                                                   text-[10px] font-bold" :class="baristaStatusClass[
                                                    detail.barista_status
                                                ]
                                                    ">
                                            <span class="material-symbols-outlined
                                                       text-[13px]">
                                                {{
                                                    getBaristaIcon(
                                                        detail.barista_status
                                                    )
                                                }}
                                            </span>

                                            {{
                                                baristaStatusLabel[
                                                detail.barista_status
                                                ]
                                                ?? detail.barista_status
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT SIDEBAR -->
                <div class="col-span-4 flex min-h-0 flex-col gap-3">

                    <!-- PROGRESS -->
                    <div class="rounded-xl border border-slate-200
                               bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">
                                    Tiến độ pha chế
                                </h2>

                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    Trạng thái các món
                                </p>
                            </div>

                            <div class="text-lg font-black" :class="baristaProgress.is_complete
                                ? 'text-emerald-600'
                                : 'text-slate-900'
                                ">
                                {{ baristaProgress.label }}
                            </div>
                        </div>

                        <div class="mt-3 h-2 overflow-hidden
                                   rounded-full bg-slate-100">
                            <div class="h-full rounded-full
                                       bg-primary transition-all" :style="{
                                        width: `${progressPercentage}%`,
                                    }"></div>
                        </div>

                        <div class="mt-2 flex justify-between
                                   text-[10px] text-slate-400">
                            <span>
                                {{ progressPercentage }}% hoàn thành
                            </span>

                            <span>
                                {{ baristaProgress.done }}
                                /
                                {{ baristaProgress.total }}
                            </span>
                        </div>
                    </div>

                    <!-- TOTAL -->
                    <div class="flex flex-1 flex-col
                               justify-between rounded-xl
                               bg-slate-900 p-5 text-white">
                        <div>
                            <p class="text-xs text-slate-400">
                                Tổng thanh toán
                            </p>

                            <p class="mt-2 font-mono text-3xl
                                       font-black tracking-tight">
                                {{
                                    formatMoney(
                                        localOrder.final_amount
                                    )
                                }}
                            </p>
                        </div>

                        <div class="border-t border-white/10
                                   pt-4">
                            <div class="flex items-center
                                       justify-between text-xs">
                                <span class="text-slate-400">
                                    Phương thức
                                </span>

                                <span class="font-bold">
                                    {{
                                        localOrder.payment
                                            ?.payment_method ?? "—"
                                    }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center
                                       justify-between text-xs">
                                <span class="text-slate-400">
                                    Trạng thái
                                </span>

                                <span class="font-bold" :class="isPaid
                                    ? 'text-emerald-400'
                                    : 'text-amber-400'
                                    ">
                                    {{
                                        isPaid
                                            ? "Đã thanh toán"
                                            : "Chưa thanh toán"
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- CANCEL MODAL GIỮ NGUYÊN -->

        </div>
    </AdminLayout>
</template>

<!-- <style scoped>
.compact-card {
    @apply flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm;
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
```
