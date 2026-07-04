<script setup>
import { ref } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    orders: Object,
    tables: Array,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
const orderType = ref(props.filters?.order_type ?? "");
const tableId = ref(props.filters?.table_id ?? "");

let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
};

const applyFilters = () => {
    router.get(
        "/quan-tri/don-hang",
        {
            search: search.value || undefined,
            status: status.value || undefined,
            order_type: orderType.value || undefined,
            table_id: tableId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const statusLabel = {
    PENDING: "Chờ duyệt",
    PROCESSING: "Đang pha chế",
    READY: "Sẵn sàng",
    DELIVERING: "Đang giao",
    COMPLETED: "Hoàn thành",
    CANCELLED: "Đã hủy",
};

const statusClass = {
    PENDING: "bg-secondary-container text-on-secondary-container",
    PROCESSING: "bg-primary-container text-on-primary-container",
    READY: "bg-tertiary-container text-on-tertiary-container",
    DELIVERING: "bg-primary-container text-on-primary-container",
    COMPLETED: "bg-tertiary-container text-on-tertiary-container",
    CANCELLED: "bg-error-container text-error",
};

const formatMoney = (value) => Number(value).toLocaleString("vi-VN") + "đ";
const formatDate = (value) => new Date(value).toLocaleString("vi-VN");
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Quản lý đơn hàng</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Theo dõi và xử lý đơn hàng tại quầy.
                    </p>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 p-4 flex flex-wrap gap-3 shadow-sm">
                <input v-model="search" @input="onSearchInput" type="text"
                    placeholder="Tìm theo mã đơn, tên khách, SĐT..."
                    class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-sans text-body-medium w-64" />

                <select v-model="status" @change="applyFilters"
                    class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-sans text-body-medium">
                    <option value="">Tất cả trạng thái</option>
                    <option value="PENDING">Chờ duyệt</option>
                    <option value="PROCESSING">Đang pha chế</option>
                    <option value="COMPLETED">Hoàn thành</option>
                    <option value="CANCELLED">Đã hủy</option>
                </select>

                <select v-model="orderType" @change="applyFilters"
                    class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-sans text-body-medium">
                    <option value="">Tất cả loại đơn</option>
                    <option value="DINE_IN">Tại chỗ</option>
                    <option value="TAKE_AWAY">Mang đi</option>
                    <option value="DELIVERY">Giao hàng</option>
                </select>

                <select v-model="tableId" @change="applyFilters"
                    class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-sans text-body-medium">
                    <option value="">Tất cả bàn</option>
                    <option v-for="t in tables" :key="t.id" :value="t.id">{{ t.table_name }}</option>
                </select>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4">Mã đơn</th>
                                <th class="p-4">Khách hàng</th>
                                <th class="p-4">Bàn</th>
                                <th class="p-4">Loại đơn</th>
                                <th class="p-4 text-center">Số món</th>
                                <th class="p-4 text-right">Tổng tiền</th>
                                <th class="p-4">Trạng thái</th>
                                <th class="p-4">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="order in orders.data" :key="order.id"
                                class="hover:bg-surface-container-low/50 cursor-pointer"
                                @click="router.visit(`/quan-tri/don-hang/${order.id}`)">
                                <td class="p-4 font-bold text-primary">#{{ order.id }}</td>
                                <td class="p-4">{{ order.user?.name ?? "Khách vãng lai" }}</td>
                                <td class="p-4">{{ order.table?.table_name ?? "—" }}</td>
                                <td class="p-4">
                                    {{ {
                                        DINE_IN: "Tại chỗ", TAKE_AWAY: "Mang đi", DELIVERY: "Giao hàng"
                                    }[order.order_type] }}
                                </td>
                                <td class="p-4 text-center">{{ order.details_count }}</td>
                                <td class="p-4 text-right font-mono">{{ formatMoney(order.final_amount) }}</td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full text-label-medium font-bold"
                                        :class="statusClass[order.status]">
                                        {{ statusLabel[order.status] }}
                                    </span>
                                </td>
                                <td class="p-4 text-on-surface-variant text-body-small">{{ formatDate(order.created_at)
                                }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="orders.links" class="flex items-left justify-center gap-1 mt-6 font-sans">
                <Component :is="link.url ? Link : 'span'" v-for="(link, index) in orders.links" :key="index"
                    :href="link.url" v-html="link.label" :class="[
                        'px-3 py-1.5 text-label-medium rounded-lg',
                        link.active ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant',
                        !link.url ? 'opacity-40' : 'cursor-pointer',
                    ]" />
            </div>
        </div>
    </AdminLayout>
</template>