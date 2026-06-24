<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

import TableFormModal from "./Components/TableFormModal.vue";

const props = defineProps({
    tables: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    distinctAreas: { type: Array, default: () => [] }
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedTable = ref(null);

// Trạng thái cho cụm bộ lọc tìm kiếm
const searchFilters = ref({
    search: props.filters.search || "",
    area: props.filters.area || "",
    capacity: props.filters.capacity || "",
    status: props.filters.status || "",
});

const clearFilters = () => {
    searchFilters.value.search = "";
    searchFilters.value.area = "";
    searchFilters.value.capacity = "";
    searchFilters.value.status = "";
};

// Theo dõi sự thay đổi của bộ lọc để tự động gọi lại dữ liệu (Inertia Get)
watch(
    searchFilters,
    (newFilters) => {
        router.get("/quan-tri/ban", newFilters, {
            preserveState: true,
            replace: true,
        });
    },
    { deep: true }
);

// Trạng thái đóng/mở modal form
const openCreateModal = () => {
    isEditMode.value = false;
    selectedTable.value = null;
    isModalOpen.value = true;
};

const openEditModal = (table) => {
    isEditMode.value = true;
    selectedTable.value = table;
    isModalOpen.value = true;
};

const deleteTable = (id, name) => {
    if (confirm(`Bạn có chắc muốn xóa cấu hình "${name}" khỏi sơ đồ quán không?`)) {
        router.delete(`/quan-tri/ban/${id}`);
    }
};

// Trả ra nhãn hiển thị trạng thái đẹp mắt
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'EMPTY': return 'bg-primary-container text-on-primary-container';
        case 'OCCUPIED': return 'bg-error-container text-on-error-container font-bold';
        case 'RESERVED': return 'bg-tertiary-container text-on-tertiary-container';
        default: return 'bg-surface-container text-outline';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'EMPTY': return 'Bàn trống';
        case 'OCCUPIED': return 'Đang có khách';
        case 'RESERVED': return 'Đã đặt trước';
        default: return status;
    }
};

// Hàm định dạng ngày giờ thân thiện để Admin dễ giám sát
const formatDateTime = (dateStr) => {
    if (!dateStr) return "---";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    return `${hours}:${minutes} — ${day}/${month}/${year}`;
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Sơ đồ & Quản lý bàn ăn</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Quản lý định danh mã QR đầu cuối phục vụ tại bàn cho Nắng Coffee.</p>
                </div>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-md">add</span> Thêm bàn mới
                </button>
            </div>

            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm font-sans space-y-3">
                <div class="flex items-center gap-2 text-label-large text-outline font-bold uppercase tracking-wider select-none">
                    <span class="material-symbols-outlined text-lg">filter_list</span>
                    <span>Bộ lọc sơ đồ bàn</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-body-medium">
                    <div class="sm:col-span-4 flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                        <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                        <input v-model="searchFilters.search" type="text" placeholder="Tìm tên bàn hoặc URL QR Code..." class="w-full bg-transparent focus:outline-none text-on-surface" />
                    </div>

                    <div class="sm:col-span-3">
                        <select v-model="searchFilters.area" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Tất cả khu vực --</option>
                            <option v-for="area in distinctAreas" :key="area" :value="area">{{ area }}</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <select v-model="searchFilters.capacity" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Sức chứa --</option>
                            <option value="2">Bàn 2 người</option>
                            <option value="4">Bàn 4 người</option>
                            <option value="6">Bàn 6 người</option>
                            <option value="8">Bàn 8 người gia đình</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <select v-model="searchFilters.status" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                            <option value="">-- Trạng thái --</option>
                            <option value="EMPTY">Bàn trống</option>
                            <option value="OCCUPIED">Đang có khách</option>
                        </select>
                    </div>

                    <div class="sm:col-span-1 text-right flex justify-end">
                        <button @click="clearFilters" v-if="searchFilters.search || searchFilters.area || searchFilters.capacity || searchFilters.status" class="w-full h-full p-2 hover:bg-error-container/20 text-outline hover:text-error rounded-xl transition-colors flex items-center justify-center cursor-pointer" title="Xóa toàn bộ bộ lọc">
                            <span class="material-symbols-outlined">filter_alt_off</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16">ID</th>
                                <th class="p-4">Tên / Số bàn</th>
                                <th class="p-4">Khu vực tầng</th>
                                <th class="p-4">Sức chứa tối đa</th>
                                <th class="p-4 hidden md:table-cell">Định danh QR Link</th>
                                <th class="p-4 hidden md:table-cell">QR Link Ảnh</th>
                                <th class="p-4">Trạng thái</th>
                                <th class="p-4 text-right w-36">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="table in tables.data" :key="table.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-outline font-mono">{{ table.id }}</td>
                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        
                                        <div class="font-bold text-primary hover:text-primary-dark transition-colors">
                                            {{ table.table_name }}
                                        </div>
                                    
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all animate-fade-in">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                <span class="text-neutral-400 select-none">Tạo lúc:</span> 
                                                <span>{{ formatDateTime(table.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                                <span class="text-neutral-400 select-none">Sửa cuối:</span> 
                                                <span>{{ formatDateTime(table.updated_at) }}</span>
                                            </div>
                                            <div class="absolute top-full left-4 border-4 border-transparent border-t-neutral-900"></div>
                                        </div>
                                    
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 bg-surface-container-high rounded-full text-body-small">
                                        {{ table.area || 'Mặc định' }}
                                    </span>
                                </td>
                                <td class="p-4 font-bold">{{ table.capacity }} người</td>
                                <td class="p-4 hidden md:table-cell text-left font-mono text-body-small text-on-surface-variant truncate max-w-xs">
                                    {{ table.qr_code || 'Chưa gắn định danh mã QR' }}
                                </td>
                                <td class="p-4 hidden md:table-cell text-left font-mono text-body-small text-on-surface-variant truncate max-w-xs">
                                    {{ table.qr_image || 'Chưa có mã ảnh QR' }}
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="['px-3 py-1 rounded-full text-label-medium font-bold', getStatusBadgeClass(table.status)]">
                                        {{ getStatusLabel(table.status) }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(table)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer" title="Sửa thông tin bàn">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteTable(table.id, table.table_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" title="Xóa bàn khỏi sơ đồ">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="tables.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block font-light">table_restaurant</span>
                                    Không tìm thấy dữ liệu bàn phục vụ phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="tables.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-center gap-1 font-sans">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in tables.links"
                        :key="index"
                        :href="link.url"
                        v-html="link.label"
                        :preserve-scroll="true" 
                        :class="[
                            'px-3 py-1.5 text-label-medium rounded-lg transition-all',
                            link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high',
                            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                        ]"
                    />
                </div>
            </div>

            <TableFormModal :isOpen="isModalOpen" :editMode="isEditMode" :tableData="selectedTable" @close="isModalOpen = false" />
        </div>
    </AdminLayout>
</template>