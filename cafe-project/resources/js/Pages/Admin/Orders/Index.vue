```vue
<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

const props = defineProps({
    orders: Object,
    tables: Array,
    filters: Object,
});

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

const statusClass = {
    PENDING: "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20",
    PROCESSING: "bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20",
    READY: "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20",
    DELIVERING:
        "bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20",
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

const sourceLabel = {
    CUSTOMER: "Khách tự đặt",
    STAFF: "Nhân viên tạo",
};

const sourceClass = {
    CUSTOMER:
        "bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-600/20",
    STAFF: "bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/20",
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
    };
});

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

const getOrderTypeLabel = (type) => {
    return orderTypeLabel[type] ?? type ?? "—";
};

const getOrderTypeIcon = (type) => {
    return orderTypeIcon[type] ?? "receipt_long";
};

const getStatusLabel = (status) => {
    return statusLabel[status] ?? status ?? "Không xác định";
};

const getStatusClass = (status) => {
    return statusClass[status] ?? "bg-slate-100 text-slate-700";
};

const getStatusDotClass = (status) => {
    return statusDotClass[status] ?? "bg-slate-400";
};

const openOrder = (order) => {
    router.visit(`/quan-tri/don-hang/${order.id}`);
};

const refreshOrders = () => {
    router.reload({
        only: ["orders"],
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <AdminLayout>
        <div class="min-h-screen space-y-6 bg-slate-50/50 p-1 font-sans">
            <!-- ========================================================= -->
            <!-- SUMMARY CARDS -->
            <!-- ========================================================= -->

            <section class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <!-- Pending -->
                <div
                    class="group relative overflow-hidden rounded-2xl border border-amber-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Chờ duyệt
                            </p>

                            <p class="mt-3 text-3xl font-black text-amber-600">
                                {{ summary.pending }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Đơn cần xử lý
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-transform duration-300 group-hover:scale-110">
                            <span class="material-symbols-outlined">
                                hourglass_top
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Processing -->
                <div
                    class="group relative overflow-hidden rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Đang xử lý
                            </p>

                            <p class="mt-3 text-3xl font-black text-blue-600">
                                {{ summary.processing }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Đang pha chế / giao
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-transform duration-300 group-hover:scale-110">
                            <span class="material-symbols-outlined">
                                coffee
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Completed -->
                <div
                    class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Hoàn thành
                            </p>

                            <p class="mt-3 text-3xl font-black text-emerald-600">
                                {{ summary.completed }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Đơn đã hoàn tất
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-transform duration-300 group-hover:scale-110">
                            <span class="material-symbols-outlined">
                                task_alt
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cancelled -->
                <div
                    class="group relative overflow-hidden rounded-2xl border border-red-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Đã hủy
                            </p>

                            <p class="mt-3 text-3xl font-black text-red-600">
                                {{ summary.cancelled }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Đơn bị hủy
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-600 transition-transform duration-300 group-hover:scale-110">
                            <span class="material-symbols-outlined">
                                cancel
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- FILTER -->
            <!-- ========================================================= -->

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <span class="material-symbols-outlined text-xl">
                                tune
                            </span>
                        </div>

                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Bộ lọc đơn hàng
                            </h2>

                            <p class="text-xs text-slate-500">
                                Tìm kiếm và lọc dữ liệu
                            </p>
                        </div>
                    </div>

                    <button v-if="hasActiveFilters" type="button" @click="resetFilters"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-primary transition hover:opacity-70">
                        <span class="material-symbols-outlined text-[16px]">
                            restart_alt
                        </span>

                        Xóa bộ lọc
                    </button>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <!-- Search -->
                        <div class="xl:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-slate-600">
                                Tìm kiếm
                            </label>

                            <div class="relative">
                                <span
                                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[19px] text-slate-400">
                                    search
                                </span>

                                <input v-model="search" @input="onSearchInput" type="text"
                                    placeholder="Mã đơn, tên khách, SĐT..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/10" />
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-600">
                                Trạng thái
                            </label>

                            <select v-model="status" @change="applyFilters"
                                class="w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/10">
                                <option value="">Tất cả trạng thái</option>

                                <option value="PENDING">Chờ duyệt</option>

                                <option value="PROCESSING">Đang pha chế</option>

                                <option value="COMPLETED">Hoàn thành</option>

                                <option value="CANCELLED">Đã hủy</option>
                            </select>
                        </div>

                        <!-- Order type -->
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-600">
                                Loại đơn
                            </label>

                            <select v-model="orderType" @change="applyFilters"
                                class="w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/10">
                                <option value="">Tất cả loại đơn</option>

                                <option value="DINE_IN">Tại chỗ</option>

                                <option value="TAKE_AWAY">Mang đi</option>

                                <option value="DELIVERY">Giao hàng</option>
                            </select>
                        </div>

                        <!-- Table -->
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-600">
                                Bàn
                            </label>

                            <select v-model="tableId" @change="applyFilters"
                                class="w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/10">
                                <option value="">Tất cả bàn</option>

                                <option v-for="table in tables" :key="table.id" :value="table.id">
                                    {{ table.table_name }}
                                </option>
                            </select>
                        </div>

                        <!-- Source -->
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-600">
                                Nguồn đơn
                            </label>

                            <select v-model="source" @change="applyFilters"
                                class="w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/10">
                                <option value="">Tất cả nguồn</option>

                                <option value="CUSTOMER">Khách tự đặt</option>

                                <option value="STAFF">Nhân viên tạo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- ORDER LIST -->
            <!-- ========================================================= -->

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <!-- Table Header -->
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-900">
                            Danh sách đơn hàng
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ summary.total }} đơn hàng đang hiển thị
                        </p>
                    </div>

                    <div
                        class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">
                        <span class="h-2 w-2 rounded-full" :class="realtimeConnected
                            ? 'animate-pulse bg-emerald-500'
                            : 'bg-slate-400'
                            "></span>

                        {{
                            realtimeConnected
                                ? "Đang cập nhật realtime"
                                : "Realtime chưa kết nối"
                        }}
                    </div>
                </div>

                <!-- Empty -->
                <div v-if="localOrders.length === 0"
                    class="flex flex-col items-center justify-center px-6 py-20 text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                        <span class="material-symbols-outlined text-4xl">
                            receipt_long
                        </span>
                    </div>

                    <h3 class="mt-5 text-base font-bold text-slate-900">
                        Không có đơn hàng
                    </h3>

                    <p class="mt-1 max-w-sm text-sm text-slate-500">
                        {{
                            hasActiveFilters
                                ? "Thử thay đổi bộ lọc để xem kết quả khác."
                                : "Đơn hàng mới sẽ xuất hiện tại đây."
                        }}
                    </p>

                    <button v-if="hasActiveFilters" type="button" @click="resetFilters"
                        class="mt-5 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:opacity-90">
                        Xóa bộ lọc
                    </button>
                </div>

                <!-- Desktop Table -->
                <div v-else class="hidden overflow-x-auto lg:block">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70">
                                <th
                                    class="whitespace-nowrap px-5 py-4 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Đơn hàng
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-4 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Khách hàng
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-4 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Nguồn
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-4 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Bàn
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-4 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Loại đơn
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-4 text-center text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Pha chế
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-4 text-right text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Tổng tiền
                                </th>

                                <th
                                    class="whitespace-nowrap px-5 py-4 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    Trạng thái
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="order in localOrders" :key="order.id" @click="openOrder(order)"
                                class="group cursor-pointer transition-colors duration-200 hover:bg-slate-50/80">
                                <!-- Order -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <span class="material-symbols-outlined">
                                                receipt_long
                                            </span>
                                        </div>

                                        <div>
                                            <p class="font-mono text-sm font-black text-primary">
                                                #{{ order.id }}
                                            </p>

                                            <p class="mt-0.5 text-xs text-slate-400">
                                                {{
                                                    formatDate(order.created_at)
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Customer -->
                                <td class="px-5 py-4">
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">
                                            {{
                                                order.user?.name ??
                                                "Khách vãng lai"
                                            }}
                                        </p>

                                        <p v-if="order.user?.phone" class="mt-0.5 text-xs text-slate-400">
                                            {{ order.user.phone }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Source -->
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-xs font-bold"
                                        :class="sourceClass[order.source]">
                                        <span class="material-symbols-outlined text-[15px]">
                                            {{
                                                order.source === "CUSTOMER"
                                                    ? "person"
                                                    : "badge"
                                            }}
                                        </span>

                                        {{
                                            sourceLabel[order.source] ??
                                            order.source
                                        }}
                                    </span>
                                </td>

                                <!-- Table -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined text-[17px]">
                                                table_restaurant
                                            </span>
                                        </span>

                                        <span class="text-sm font-semibold text-slate-700">
                                            {{ order.table?.table_name ?? "—" }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Order type -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] text-slate-400">
                                            {{
                                                getOrderTypeIcon(
                                                    order.order_type,
                                                )
                                            }}
                                        </span>

                                        <span class="text-sm text-slate-600">
                                            {{
                                                getOrderTypeLabel(
                                                    order.order_type,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Barista -->
                                <td class="px-5 py-4">
                                    <div v-if="order.barista_progress" class="mx-auto w-28">
                                        <div class="mb-1.5 flex items-center justify-between">
                                            <span class="text-[10px] font-bold text-slate-500">
                                                Tiến độ
                                            </span>

                                            <span class="text-xs font-black" :class="order.barista_progress
                                                .is_complete
                                                ? 'text-emerald-600'
                                                : 'text-slate-700'
                                                ">
                                                {{
                                                    order.barista_progress.label
                                                }}
                                            </span>
                                        </div>

                                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                                :style="{
                                                    width: `${order.barista_progress
                                                        .percentage ?? 0
                                                        }%`,
                                                }"></div>
                                        </div>
                                    </div>

                                    <span v-else class="block text-center text-slate-300">
                                        —
                                    </span>
                                </td>

                                <!-- Money -->
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <span class="font-mono text-sm font-black text-slate-900">
                                        {{ formatMoney(order.final_amount) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold"
                                        :class="getStatusClass(order.status)">
                                        <span class="h-2 w-2 rounded-full" :class="getStatusDotClass(order.status)
                                            "></span>

                                        {{ getStatusLabel(order.status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ===================================================== -->
                <!-- MOBILE CARDS -->
                <!-- ===================================================== -->

                <div v-if="localOrders.length" class="space-y-3 p-4 lg:hidden">
                    <article v-for="order in localOrders" :key="`mobile-${order.id}`" @click="openOrder(order)"
                        class="cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-primary/20 hover:shadow-md active:scale-[0.99]">
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <span class="material-symbols-outlined">
                                        receipt_long
                                    </span>
                                </div>

                                <div>
                                    <p class="font-mono text-sm font-black text-primary">
                                        #{{ order.id }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ formatDate(order.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-[11px] font-bold"
                                :class="getStatusClass(order.status)">
                                <span class="h-1.5 w-1.5 rounded-full" :class="getStatusDotClass(order.status)"></span>

                                {{ getStatusLabel(order.status) }}
                            </span>
                        </div>

                        <!-- Customer -->
                        <div class="mt-4 rounded-xl bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">
                                        {{
                                            order.user?.name ?? "Khách vãng lai"
                                        }}
                                    </p>

                                    <p v-if="order.user?.phone" class="mt-0.5 text-xs text-slate-400">
                                        {{ order.user.phone }}
                                    </p>
                                </div>

                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-bold"
                                    :class="sourceClass[order.source]">
                                    {{
                                        sourceLabel[order.source] ??
                                        order.source
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    Bàn
                                </p>

                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[17px] text-slate-400">
                                        table_restaurant
                                    </span>

                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ order.table?.table_name ?? "—" }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    Loại đơn
                                </p>

                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[17px] text-slate-400">
                                        {{ getOrderTypeIcon(order.order_type) }}
                                    </span>

                                    <span class="text-sm font-semibold text-slate-700">
                                        {{
                                            getOrderTypeLabel(order.order_type)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                            <div v-if="order.barista_progress" class="flex items-center gap-2">
                                <div class="w-16 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-1.5 rounded-full bg-emerald-500" :style="{
                                        width: `${order.barista_progress
                                            .percentage ?? 0
                                            }%`,
                                    }"></div>
                                </div>

                                <span class="text-xs font-bold text-slate-500">
                                    {{ order.barista_progress.label }}
                                </span>
                            </div>

                            <span v-else class="text-xs text-slate-400">
                                Chưa pha chế
                            </span>

                            <span class="font-mono text-base font-black text-slate-900">
                                {{ formatMoney(order.final_amount) }}
                            </span>
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
                        :class="link.active
                            ? 'bg-slate-900 text-white shadow-md'
                            : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                            " />

                    <span v-else v-html="link.label"
                        class="min-w-9 cursor-not-allowed rounded-xl px-3 py-2 text-center text-xs font-bold text-slate-300" />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>