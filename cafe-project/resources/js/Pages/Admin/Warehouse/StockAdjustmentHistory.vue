<!-- StockAdjustmentHistory.vue -->
<script setup>
import { reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    adjustments: { type: Object, required: true },
    materials: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const filters = reactive({
    material_id: props.filters.material_id || '',
    reason: props.filters.reason || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
})

const reasonLabels = {
    kiem_ke: 'Kiểm kê định kỳ',
    that_thoat: 'Thất thoát',
    het_han: 'Hết hạn',
    hu_hong: 'Hư hỏng',
    khac: 'Khác',
}

function applyFilters() {
    router.get(route('admin.kho.dieu-chinh.index'), filters, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.material_id = ''
    filters.reason = ''
    filters.from_date = ''
    filters.to_date = ''
    applyFilters()
}

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function formatDate(val) {
    return new Date(val).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function userDisplayName(user) {
    if (!user) return '—'
    return user.name || user.full_name || user.username || user.email || '—'
}
</script>

<template>
    <AdminLayout title="Lịch Sử Điều Chỉnh Tồn Kho">
        <div class="space-y-6 font-sans">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.kho.index')"
                        class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại kho
                    </Link>
                    <span class="text-outline-variant">/</span>
                    <h1 class="text-headline-md font-bold text-on-surface text-primary text-3xl">Lịch Sử Điều Chỉnh Tồn Kho</h1>
                </div>
                <Link :href="route('admin.kho.dieu-chinh.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full font-bold transition-colors shadow-sm cursor-pointer">
                    <span class="material-symbols-outlined text-md">add</span> Điều chỉnh tồn kho
                </Link>
            </div>

            <div
                class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-5 grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Nguyên liệu</label>
                    <select v-model="filters.material_id" @change="applyFilters"
                        class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
                        <option value="">Tất cả</option>
                        <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.material_name }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Lý do</label>
                    <select v-model="filters.reason" @change="applyFilters"
                        class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
                        <option value="">Tất cả</option>
                        <option v-for="(label, value) in reasonLabels" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Từ ngày</label>
                    <input v-model="filters.from_date" @change="applyFilters" type="date"
                        class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Đến ngày</label>
                    <input v-model="filters.to_date" @change="applyFilters" type="date"
                        class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                </div>
                <div class="flex flex-col">
                    <button @click="resetFilters"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface rounded-xl transition-colors font-bold w-full">
                        <span class="material-symbols-outlined text-sm">filter_alt_off</span> Xoá lọc
                    </button>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-b-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4">Thời gian</th>
                                <th class="p-4">Nguyên liệu</th>
                                <th class="p-4 text-right">Trước</th>
                                <th class="p-4 text-right">Sau</th>
                                <th class="p-4 text-right">Chênh lệch</th>
                                <th class="p-4">Lý do</th>
                                <th class="p-4">Người thực hiện</th>
                                <th class="p-4">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-if="adjustments.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-on-surface-variant font-medium">
                                    Chưa có điều chỉnh nào.
                                </td>
                            </tr>
                            <tr v-for="a in adjustments.data" :key="a.id"
                                class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-on-surface-variant whitespace-nowrap">{{ formatDate(a.created_at) }}
                                </td>
                                <td class="p-4 font-bold text-on-surface">{{ a.material?.material_name || '—' }}</td>
                                <td class="p-4 text-right font-mono text-on-surface-variant">{{
                                    formatNum(a.quantity_before) }}</td>
                                <td class="p-4 text-right font-mono text-on-surface">{{ formatNum(a.quantity_after) }}
                                </td>
                                <td class="p-4 text-right font-mono font-bold"
                                    :class="Number(a.change_amount) > 0 ? 'text-tertiary' : (Number(a.change_amount) < 0 ? 'text-error' : 'text-on-surface-variant')">
                                    {{ Number(a.change_amount) > 0 ? '+' : '' }}{{ formatNum(a.change_amount) }}
                                </td>
                                <td class="p-4 text-on-surface-variant">{{ reasonLabels[a.reason] || a.reason }}</td>
                                <td class="p-4 text-on-surface-variant">{{ userDisplayName(a.user) }}</td>
                                <td class="p-4 text-on-surface-variant/70 max-w-[200px] truncate" :title="a.note">{{
                                    a.note || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="adjustments.links?.length > 3"
                class="flex items-center justify-center gap-1 mt-6 mb-3 font-sans">
                <template v-for="(link, idx) in adjustments.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" preserve-scroll
                        class="px-3 py-1.5 text-label-medium rounded-lg transition-all"
                        :class="link.active
                            ? 'bg-primary text-on-primary font-bold shadow-sm'
                            : 'bg-surface border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-high'" />
                    <span v-else v-html="link.label"
                        class="px-3 py-1.5 text-label-medium rounded-lg text-on-surface-variant/40 opacity-50 cursor-not-allowed" />
                </template>
            </div>

        </div>
    </AdminLayout>
</template>