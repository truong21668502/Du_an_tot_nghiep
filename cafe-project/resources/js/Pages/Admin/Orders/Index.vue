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

/*
|--------------------------------------------------------------------------
| THEME TOKENS
|--------------------------------------------------------------------------
*/

const themeVars = {
    "--ink": "#241F1B",
    "--ink-soft": "#6B6259",
    "--paper": "#FBF8F3",
    "--paper-shade": "#EDE4D2",
    "--brass": "#B8863B",
    "--brass-soft": "#F5E9CE",
    "--denim": "#33587A",
    "--denim-soft": "#DCE7EF",
    "--teal": "#2A7D6F",
    "--teal-soft": "#DDEFEC",
    "--forest": "#3C6B4E",
    "--forest-soft": "#E1EEE4",
    "--rust": "#A8452E",
    "--rust-soft": "#F5E0DA",
    "--plum": "#6B4C6B",
    "--plum-soft": "#EAE0EA",
};

const statusTheme = {
    PENDING: { text: "var(--brass)", bg: "var(--brass-soft)" },
    PROCESSING: { text: "var(--denim)", bg: "var(--denim-soft)" },
    READY: { text: "var(--teal)", bg: "var(--teal-soft)" },
    DELIVERING: { text: "var(--plum)", bg: "var(--plum-soft)" },
    COMPLETED: { text: "var(--forest)", bg: "var(--forest-soft)" },
    CANCELLED: { text: "var(--rust)", bg: "var(--rust-soft)" },
};

const sourceTheme = {
    CUSTOMER: { text: "var(--plum)", bg: "var(--plum-soft)" },
    STAFF: { text: "var(--ink-soft)", bg: "var(--paper-shade)" },
};

const paymentTheme = {
    PAID: { text: "var(--forest)", bg: "var(--forest-soft)" },
    PENDING: { text: "var(--brass)", bg: "var(--brass-soft)" },
    UNPAID: { text: "var(--brass)", bg: "var(--brass-soft)" },
    REFUNDED: { text: "var(--rust)", bg: "var(--rust-soft)" },
};

const getStatusStyle = (status) => {
    const t = statusTheme[status] ?? { text: "#6b7280", bg: "#f3f4f6" };
    return { color: t.text, backgroundColor: t.bg };
};

const getSourceStyle = (source) => {
    const t = sourceTheme[source] ?? { text: "#6b7280", bg: "#f3f4f6" };
    return { color: t.text, backgroundColor: t.bg };
};

const getPaymentStyle = (status) => {
    const t = paymentTheme[status] ?? { text: "#6b7280", bg: "#f3f4f6" };
    return { color: t.text, backgroundColor: t.bg };
};

/*
|--------------------------------------------------------------------------
| LOCAL ORDERS
|--------------------------------------------------------------------------
*/

const localOrders = ref([...props.orders.data]);

watch(
    () => props.orders.data,
    (newData) => {
        localOrders.value = [...newData];
    },
);

/*
|--------------------------------------------------------------------------
| REALTIME
|--------------------------------------------------------------------------
*/

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
        /*
        |--------------------------------------------------------------------------
        | ORDER CREATED
        |--------------------------------------------------------------------------
        */
        .listen(".order.created", async (e) => {
            const exists = localOrders.value.some((o) => o.id === e.order.id);

            if (exists) return;

            try {
                const { data } = await axios.get(
                    `/quan-tri/don-hang/${e.order.id}`,
                    {
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                localOrders.value.unshift(data);

                toast.success(`Đơn hàng #${e.order.id} đã được tạo!`, {
                    autoClose: 3000,
                });
            } catch (error) {
                console.error("Không thể tải chi tiết đơn hàng mới:", error);
            }
        })

        /*
        |--------------------------------------------------------------------------
        | ORDER STATUS UPDATED
        |--------------------------------------------------------------------------
        */
        .listen(".order.status-updated", (e) => {
            const index = localOrders.value.findIndex(
                (o) => o.id === e.order.id,
            );

            if (index !== -1) {
                localOrders.value[index] = {
                    ...localOrders.value[index],
                    status: e.order.status,
                    table_id: e.order.table_id,
                    table: e.order.table,
                };

                toast.info(
                    `Đơn #${e.order.id} chuyển sang trạng thái "${statusLabel[e.order.status] ?? e.order.status
                    }"`,
                    {
                        autoClose: 3000,
                    },
                );
            }
        })

        /*
        |--------------------------------------------------------------------------
        | BARISTA DETAIL UPDATED
        |--------------------------------------------------------------------------
        */
        .listen(".barista.detail.updated", (e) => {
            const order = localOrders.value.find((o) => o.id === e.order_id);

            if (!order || !order.details) return;

            const detail = order.details.find((d) => d.id === e.id);

            if (detail) {
                detail.barista_status = e.barista_status;

                order.barista_progress = computeBaristaProgress(order.details);

                if (e.barista_status === "COMPLETED") {
                    toast.success(
                        `Đã pha xong "${e.product_name}" — đơn #${e.order_id}`,
                        {
                            autoClose: 3000,
                        },
                    );
                }
            }
        })

        /*
        |--------------------------------------------------------------------------
        | PAYMENT CONFIRMED
        |--------------------------------------------------------------------------
        */
        .listen(".order.payment-confirmed", (e) => {
            const order = localOrders.value.find((o) => o.id === e.id);

            if (order) {
                if (order.payment) {
                    order.payment.payment_status = e.payment_status;
                }

                order.status = e.status;

                toast.success(`Đơn ${e.order_code} đã xác nhận thanh toán!`, {
                    autoClose: 3000,
                });
            }
        });
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel("staff-orders");
    }
});

/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
const orderType = ref(props.filters?.order_type ?? "");
const tableId = ref(props.filters?.table_id ?? "");
const source = ref(props.filters?.source ?? "");

let searchTimeout = null;

const onSearchInput = () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
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
        {
            preserveState: true,
            replace: true,
        },
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
    Boolean(
        search.value ||
        status.value ||
        orderType.value ||
        tableId.value ||
        source.value,
    ),
);

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

const summary = computed(() => {
    const data = localOrders.value;

    return {
        total: data.length,

        pending: data.filter((o) => o.status === "PENDING").length,

        processing: data.filter((o) =>
            ["PROCESSING", "READY", "DELIVERING"].includes(o.status),
        ).length,

        completed: data.filter((o) => o.status === "COMPLETED").length,

        cancelled: data.filter((o) => o.status === "CANCELLED").length,

        revenue: data
            .filter((o) => o.status === "COMPLETED")
            .reduce((sum, o) => sum + Number(o.final_amount ?? 0), 0),
    };
});

const statusTabs = computed(() => [
    { value: "", label: "Tất cả", count: summary.value.total },
    { value: "PENDING", label: "Chờ duyệt", count: summary.value.pending },
    { value: "PROCESSING", label: "Đang xử lý", count: summary.value.processing },
    { value: "COMPLETED", label: "Hoàn thành", count: summary.value.completed },
    { value: "CANCELLED", label: "Đã hủy", count: summary.value.cancelled },
]);

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
        percentage: total > 0 ? Math.round((done / total) * 100) : 0,
    };
};

const getItemCount = (order) => {
    return (order.details ?? []).reduce((t, d) => t + Number(d.quantity ?? 0), 0);
};

const getOrderTypeLabel = (type) => {
    return orderTypeLabel[type] ?? type ?? "—";
};

const getOrderTypeIcon = (type) => {
    return orderTypeIcon[type] ?? "receipt_long";
};

const getStatusLabel = (status) => {
    return statusLabel[status] ?? status ?? "Không xác định";
};

const openOrder = (order) => {
    selectedOrderId.value = order.id;
};

const closeOrderModal = () => {
    selectedOrderId.value = null;
};
</script>

<template>
    <AdminLayout>
        <div class="admin-orders-page min-h-screen space-y-5 p-1 font-sans" :style="themeVars">
            <!-- ========================================================= -->
            <!-- PAGE HEADER -->
            <!-- ========================================================= -->

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.2em]" style="color: var(--brass)">
                        Quầy vận hành
                    </p>

                    <h1 class="mt-0.5 text-2xl font-black" style="color: var(--ink)">
                        Quản lý đơn hàng
                    </h1>
                </div>

                <div class="inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold"
                    style="border-color: var(--paper-shade); color: var(--ink-soft)">
                    <span class="h-2 w-2 rounded-full" :class="realtimeConnected ? 'animate-pulse' : ''"
                        :style="{ backgroundColor: realtimeConnected ? 'var(--forest)' : '#cbd5e1' }"></span>

                    {{ realtimeConnected ? "Đang cập nhật realtime" : "Realtime chưa kết nối" }}
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- STAT STRIP -->
            <!-- ========================================================= -->

            <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                <div class="stat-card" style="border-left-color: var(--brass)">
                    <p class="stat-label">Chờ duyệt</p>
                    <p class="stat-value" style="color: var(--brass)">{{ summary.pending }}</p>
                </div>

                <div class="stat-card" style="border-left-color: var(--denim)">
                    <p class="stat-label">Đang xử lý</p>
                    <p class="stat-value" style="color: var(--denim)">{{ summary.processing }}</p>
                </div>

                <div class="stat-card" style="border-left-color: var(--forest)">
                    <p class="stat-label">Hoàn thành</p>
                    <p class="stat-value" style="color: var(--forest)">{{ summary.completed }}</p>
                </div>

                <div class="stat-card" style="border-left-color: var(--rust)">
                    <p class="stat-label">Đã hủy</p>
                    <p class="stat-value" style="color: var(--rust)">{{ summary.cancelled }}</p>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- STATUS TABS + FILTERS -->
            <!-- ========================================================= -->

            <section class="rounded-2xl border bg-white p-4 shadow-sm" style="border-color: var(--paper-shade)">
                <div class="flex flex-wrap items-center gap-2">
                    <button v-for="tab in statusTabs" :key="tab.value" type="button" @click="setStatusTab(tab.value)"
                        class="rounded-full border px-3.5 py-1.5 text-xs font-bold transition" :style="status === tab.value
                            ? { backgroundColor: 'var(--ink)', color: '#fff', borderColor: 'var(--ink)' }
                            : { borderColor: 'var(--paper-shade)', color: 'var(--ink-soft)' }">
                        {{ tab.label }}
                        <span class="ml-1 opacity-70">{{ tab.count }}</span>
                    </button>

                    <button v-if="hasActiveFilters" type="button" @click="resetFilters"
                        class="ml-auto inline-flex items-center gap-1 text-xs font-bold" style="color: var(--rust)">
                        <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                        Xóa bộ lọc
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="relative xl:col-span-2">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[19px]"
                            style="color: var(--ink-soft)">search</span>

                        <input v-model="search" @input="onSearchInput" type="text"
                            placeholder="Mã đơn, tên khách, SĐT..."
                            class="w-full rounded-xl border py-2.5 pl-10 pr-4 text-sm outline-none transition focus:ring-4"
                            style="border-color: var(--paper-shade); background: var(--paper)" />
                    </div>

                    <select v-model="orderType" @change="applyFilters"
                        class="cursor-pointer rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                        style="border-color: var(--paper-shade); background: var(--paper); color: var(--ink)">
                        <option value="">Tất cả loại đơn</option>
                        <option value="DINE_IN">Tại chỗ</option>
                        <option value="TAKE_AWAY">Mang đi</option>
                        <option value="DELIVERY">Giao hàng</option>
                    </select>

                    <select v-model="tableId" @change="applyFilters"
                        class="cursor-pointer rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                        style="border-color: var(--paper-shade); background: var(--paper); color: var(--ink)">
                        <option value="">Tất cả bàn</option>
                        <option v-for="table in tables" :key="table.id" :value="table.id">
                            {{ table.table_name }}
                        </option>
                    </select>

                    <select v-model="source" @change="applyFilters"
                        class="cursor-pointer rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                        style="border-color: var(--paper-shade); background: var(--paper); color: var(--ink)">
                        <option value="">Tất cả nguồn</option>
                        <option value="CUSTOMER">Khách tự đặt</option>
                        <option value="STAFF">Nhân viên tạo</option>
                    </select>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- ORDER LIST -->
            <!-- ========================================================= -->

            <section class="overflow-hidden rounded-2xl border bg-white shadow-sm"
                style="border-color: var(--paper-shade)">
                <div class="flex items-center justify-between border-b px-5 py-4"
                    style="border-color: var(--paper-shade)">
                    <h2 class="text-base font-black" style="color: var(--ink)">Danh sách đơn hàng</h2>
                    <p class="text-xs font-semibold" style="color: var(--ink-soft)">
                        {{ summary.total }} đơn hàng
                    </p>
                </div>

                <!-- Empty -->
                <div v-if="localOrders.length === 0"
                    class="flex flex-col items-center justify-center px-6 py-20 text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl"
                        style="background: var(--paper-shade); color: var(--ink-soft)">
                        <span class="material-symbols-outlined text-4xl">receipt_long</span>
                    </div>

                    <h3 class="mt-5 text-base font-bold" style="color: var(--ink)">Không có đơn hàng</h3>

                    <p class="mt-1 max-w-sm text-sm" style="color: var(--ink-soft)">
                        {{ hasActiveFilters
                            ? "Thử thay đổi bộ lọc để xem kết quả khác."
                            : "Đơn hàng mới sẽ xuất hiện tại đây." }}
                    </p>

                    <button v-if="hasActiveFilters" type="button" @click="resetFilters"
                        class="mt-5 rounded-xl px-4 py-2.5 text-sm font-bold text-white" style="background: var(--ink)">
                        Xóa bộ lọc
                    </button>
                </div>

                <!-- Desktop table -->
                <div v-else class="hidden overflow-x-auto lg:block">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="text-left text-[11px] font-black uppercase tracking-wider"
                                style="color: var(--ink-soft); background: var(--paper)">
                                <th class="whitespace-nowrap px-5 py-3">Đơn hàng</th>
                                <th class="whitespace-nowrap px-5 py-3">Khách hàng</th>
                                <th class="whitespace-nowrap px-5 py-3">Loại đơn / Bàn</th>
                                <th class="whitespace-nowrap px-5 py-3 text-center">Pha chế</th>
                                <th class="whitespace-nowrap px-5 py-3">Thanh toán</th>
                                <th class="whitespace-nowrap px-5 py-3 text-right">Tổng tiền</th>
                                <th class="whitespace-nowrap px-5 py-3">Trạng thái</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="order in localOrders" :key="order.id" @click="openOrder(order)"
                                class="group cursor-pointer border-b transition-colors"
                                style="border-color: var(--paper-shade)"
                                :style="{ boxShadow: `inset 4px 0 0 0 ${statusTheme[order.status]?.text ?? '#cbd5e1'}` }">

                                <!-- Order -->
                                <td class="px-5 py-4">
                                    <p class="font-mono text-sm font-black" style="color: var(--ink)">
                                        #{{ order.order_code ?? order.id }}
                                    </p>
                                    <p class="mt-0.5 text-xs" style="color: var(--ink-soft)">
                                        {{ formatDate(order.created_at) }} · {{ getItemCount(order) }} món
                                    </p>
                                    <span
                                        class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :style="getSourceStyle(order.source)">
                                        {{ sourceLabel[order.source] ?? order.source }}
                                    </span>
                                </td>

                                <!-- Customer -->
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold" style="color: var(--ink)">
                                        {{ order.user?.name ?? "Khách vãng lai" }}
                                    </p>
                                    <p v-if="order.user?.phone" class="mt-0.5 text-xs" style="color: var(--ink-soft)">
                                        {{ order.user.phone }}
                                    </p>
                                </td>

                                <!-- Type / table -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px]"
                                            style="color: var(--ink-soft)">
                                            {{ getOrderTypeIcon(order.order_type) }}
                                        </span>
                                        <span class="text-sm" style="color: var(--ink)">{{
                                            getOrderTypeLabel(order.order_type) }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs" style="color: var(--ink-soft)">
                                        {{ order.table?.table_name ?? "Không có bàn" }}
                                    </p>
                                </td>

                                <!-- Barista -->
                                <td class="px-5 py-4">
                                    <div v-if="order.barista_progress" class="mx-auto w-24">
                                        <div class="mb-1 flex items-center justify-between">
                                            <span class="text-[10px] font-bold" style="color: var(--ink-soft)">Tiến
                                                độ</span>
                                            <span class="text-xs font-black"
                                                :style="{ color: order.barista_progress.is_complete ? 'var(--forest)' : 'var(--ink)' }">
                                                {{ order.barista_progress.label }}
                                            </span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full"
                                            style="background: var(--paper-shade)">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :style="{ width: `${order.barista_progress.percentage ?? 0}%`, background: 'var(--forest)' }">
                                            </div>
                                        </div>
                                    </div>
                                    <span v-else class="block text-center" style="color: var(--paper-shade)">—</span>
                                </td>

                                <!-- Payment -->
                                <td class="px-5 py-4">
                                    <p class="text-xs font-semibold" style="color: var(--ink)">
                                        {{ order.payment?.payment_method ?? "—" }}
                                    </p>
                                    <span
                                        class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :style="getPaymentStyle(order.payment?.payment_status)">
                                        {{ paymentStatusLabel[order.payment?.payment_status] ?? "Chưa thanh toán" }}
                                    </span>
                                </td>

                                <!-- Money -->
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <span class="font-mono text-sm font-black" style="color: var(--ink)">
                                        {{ formatMoney(order.final_amount) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold"
                                        :style="getStatusStyle(order.status)">
                                        <span class="h-2 w-2 rounded-full"
                                            :style="{ backgroundColor: statusTheme[order.status]?.text ?? '#9ca3af' }"></span>
                                        {{ getStatusLabel(order.status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ===================================================== -->
                <!-- MOBILE TICKET CARDS -->
                <!-- ===================================================== -->

                <div v-if="localOrders.length" class="space-y-3 p-4 lg:hidden">
                    <article v-for="order in localOrders" :key="`mobile-${order.id}`" @click="openOrder(order)"
                        class="ticket cursor-pointer overflow-hidden rounded-2xl shadow-sm active:scale-[0.99]">

                        <!-- ticket head -->
                        <div class="flex items-start justify-between gap-3 p-4"
                            :style="{ background: statusTheme[order.status]?.bg ?? 'var(--paper-shade)' }">
                            <div>
                                <p class="font-mono text-sm font-black" style="color: var(--ink)">
                                    #{{ order.order_code ?? order.id }}
                                </p>
                                <p class="mt-0.5 text-xs" style="color: var(--ink-soft)">
                                    {{ formatDate(order.created_at) }}
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-2.5 py-1 text-[11px] font-bold"
                                :style="{ color: statusTheme[order.status]?.text ?? '#6b7280' }">
                                {{ getStatusLabel(order.status) }}
                            </span>
                        </div>

                        <!-- torn edge -->
                        <div class="ticket-notch"
                            :style="{ '--notch-color': statusTheme[order.status]?.bg ?? 'var(--paper-shade)' }"></div>

                        <!-- ticket body -->
                        <div class="bg-white p-4" style="background: var(--paper)">
                            <div class="flex items-center justify-between rounded-xl p-3"
                                style="background: var(--paper-shade)">
                                <div>
                                    <p class="text-sm font-bold" style="color: var(--ink)">
                                        {{ order.user?.name ?? "Khách vãng lai" }}
                                    </p>
                                    <p v-if="order.user?.phone" class="mt-0.5 text-xs" style="color: var(--ink-soft)">
                                        {{ order.user.phone }}
                                    </p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-[10px] font-bold"
                                    :style="getSourceStyle(order.source)">
                                    {{ sourceLabel[order.source] ?? order.source }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider"
                                        style="color: var(--ink-soft)">Loại đơn / Bàn</p>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[17px]"
                                            style="color: var(--ink-soft)">
                                            {{ getOrderTypeIcon(order.order_type) }}
                                        </span>
                                        <span class="text-sm font-semibold" style="color: var(--ink)">
                                            {{ getOrderTypeLabel(order.order_type) }} · {{ order.table?.table_name ??
                                                "—" }}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider"
                                        style="color: var(--ink-soft)">Thanh toán</p>
                                    <p class="mt-1 text-sm font-semibold" style="color: var(--ink)">
                                        {{ order.payment?.payment_method ?? "—" }}
                                    </p>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :style="getPaymentStyle(order.payment?.payment_status)">
                                        {{ paymentStatusLabel[order.payment?.payment_status] ?? "Chưa thanh toán" }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t pt-4"
                                style="border-color: var(--paper-shade)">
                                <div v-if="order.barista_progress" class="flex items-center gap-2">
                                    <div class="w-16 overflow-hidden rounded-full"
                                        style="background: var(--paper-shade)">
                                        <div class="h-1.5 rounded-full"
                                            :style="{ width: `${order.barista_progress.percentage ?? 0}%`, background: 'var(--forest)' }">
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold" style="color: var(--ink-soft)">{{
                                        order.barista_progress.label }}</span>
                                </div>
                                <span v-else class="text-xs" style="color: var(--ink-soft)">{{ getItemCount(order) }}
                                    món</span>

                                <span class="font-mono text-base font-black" style="color: var(--ink)">
                                    {{ formatMoney(order.final_amount) }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- PAGINATION -->
            <!-- ========================================================= -->

            <div v-if="orders.links?.length > 3" class="flex flex-wrap items-center justify-center gap-1 pb-4">
                <template v-for="(link, index) in orders.links" :key="index">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" preserve-scroll
                        class="min-w-9 rounded-xl px-3 py-2 text-center text-xs font-bold transition-all duration-200"
                        :style="link.active
                            ? { background: 'var(--ink)', color: '#fff' }
                            : { border: '1px solid var(--paper-shade)', color: 'var(--ink-soft)' }" />

                    <span v-else v-html="link.label" class="min-w-9 rounded-xl px-3 py-2 text-center text-xs font-bold"
                        style="color: var(--paper-shade)" />
                </template>
            </div>
        </div>

        <OrderDetailModal :order="selectedOrder" @close="closeOrderModal" />
    </AdminLayout>
</template>

<style scoped>
.stat-card {
    background: #fff;
    border-radius: 1rem;
    border-left-width: 4px;
    padding: 1rem 1.1rem;
    box-shadow: 0 1px 2px rgba(36, 31, 27, 0.05);
}

.stat-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ink-soft);
}

.stat-value {
    margin-top: 0.4rem;
    font-size: 1.75rem;
    font-weight: 900;
}

.ticket-notch {
    height: 12px;
    background-image:
        linear-gradient(135deg, var(--notch-color) 8px, transparent 8px),
        linear-gradient(-135deg, var(--notch-color) 8px, transparent 8px);
    background-position: left top;
    background-size: 16px 16px;
    background-repeat: repeat-x;
    background-color: var(--paper);
}
</style>