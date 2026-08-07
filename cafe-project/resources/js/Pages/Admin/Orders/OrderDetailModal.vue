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

/* ---------------- LABELS & STYLE MAPS (đồng bộ với Index.vue) ---------------- */

const statusLabel = {
    PENDING: "Chờ duyệt",
    PROCESSING: "Đang pha chế",
    READY: "Sẵn sàng",
    DELIVERING: "Đang giao",
    COMPLETED: "Hoàn thành",
    CANCELLED: "Đã hủy",
};

const statusClasses = {
    PENDING: "bg-tertiary-container text-on-tertiary-container",
    PROCESSING: "bg-secondary-container text-on-secondary-container",
    READY: "bg-primary-container text-on-primary-container",
    DELIVERING: "bg-primary text-on-primary",
    COMPLETED: "bg-surface-container-high text-on-surface-variant",
    CANCELLED: "bg-error-container text-on-error-container",
};

const statusDotClasses = {
    PENDING: "bg-tertiary",
    PROCESSING: "bg-secondary",
    READY: "bg-primary",
    DELIVERING: "bg-on-primary",
    COMPLETED: "bg-outline",
    CANCELLED: "bg-error",
};

const sourceClasses = {
    CUSTOMER: "bg-secondary-container text-on-secondary-container",
    STAFF: "bg-surface-container-high text-on-surface-variant",
};

const paymentClasses = {
    PAID: "bg-primary-container text-on-primary-container",
    PENDING: "bg-tertiary-container text-on-tertiary-container",
    UNPAID: "bg-tertiary-container text-on-tertiary-container",
    REFUNDED: "bg-error-container text-on-error-container",
};

const paymentStatusLabel = {
    PAID: "Đã thanh toán",
    PENDING: "Chưa thanh toán",
    UNPAID: "Chưa thanh toán",
    REFUNDED: "Đã hoàn tiền",
};

const baristaStatusLabel = {
    PENDING: "Chờ pha",
    PREPARING: "Đang pha",
    COMPLETED: "Đã xong",
    CANCELLED: "Đã hủy",
};

const baristaStatusClasses = {
    PENDING: "bg-tertiary-container text-on-tertiary-container",
    PREPARING: "bg-secondary-container text-on-secondary-container",
    COMPLETED: "bg-primary-container text-on-primary-container",
    CANCELLED: "bg-error-container text-on-error-container",
};

const orderTypeLabel = { DINE_IN: "Tại chỗ", TAKE_AWAY: "Mang đi", DELIVERY: "Giao hàng" };
const orderTypeIcon = { DINE_IN: "table_restaurant", TAKE_AWAY: "shopping_bag", DELIVERY: "delivery_dining" };
const sourceLabel = { CUSTOMER: "Khách tự đặt", STAFF: "Nhân viên tạo" };

const getStatusClasses = (status) => statusClasses[status] ?? "bg-surface-container text-on-surface-variant";
const getStatusDotClasses = (status) => statusDotClasses[status] ?? "bg-outline";
const getSourceClasses = (source) => sourceClasses[source] ?? "bg-surface-container text-on-surface-variant";
const getPaymentClasses = (status) => paymentClasses[status] ?? "bg-tertiary-container text-on-tertiary-container";
const getStatusLabel = (status) => statusLabel[status] ?? status ?? "Không xác định";

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

/* ---------------- VOUCHER ---------------- */

const coupon = computed(() => props.order?.coupon ?? null);

const discountAmount = computed(() => Number(props.order?.discount_amount ?? 0));

const couponDiscountText = computed(() => {
    const c = coupon.value;
    if (!c) return "";
    return c.discount_type === "PERCENTAGE"
        ? `Giảm ${Number(c.discount_value)}%`
        : `Giảm ${formatMoney(c.discount_value)}`;
});

/* ---------------- SHIPPING ---------------- */

const isDelivery = computed(() => props.order?.order_type === "DELIVERY");

const shippingFee = computed(() => Number(props.order?.shipping_fee ?? 0));

const hasBreakdown = computed(() =>
    Boolean(coupon.value) || (isDelivery.value && shippingFee.value > 0),
);

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
        const canComplete = order.barista_progress?.is_complete ?? true;
        return [
            { label: "Sẵn sàng", value: "READY", icon: "task_alt", disabled: !canComplete },
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

const getItemCount = () => totalItems.value;

const close = () => emit("close");
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="order"
                class="fixed inset-0 z-50 flex items-center justify-center bg-scrim/50 p-2 backdrop-blur-sm sm:p-6"
                @click.self="close">
                <Transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-3 opacity-0 scale-[0.98]"
                    enter-to-class="translate-y-0 opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100"
                    leave-to-class="translate-y-3 opacity-0 scale-[0.98]">
                    <div v-if="order"
                        class="flex h-full max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-outline-variant/20 bg-surface shadow-2xl">
                        <!-- HEADER -->
                        <div
                            class="flex shrink-0 items-center justify-between border-b border-outline-variant/20 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-container text-on-primary-container">
                                    <span class="material-symbols-outlined">receipt_long</span>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-sans text-body-medium font-black text-on-surface">Đơn hàng #{{
                                            order.order_code ?? order.id }}</h2>

                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-sans text-label-medium font-bold"
                                            :class="getStatusClasses(order.status)">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                :class="getStatusDotClasses(order.status)"></span>
                                            {{ getStatusLabel(order.status) }}
                                        </span>

                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 font-sans text-label-medium font-bold"
                                            :class="getSourceClasses(order.source)">
                                            {{ sourceLabel[order.source] ?? order.source }}
                                        </span>
                                    </div>

                                    <p class="font-sans text-body-small text-on-surface-variant">{{
                                        formatDate(order.created_at) }}</p>
                                </div>
                            </div>

                            <button @click="close"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-high cursor-pointer">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <!-- BODY (scrollable) -->
                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                            <!-- INFO CARDS -->
                            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div
                                    class="flex min-w-0 items-center gap-3 rounded-2xl border border-outline-variant/20 bg-surface px-4 py-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                                        <span class="material-symbols-outlined">person</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="font-sans text-label-medium font-semibold uppercase tracking-wide text-on-surface-variant">
                                            Khách hàng</p>
                                        <p class="mt-0.5 truncate font-sans text-body-small font-bold text-on-surface">
                                            {{ order.user?.full_name ?? "Khách vãng lai" }}</p>
                                        <p v-if="order.user?.phone_number"
                                            class="font-sans text-body-small text-on-surface-variant">
                                            {{ order.user.phone_number }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Bàn (chỉ hiển thị ý nghĩa khi không phải DELIVERY) -->
                                <div v-if="!isDelivery"
                                    class="flex min-w-0 items-center gap-3 rounded-2xl border border-outline-variant/20 bg-surface px-4 py-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-container text-on-primary-container">
                                        <span class="material-symbols-outlined">table_restaurant</span>
                                    </div>
                                    <div>
                                        <p
                                            class="font-sans text-label-medium font-semibold uppercase tracking-wide text-on-surface-variant">
                                            Bàn</p>
                                        <p class="mt-0.5 font-sans text-body-small font-bold text-on-surface">{{
                                            order.table?.table_name ?? "Không có bàn" }}</p>
                                    </div>
                                </div>

                                <!-- Phí giao hàng: thay thế vị trí "Bàn" khi order_type là DELIVERY -->
                                <div v-else
                                    class="flex min-w-0 items-center gap-3 rounded-2xl border border-outline-variant/20 bg-surface px-4 py-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                                        <span class="material-symbols-outlined">local_shipping</span>
                                    </div>
                                    <div>
                                        <p
                                            class="font-sans text-label-medium font-semibold uppercase tracking-wide text-on-surface-variant">
                                            Phí giao hàng</p>
                                        <p class="mt-0.5 font-sans text-body-small font-bold text-on-surface">{{
                                            formatMoney(shippingFee) }}</p>
                                    </div>
                                </div>

                                <div
                                    class="flex min-w-0 items-center gap-3 rounded-2xl border border-outline-variant/20 bg-surface px-4 py-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-tertiary-container text-on-tertiary-container">
                                        <span class="material-symbols-outlined">
                                            {{ orderTypeIcon[order.order_type] ?? "receipt_long" }}
                                        </span>
                                    </div>
                                    <div>
                                        <p
                                            class="font-sans text-label-medium font-semibold uppercase tracking-wide text-on-surface-variant">
                                            Loại đơn</p>
                                        <p class="mt-0.5 font-sans text-body-small font-bold text-on-surface">{{
                                            orderTypeLabel[order.order_type] ?? order.order_type ?? "—" }}</p>
                                    </div>
                                </div>

                                <div
                                    class="flex min-w-0 items-center gap-3 rounded-2xl border border-outline-variant/20 bg-surface px-4 py-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                        :class="getPaymentClasses(order.payment?.payment_status)">
                                        <span class="material-symbols-outlined">payments</span>
                                    </div>
                                    <div>
                                        <p
                                            class="font-sans text-label-medium font-semibold uppercase tracking-wide text-on-surface-variant">
                                            Thanh toán</p>
                                        <p class="mt-0.5 font-sans text-body-small font-bold text-on-surface">{{
                                            order.payment?.payment_method ?? "—" }}</p>
                                        <p class="font-sans text-body-small font-semibold"
                                            :class="isPaid ? 'text-primary' : 'text-tertiary'">
                                            {{ paymentStatusLabel[order.payment?.payment_status] ?? "Chưa thanh toán" }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Địa chỉ giao hàng (chỉ khi DELIVERY và có dữ liệu) -->
                            <div v-if="isDelivery && (order.address_detail || order.receiver_name)"
                                class="mb-4 flex items-start gap-3 rounded-2xl border border-outline-variant/20 bg-surface px-4 py-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-tertiary-container text-on-tertiary-container">
                                    <span class="material-symbols-outlined">location_on</span>
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="font-sans text-label-medium font-semibold uppercase tracking-wide text-on-surface-variant">
                                        Giao đến</p>
                                    <p class="mt-0.5 font-sans text-body-small font-bold text-on-surface">
                                        {{ order.receiver_name ?? order.user?.full_name ?? "—" }}
                                        <span v-if="order.receiver_phone" class="font-normal text-on-surface-variant">
                                            · {{ order.receiver_phone }}</span>
                                    </p>
                                    <p class="mt-0.5 font-sans text-body-small text-on-surface-variant">
                                        {{ [order.address_detail, order.ward, order.city].filter(Boolean).join(", ")
                                            || "—" }}
                                    </p>
                                </div>
                            </div>

                            <!-- VOUCHER -->
                            <div v-if="coupon"
                                class="mb-4 flex items-center justify-between rounded-2xl border border-primary/20 bg-primary-container/40 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary">
                                        <span class="material-symbols-outlined text-[18px]">local_offer</span>
                                    </div>
                                    <div>
                                        <p class="font-sans text-body-small font-black text-on-primary-container">
                                            {{ coupon.code }}
                                        </p>
                                        <p class="font-sans text-body-small text-on-surface-variant">
                                            {{ couponDiscountText }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="font-sans text-label-medium font-semibold uppercase tracking-wide text-on-surface-variant">
                                        Đã giảm
                                    </p>
                                    <p class="font-mono text-body-medium font-black text-primary">
                                        -{{ formatMoney(discountAmount) }}
                                    </p>
                                </div>
                            </div>

                            <!-- PROGRESS -->
                            <div class="mb-4 rounded-2xl border border-outline-variant/20 bg-surface p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-sans text-body-medium font-bold text-on-surface">Tiến độ pha chế
                                        </h3>
                                        <p class="mt-0.5 font-sans text-body-small text-on-surface-variant">{{
                                            totalItems }} sản phẩm</p>
                                    </div>
                                    <div class="font-sans text-headline-md font-black"
                                        :class="baristaProgress.is_complete ? 'text-primary' : 'text-on-surface'">
                                        {{ baristaProgress.label }}
                                    </div>
                                </div>

                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-container-high">
                                    <div class="h-full rounded-full bg-primary transition-all duration-500"
                                        :style="{ width: `${progressPercentage}%` }"></div>
                                </div>
                            </div>

                            <!-- ITEMS -->
                            <div class="overflow-hidden rounded-2xl border border-outline-variant/20">
                                <table class="w-full">
                                    <thead class="bg-surface-container">
                                        <tr
                                            class="font-sans text-label-medium font-bold uppercase tracking-wider text-on-surface-variant">
                                            <th class="px-4 py-2.5 text-left">Món</th>
                                            <th class="px-3 py-2.5 text-left">Size</th>
                                            <th class="px-3 py-2.5 text-center">SL</th>
                                            <th class="px-3 py-2.5 text-right">Giá</th>
                                            <th class="px-4 py-2.5 text-left">Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/10">
                                        <tr v-for="detail in order.details" :key="detail.id"
                                            class="hover:bg-surface-container-low/50">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div
                                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-tertiary-container text-on-tertiary-container">
                                                        <span
                                                            class="material-symbols-outlined text-[17px]">local_cafe</span>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate font-sans text-body-small font-bold text-on-surface">
                                                            {{ detail.product?.product_name ?? "Sản phẩm" }}
                                                        </p>
                                                        <p v-if="detail.note"
                                                            class="max-w-[220px] truncate font-sans text-body-small text-tertiary">
                                                            {{ detail.note }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 font-sans text-body-small text-on-surface-variant">{{
                                                detail.variant?.size ?? "—" }}</td>
                                            <td class="px-3 py-3 text-center">
                                                <span
                                                    class="inline-flex h-6 min-w-6 items-center justify-center rounded-md bg-surface-container-high font-sans text-body-small font-bold text-on-surface">
                                                    {{ detail.quantity }}
                                                </span>
                                            </td>
                                            <td
                                                class="px-3 py-3 text-right font-mono text-body-small font-bold text-on-surface">
                                                {{ formatMoney(detail.unit_price) }}</td>
                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 font-sans text-label-medium font-bold"
                                                    :class="baristaStatusClasses[detail.barista_status]">
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
                            <div v-if="showCancelForm"
                                class="mt-4 rounded-2xl border border-error/20 bg-error-container p-4">
                                <label
                                    class="mb-1.5 block font-sans text-label-medium font-bold text-on-error-container">Lý
                                    do hủy đơn</label>
                                <textarea v-model="cancelReason" rows="2"
                                    class="w-full rounded-xl border border-outline-variant bg-surface px-3 py-2 font-sans text-body-medium text-on-surface outline-none focus:ring-2 focus:ring-error"
                                    placeholder="Nhập lý do..."></textarea>
                                <div class="mt-2 flex justify-end gap-2">
                                    <button @click="showCancelForm = false"
                                        class="rounded-full px-3 py-1.5 font-sans text-label-medium font-bold text-on-surface-variant hover:bg-surface cursor-pointer">
                                        Đóng
                                    </button>
                                    <button :disabled="processing || !cancelReason.trim()" @click="confirmCancel"
                                        class="rounded-full bg-error px-3 py-1.5 font-sans text-label-medium font-bold text-on-error hover:opacity-90 disabled:opacity-40 cursor-pointer">
                                        Xác nhận hủy
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER: TOTAL + ACTIONS -->
                        <div
                            class="flex shrink-0 flex-col gap-3 border-t border-outline-variant/20 bg-surface-container-low px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-sans text-body-small text-on-surface-variant">Tổng thanh toán</p>

                                <div v-if="hasBreakdown" class="mb-1 space-y-0.5">
                                    <div
                                        class="flex items-center gap-2 font-mono text-body-small text-on-surface-variant">
                                        <span>Tạm tính:</span>
                                        <span>{{ formatMoney(order.total_amount) }}</span>
                                    </div>
                                    <div v-if="coupon"
                                        class="flex items-center gap-2 font-mono text-body-small font-bold text-primary">
                                        <span class="font-normal text-on-surface-variant">Giảm giá ({{ coupon.code
                                            }}):</span>
                                        <span>-{{ formatMoney(discountAmount) }}</span>
                                    </div>
                                    <div v-if="isDelivery && shippingFee > 0"
                                        class="flex items-center gap-2 font-mono text-body-small text-on-surface-variant">
                                        <span>Phí giao hàng:</span>
                                        <span>+{{ formatMoney(shippingFee) }}</span>
                                    </div>
                                </div>

                                <p class="font-mono text-headline-md font-black text-on-surface">
                                    {{ formatMoney(order.final_amount) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button v-for="action in getNextActions(order)" :key="action.value"
                                    :disabled="processing || action.disabled" @click="updateStatus(action.value)"
                                    class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 font-sans text-label-large font-bold transition disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer"
                                    :class="action.value === 'CANCELLED' ? 'bg-error-container text-on-error-container hover:opacity-90' : 'bg-primary text-on-primary hover:opacity-90'">
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