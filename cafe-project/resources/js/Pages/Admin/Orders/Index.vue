<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import OrderDetailModal from "./OrderDetailModal.vue";

const selectedOrderId = ref(null);

const selectedOrder = computed(() =>
    localOrders.value.find((o) => o.id === selectedOrderId.value) ?? null,
);

const props = defineProps({
    orders: Object,
    tables: Array,
    filters: Object,
});

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

const statusBorderClasses = {
    PENDING: "border-l-tertiary",
    PROCESSING: "border-l-secondary",
    READY: "border-l-primary",
    DELIVERING: "border-l-primary",
    COMPLETED: "border-l-outline-variant",
    CANCELLED: "border-l-error",
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

const getStatusClasses = (status) => statusClasses[status] ?? "bg-surface-container text-on-surface-variant";
const getStatusDotClasses = (status) => statusDotClasses[status] ?? "bg-outline";
const getStatusBorderClasses = (status) => statusBorderClasses[status] ?? "border-l-outline-variant";
const getSourceClasses = (source) => sourceClasses[source] ?? "bg-surface-container text-on-surface-variant";
const getPaymentClasses = (status) => paymentClasses[status] ?? "bg-tertiary-container text-on-tertiary-container";

const localOrders = ref([...props.orders.data]);

watch(
    () => props.orders.data,
    (newData) => {
        localOrders.value = [...newData];
    },
);

const realtimeConnected = ref(false);

const statusLabel = {
    PENDING: "Chờ duyệt",
    PROCESSING: "Đang pha chế",
    READY: "Sẵn sàng",
    DELIVERING: "Đang giao",
    COMPLETED: "Hoàn thành",
    CANCELLED: "Đã hủy",
};

const sourceLabel = {
    CUSTOMER: "Khách tự đặt",
    STAFF: "Nhân viên tạo",
};

const paymentStatusLabel = {
    PAID: "Đã thanh toán",
    PENDING: "Chưa thanh toán",
    UNPAID: "Chưa thanh toán",
    REFUNDED: "Đã hoàn tiền",
};

const orderTypeLabel = {
    DINE_IN: "Tại chỗ",
    TAKE_AWAY: "Mang đi",
    DELIVERY: "Giao hàng",
};

const orderTypeIcon = {
    DINE_IN: "table_restaurant",
    TAKE_AWAY: "shopping_bag",
    DELIVERY: "delivery_dining",
};

onMounted(() => {
    if (!window.Echo) {
        console.warn("Laravel Echo chưa được khởi tạo");
        return;
    }

    realtimeConnected.value = true;

    window.Echo.channel("staff-orders")
        .listen(".order.created", async (e) => {
            const exists = localOrders.value.some((o) => o.id === e.order.id);
            if (exists) return;

            try {
                const { data } = await axios.get(`/quan-tri/don-hang/${e.order.id}`, {
                    headers: { Accept: "application/json" },
                });

                localOrders.value.unshift(data);
                toast.success(`Đơn hàng #${e.order.id} đã được tạo!`, { autoClose: 3000 });
            } catch (error) {
                console.error("Không thể tải chi tiết đơn hàng mới:", error);
            }
        })
        .listen(".order.status-updated", (e) => {
            const index = localOrders.value.findIndex((o) => o.id === e.order.id);

            if (index !== -1) {
                localOrders.value[index] = {
                    ...localOrders.value[index],
                    status: e.order.status,
                    table_id: e.order.table_id,
                    table: e.order.table,
                    shipper: e.order.shipper,
                    delivery_photo: e.order.delivery_photo,
                };

                toast.info(
                    `Đơn #${e.order.id} chuyển sang trạng thái "${statusLabel[e.order.status] ?? e.order.status}"`,
                    { autoClose: 3000 },
                );
            }
        })
        .listen(".barista.detail.updated", (e) => {
            const order = localOrders.value.find((o) => o.id === e.order_id);
            if (!order || !order.details) return;

            const detail = order.details.find((d) => d.id === e.id);

            if (detail) {
                detail.barista_status = e.barista_status;
                order.barista_progress = computeBaristaProgress(order.details);

                if (e.barista_status === "COMPLETED") {
                    toast.success(`Đã pha xong "${e.product_name}" — đơn #${e.order_id}`, { autoClose: 3000 });
                }
            }
        })
        .listen(".order.payment-confirmed", (e) => {
            const order = localOrders.value.find((o) => o.id === e.id);

            if (order) {
                if (order.payment) {
                    order.payment.payment_status = e.payment_status;
                }
                order.status = e.status;

                toast.success(`Đơn ${e.order_code} đã xác nhận thanh toán!`, { autoClose: 3000 });
            }
        });
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel("staff-orders");
    }
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
const orderType = ref(props.filters?.order_type ?? "");
const tableId = ref(props.filters?.table_id ?? "");
const source = ref(props.filters?.source ?? "");

let searchTimeout = null;

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
};

const applyFilters = () => {
    router.get(
        "/quan-tri/don-hang",
        {
            search: search.value || undefined,
            status: status.value || undefined,
            order_type: orderType.value || undefined,
            table_id: tableId.value || undefined,
            source: source.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const setStatusTab = (value) => {
    status.value = value;
    applyFilters();
};

const resetFilters = () => {
    search.value = "";
    status.value = "";
    orderType.value = "";
    tableId.value = "";
    source.value = "";
    applyFilters();
};

const hasActiveFilters = computed(() =>
    Boolean(search.value || status.value || orderType.value || tableId.value || source.value),
);

const summary = computed(() => {
    const data = localOrders.value;

    return {
        total: data.length,
        pending: data.filter((o) => o.status === "PENDING").length,
        processing: data.filter((o) => ["PROCESSING", "READY", "DELIVERING"].includes(o.status)).length,
        completed: data.filter((o) => o.status === "COMPLETED").length,
        cancelled: data.filter((o) => o.status === "CANCELLED").length,
        revenue: data.filter((o) => o.status === "COMPLETED").reduce((sum, o) => sum + Number(o.final_amount ?? 0), 0),
    };
});

const statusTabs = computed(() => [
    { value: "", label: "Tất cả", count: summary.value.total },
    { value: "PENDING", label: "Chờ duyệt", count: summary.value.pending },
    { value: "PROCESSING", label: "Đang xử lý", count: summary.value.processing },
    { value: "COMPLETED", label: "Hoàn thành", count: summary.value.completed },
    { value: "CANCELLED", label: "Đã hủy", count: summary.value.cancelled },
]);

const formatMoney = (value) => Number(value ?? 0).toLocaleString("vi-VN") + "đ";

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
        percentage: total > 0 ? Math.round((done / total) * 100) : 0,
    };
};


const previewPhoto = ref(null); // { url, orderCode, receiverName }

const openPhotoPreview = (order, event) => {
    event.stopPropagation(); // chặn không cho mở OrderDetailModal khi bấm badge
    previewPhoto.value = {
        url: order.delivery_photo,
        orderCode: order.order_code ?? order.id,
        receiverName: order.receiver_name ?? order.user?.full_name ?? "Khách hàng",
    };
};

const closePhotoPreview = () => {
    previewPhoto.value = null;
};

const getItemCount = (order) => (order.details ?? []).reduce((t, d) => t + Number(d.quantity ?? 0), 0);
const getOrderTypeLabel = (type) => orderTypeLabel[type] ?? type ?? "—";
const getOrderTypeIcon = (type) => orderTypeIcon[type] ?? "receipt_long";
const getStatusLabel = (status) => statusLabel[status] ?? status ?? "Không xác định";

const openOrder = (order) => {
    selectedOrderId.value = order.id;
};

const closeOrderModal = () => {
    selectedOrderId.value = null;
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl">
                        <span class="material-symbols-outlined text-primary">receipt_long</span>
                        QUẢN LÝ ĐƠN HÀNG
                    </h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">
                        Theo dõi và xử lý đơn hàng của Nắng Coffee theo thời gian thực.
                    </p>
                </div>

                <div
                    class="inline-flex w-fit items-center gap-2 rounded-full border border-outline-variant/20 bg-surface px-3.5 py-2 font-sans text-label-large text-on-surface-variant self-start sm:self-center">
                    <span class="h-2 w-2 rounded-full"
                        :class="realtimeConnected ? 'bg-primary animate-pulse' : 'bg-outline-variant'"></span>
                    {{ realtimeConnected ? "Đang cập nhật realtime" : "Realtime chưa kết nối" }}
                </div>
            </div>

            <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    class="rounded-2xl border border-outline-variant/20 border-l-4 border-l-tertiary bg-surface p-4 shadow-sm">
                    <p class="font-sans text-label-medium text-on-surface-variant">Chờ duyệt</p>
                    <p class="mt-1 font-sans text-headline-md font-black text-tertiary">{{ summary.pending }}</p>
                </div>
                <div
                    class="rounded-2xl border border-outline-variant/20 border-l-4 border-l-secondary bg-surface p-4 shadow-sm">
                    <p class="font-sans text-label-medium text-on-surface-variant">Đang xử lý</p>
                    <p class="mt-1 font-sans text-headline-md font-black text-secondary">{{ summary.processing }}</p>
                </div>
                <div
                    class="rounded-2xl border border-outline-variant/20 border-l-4 border-l-primary bg-surface p-4 shadow-sm">
                    <p class="font-sans text-label-medium text-on-surface-variant">Hoàn thành</p>
                    <p class="mt-1 font-sans text-headline-md font-black text-primary">{{ summary.completed }}</p>
                </div>
                <div
                    class="rounded-2xl border border-outline-variant/20 border-l-4 border-l-error bg-surface p-4 shadow-sm">
                    <p class="font-sans text-label-medium text-on-surface-variant">Đã hủy</p>
                    <p class="mt-1 font-sans text-headline-md font-black text-error">{{ summary.cancelled }}</p>
                </div>
            </section>

            <section class="flex flex-wrap items-center gap-2">
                <button v-for="tab in statusTabs" :key="tab.value" type="button" @click="setStatusTab(tab.value)"
                    class="rounded-full px-3.5 py-1.5 font-sans text-label-large font-bold transition-colors cursor-pointer"
                    :class="status === tab.value
                        ? 'bg-primary text-on-primary'
                        : 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container'">
                    {{ tab.label }}
                    <span class="ml-1 opacity-70">{{ tab.count }}</span>
                </button>
            </section>

            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm font-sans space-y-3">
                <div
                    class="flex items-center gap-2 text-label-large text-outline font-bold uppercase tracking-wider select-none">
                    <span class="material-symbols-outlined text-lg">filter_list</span>
                    <span>Bộ lọc tìm kiếm</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-body-medium">
                    <div
                        class="sm:col-span-4 flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                        <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                        <input v-model="search" @input="onSearchInput" type="text"
                            placeholder="Mã đơn, tên khách, SĐT..."
                            class="w-full bg-transparent focus:outline-none text-on-surface" />
                    </div>

                    <div class="sm:col-span-2">
                        <select v-model="orderType" @change="applyFilters"
                            class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Tất cả loại đơn --</option>
                            <option value="DINE_IN">Tại chỗ</option>
                            <option value="TAKE_AWAY">Mang đi</option>
                            <option value="DELIVERY">Giao hàng</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <select v-model="tableId" @change="applyFilters"
                            class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Tất cả bàn --</option>
                            <option v-for="table in tables" :key="table.id" :value="table.id">{{ table.table_name }}
                            </option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <select v-model="source" @change="applyFilters"
                            class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Tất cả nguồn --</option>
                            <option value="CUSTOMER">Khách tự đặt</option>
                            <option value="STAFF">Nhân viên tạo</option>
                        </select>
                    </div>

                    <div class="sm:col-span-1 text-right">
                        <button @click="resetFilters" v-if="hasActiveFilters"
                            class="w-full h-full p-2 hover:bg-error-container/20 text-outline hover:text-error rounded-xl transition-colors flex items-center justify-center cursor-pointer"
                            title="Xóa bộ lọc">
                            <span class="material-symbols-outlined">filter_alt_off</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">

                <div v-if="localOrders.length === 0"
                    class="flex flex-col items-center justify-center px-6 py-20 text-center">
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-3xl bg-surface-container-high text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl">receipt_long</span>
                    </div>
                    <h3 class="mt-5 font-sans text-body-medium font-bold text-on-surface">Không có đơn hàng</h3>
                    <p class="mt-1 max-w-sm font-sans text-body-small text-on-surface-variant">
                        {{
                            hasActiveFilters
                                ? "Thử thay đổi bộ lọc để xem kết quả khác."
                                : "Đơn hàng mới sẽ xuất hiện tại đây."
                        }}
                    </p>
                    <button v-if="hasActiveFilters" type="button" @click="resetFilters"
                        class="mt-5 rounded-full bg-primary px-5 py-2.5 font-sans text-label-large font-bold text-on-primary cursor-pointer">
                        Xóa bộ lọc
                    </button>
                </div>

                <div v-else class="hidden overflow-x-auto lg:block">
                    <table class="w-full border-collapse text-center">
                        <thead>
                            <tr
                                class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 text-left">Đơn hàng</th>
                                <th class="p-4 text-left">Khách hàng</th>
                                <th class="p-4 hidden lg:table-cell">Loại đơn / Bàn</th>
                                <th class="p-4 hidden md:table-cell">Pha chế</th>
                                <th class="p-4 hidden md:table-cell">Thanh toán</th>
                                <th class="p-4 text-right">Tổng tiền</th>
                                <th class="p-4 text-center">Trạng thái</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="order in localOrders" :key="order.id" @click="openOrder(order)"
                                class="hover:bg-surface-container-low/50 transition-colors cursor-pointer border-l-4"
                                :class="getStatusBorderClasses(order.status)">

                                <td class="p-4 text-left">
                                    <p class="font-mono font-black text-primary">#{{ order.order_code ?? order.id }}</p>
                                    <p class="mt-0.5 font-sans text-body-small text-on-surface-variant">
                                        {{ formatDate(order.created_at) }} · {{ getItemCount(order) }} món
                                    </p>
                                    <span
                                        class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 font-sans text-body-small font-bold"
                                        :class="getSourceClasses(order.source)">
                                        {{ sourceLabel[order.source] ?? order.source }}
                                    </span>
                                </td>

                                <td class="p-4 text-left">
                                    <p class="font-sans text-body-medium font-bold text-on-surface">{{
                                        order.user?.full_name
                                        ?? "Khách vãng lai" }}</p>
                                    <p v-if="order.user?.phone"
                                        class="mt-0.5 font-sans text-body-small text-on-surface-variant">{{
                                            order.user.phone_number }}</p>
                                </td>

                                <td class="p-4 hidden lg:table-cell">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">{{
                                            getOrderTypeIcon(order.order_type) }}</span>
                                        <span class="font-sans text-body-medium text-on-surface">{{
                                            getOrderTypeLabel(order.order_type) }}</span>
                                    </div>
                                    <p class="mt-0.5 font-sans text-body-small text-on-surface-variant">{{
                                        order.table?.table_name ?? "Không có bàn" }}</p>
                                </td>

                                <td class="p-4 hidden md:table-cell text-center">
                                    <div v-if="order.barista_progress" class="mx-auto w-24">
                                        <div class="mb-1 flex items-center justify-between">
                                            <span
                                                class="font-sans text-body-small font-bold text-on-surface-variant">Tiến
                                                độ</span>
                                            <span class="font-sans text-body-small font-black"
                                                :class="order.barista_progress.is_complete ? 'text-primary' : 'text-on-surface'">
                                                {{ order.barista_progress.label }}
                                            </span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-surface-container-high">
                                            <div class="h-full rounded-full bg-primary transition-all duration-500"
                                                :style="{ width: `${order.barista_progress.percentage ?? 0}%` }"></div>
                                        </div>
                                    </div>
                                    <span v-else class="text-outline-variant">—</span>
                                </td>

                                <td class="p-4 hidden md:table-cell">
                                    <p class="font-sans text-body-small font-semibold text-on-surface">{{
                                        order.payment?.payment_method ?? "—" }}</p>
                                    <span
                                        class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 font-sans text-body-small font-bold"
                                        :class="getPaymentClasses(order.payment?.payment_status)">
                                        {{ paymentStatusLabel[order.payment?.payment_status] ?? "Chưa thanh toán" }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap p-4 text-right">
                                    <span class="font-mono text-body-medium font-black text-on-surface">{{
                                        formatMoney(order.final_amount) }}</span>
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span
                                            class="inline-flex items-center gap-2 justify-center px-3 py-1 rounded-full text-label-medium font-bold"
                                            :class="getStatusClasses(order.status)">
                                            <span class="h-2 w-2 rounded-full"
                                                :class="getStatusDotClasses(order.status)"></span>
                                            {{ getStatusLabel(order.status) }}
                                        </span>

                                        <button v-if="order.order_type === 'DELIVERY' && order.delivery_photo"
                                            @click="openPhotoPreview(order, $event)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container hover:opacity-80 cursor-pointer"
                                            title="Đã có ảnh xác nhận giao hàng — bấm để xem">
                                            <span class="material-symbols-outlined text-[15px]">photo_camera</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="localOrders.length" class="space-y-3 p-4 lg:hidden">
                    <article v-for="order in localOrders" :key="`mobile-${order.id}`" @click="openOrder(order)"
                        class="cursor-pointer overflow-hidden rounded-2xl border border-outline-variant/20 bg-surface shadow-sm active:scale-[0.99]">
                        <div class="flex items-start justify-between gap-3 border-b border-outline-variant/20 p-4"
                            :class="getStatusClasses(order.status)">
                            <div>
                                <p class="font-mono text-body-medium font-black">#{{ order.order_code ?? order.id }}</p>
                                <p class="mt-0.5 font-sans text-body-small opacity-80">{{ formatDate(order.created_at)
                                    }}</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button v-if="order.order_type === 'DELIVERY' && order.delivery_photo"
                                    @click="openPhotoPreview(order, $event)"
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-surface/70 hover:opacity-80 cursor-pointer"
                                    title="Đã có ảnh xác nhận giao hàng">
                                    <span class="material-symbols-outlined text-[15px]">photo_camera</span>
                                </button>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-surface/70 px-2.5 py-1 font-sans text-label-medium font-bold">
                                    {{ getStatusLabel(order.status) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between rounded-xl bg-surface-container p-3">
                                <div>
                                    <p class="font-sans text-body-medium font-bold text-on-surface">{{ order.user?.name
                                        ?? "Khách vãng lai" }}</p>
                                    <p v-if="order.user?.phone"
                                        class="mt-0.5 font-sans text-body-small text-on-surface-variant">{{
                                            order.user.phone }}</p>
                                </div>
                                <span class="rounded-full px-2 py-1 font-sans text-label-medium font-bold"
                                    :class="getSourceClasses(order.source)">
                                    {{ sourceLabel[order.source] ?? order.source }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <p
                                        class="font-sans text-body-small font-bold uppercase tracking-wider text-on-surface-variant">
                                        Loại đơn / Bàn</p>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[17px] text-on-surface-variant">{{
                                            getOrderTypeIcon(order.order_type) }}</span>
                                        <span class="font-sans text-body-medium font-semibold text-on-surface">{{
                                            getOrderTypeLabel(order.order_type) }} · {{ order.table?.table_name ?? "—"
                                            }}</span>
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="font-sans text-body-small font-bold uppercase tracking-wider text-on-surface-variant">
                                        Thanh toán</p>
                                    <p class="mt-1 font-sans text-body-medium font-semibold text-on-surface">{{
                                        order.payment?.payment_method ?? "—" }}</p>
                                    <span
                                        class="mt-1 inline-flex rounded-full px-2 py-1 font-sans text-label-medium font-bold"
                                        :class="getPaymentClasses(order.payment?.payment_status)">
                                        {{ paymentStatusLabel[order.payment?.payment_status] ?? "Chưa thanh toán" }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t border-outline-variant/20 pt-4">
                                <div v-if="order.barista_progress" class="flex items-center gap-2">
                                    <div class="w-16 overflow-hidden rounded-full bg-surface-container-high">
                                        <div class="h-1.5 rounded-full bg-primary"
                                            :style="{ width: `${order.barista_progress.percentage ?? 0}%` }"></div>
                                    </div>
                                    <span class="font-sans text-body-small font-bold text-on-surface-variant">{{
                                        order.barista_progress.label }}</span>
                                </div>
                                <span v-else class="font-sans text-body-small text-on-surface-variant">{{
                                    getItemCount(order) }} món</span>
                                <span class="font-mono text-body-medium font-black text-on-surface">{{
                                    formatMoney(order.final_amount) }}</span>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-if="orders.links?.length > 3" class="flex items-left justify-center gap-1 mt-6 mb-3 font-sans">
                    <Component :is="link.url ? Link : 'span'" v-for="(link, index) in orders.links" :key="index"
                        :href="link.url" v-html="link.label" :preserve-scroll="true"
                        :class="['px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']" />
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="previewPhoto"
                    class="fixed inset-0 z-[60] flex items-center justify-center bg-scrim/60 p-4 backdrop-blur-sm"
                    @click.self="closePhotoPreview">
                    <div class="max-w-lg w-full rounded-2xl bg-surface p-3 shadow-2xl">
                        <div class="flex items-center justify-between px-1 pb-2">
                            <p class="font-sans text-body-small font-bold text-on-surface">
                                Ảnh xác nhận · #{{ previewPhoto.orderCode }} · {{ previewPhoto.receiverName }}
                            </p>
                            <button @click="closePhotoPreview"
                                class="flex h-7 w-7 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-high cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                        <img :src="previewPhoto.url" alt="Ảnh xác nhận giao hàng"
                            class="max-h-[75vh] w-full rounded-xl object-contain" />
                    </div>
                </div>
            </Transition>
        </Teleport>

        <OrderDetailModal :order="selectedOrder" @close="closeOrderModal" />
    </AdminLayout>
</template>