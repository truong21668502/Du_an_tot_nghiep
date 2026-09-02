<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import { debounce } from "lodash-es";

const props = defineProps({
    trashedTables: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

const search = ref(props.filters.search || "");

// Tự động tìm kiếm trong thùng rác
const performSearch = debounce((value) => {
    router.get(
        route('admin.tables.trash'),
        { search: value },
        { preserveState: true, replace: true }
    );
}, 400);

watch(search, (newVal) => {
    performSearch(newVal);
});

// Khôi phục bàn
const restoreTable = (id, name) => {
    if (confirm(`Bạn có muốn đưa "${name}" trở lại danh sách hoạt động?`)) {
        router.put(route('admin.tables.restore', id), {}, { preserveScroll: true });
    }
};

// Định dạng ngày giờ
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
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link 
                            :href="route('admin.tables.index')" 
                            class="p-1 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-full transition-colors"
                            title="Quay lại danh sách bàn"
                        >
                            <span class="material-symbols-outlined text-2xl">arrow_back</span>
                        </Link>
                        <h1 class="font-sans text-headline-md text-red-600 text-3xl font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-3xl">auto_delete</span>
                            THÙNG RÁC BÀN PHỤC VỤ
                        </h1>
                    </div>
                    <p class="font-sans text-body-medium text-on-surface-variant mt-1 ml-9">
                        Danh sách các bàn đã tạm ngưng hoặc vô hiệu hóa. Bạn có thể khôi phục lại bất kỳ lúc nào.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link 
                        :href="route('admin.tables.index')" 
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-surface-container-high text-on-surface hover:bg-surface-container-highest font-sans text-label-large rounded-full shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-md">table_restaurant</span>
                        Về sơ đồ bàn
                    </Link>
                </div>
            </div>

            <!-- Search & Filter bar -->
            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm font-sans flex items-center justify-between gap-4">
                <div class="flex-1 max-w-md flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                    <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Tìm bàn đã xóa theo tên hoặc khu vực..." 
                        class="w-full bg-transparent focus:outline-none text-on-surface text-body-medium"
                    />
                    <button 
                        v-if="search" 
                        @click="search = ''" 
                        class="text-outline hover:text-error transition-colors"
                    >
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                </div>
                <div class="text-body-small text-outline font-medium">
                    Tổng số trong thùng rác: <strong class="text-primary">{{ trashedTables.total || trashedTables.data.length }}</strong> bàn
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16">ID</th>
                                <th class="p-4 text-left">Tên / Số bàn</th>
                                <th class="p-4">Khu vực</th>
                                <th class="p-4">Sức chứa</th>
                                <th class="p-4">Thời điểm vô hiệu hoá / xóa</th>
                                <th class="p-4 text-right w-44">Thao tác xử lý</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr 
                                v-for="table in trashedTables.data" 
                                :key="table.id" 
                                class="hover:bg-surface-container-low/50 transition-colors opacity-90 hover:opacity-100"
                            >
                                <td class="p-4 text-outline font-mono">{{ table.id }}</td>
                                <td class="p-4 text-left">
                                    <span class="font-bold text-on-surface line-through decoration-error/50">
                                        {{ table.table_name }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 bg-surface-container-high rounded-full text-body-small">
                                        {{ table.area || 'Mặc định' }}
                                    </span>
                                </td>
                                <td class="p-4 font-semibold">{{ table.capacity }} người</td>
                                <td class="p-4 text-error font-medium text-body-small">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="material-symbols-outlined text-base">schedule</span>
                                        <span>{{ formatDateTime(table.deleted_at) }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Nút Khôi phục -->
                                        <button 
                                            @click="restoreTable(table.id, table.table_name)" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary hover:text-on-primary rounded-xl font-medium text-label-small transition-all cursor-pointer"
                                            title="Đưa bàn trở lại sơ đồ"
                                        >
                                            <span class="material-symbols-outlined text-base">restore_from_trash</span>
                                            <span>Khôi phục</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="trashedTables.data.length === 0">
                                <td colspan="6" class="p-12 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-5xl text-outline mb-2 block font-light">delete_sweep</span>
                                    <p class="font-medium">Thùng rác hiện đang trống.</p>
                                    <p class="text-body-small text-outline mt-1">Không có bàn nào bị xóa mềm hoặc tạm ngưng.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="trashedTables.links && trashedTables.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-center gap-1 font-sans">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in trashedTables.links"
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
        </div>
    </AdminLayout>
</template>