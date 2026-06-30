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
        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.kho.index')" class="text-sm text-gray-400 hover:text-gray-600 transition">
                        ← Quay lại kho</Link>
                    <span class="text-gray-200">/</span>
                    <h1 class="text-xl font-bold text-gray-800">Lịch Sử Điều Chỉnh Tồn Kho</h1>
                </div>
                <Link :href="route('admin.kho.dieu-chinh.create')"
                    class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-xl transition">
                    + Điều chỉnh tồn kho
                </Link>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Nguyên liệu</label>
                    <select v-model="filters.material_id" @change="applyFilters"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Tất cả</option>
                        <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.material_name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Lý do</label>
                    <select v-model="filters.reason" @change="applyFilters"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Tất cả</option>
                        <option v-for="(label, value) in reasonLabels" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Từ ngày</label>
                    <input v-model="filters.from_date" @change="applyFilters" type="date"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Đến ngày</label>
                    <input v-model="filters.to_date" @change="applyFilters" type="date"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                </div>
                <div class="flex items-end">
                    <button @click="resetFilters"
                        class="text-sm text-gray-500 hover:text-gray-700 border border-gray-200 px-4 py-2 rounded-lg transition w-full">Xoá
                        lọc</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-left">
                            <th class="px-4 py-3 font-medium">Thời gian</th>
                            <th class="px-4 py-3 font-medium">Nguyên liệu</th>
                            <th class="px-4 py-3 font-medium text-right">Trước</th>
                            <th class="px-4 py-3 font-medium text-right">Sau</th>
                            <th class="px-4 py-3 font-medium text-right">Chênh lệch</th>
                            <th class="px-4 py-3 font-medium">Lý do</th>
                            <th class="px-4 py-3 font-medium">Người thực hiện</th>
                            <th class="px-4 py-3 font-medium">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="adjustments.data.length === 0">
                            <td colspan="8" class="text-center py-12 text-gray-400">Chưa có điều chỉnh nào.</td>
                        </tr>
                        <tr v-for="a in adjustments.data" :key="a.id" class="border-t border-gray-100">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ formatDate(a.created_at) }}</td>
                            <td class="px-4 py-3 text-gray-700 font-medium">{{ a.material?.material_name || '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-500">{{ formatNum(a.quantity_before) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-700">{{ formatNum(a.quantity_after) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-semibold"
                                :class="Number(a.change_amount) > 0 ? 'text-green-600' : (Number(a.change_amount) < 0 ? 'text-red-500' : 'text-gray-400')">
                                {{ Number(a.change_amount) > 0 ? '+' : '' }}{{ formatNum(a.change_amount) }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ reasonLabels[a.reason] || a.reason }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ userDisplayName(a.user) }}</td>
                            <td class="px-4 py-3 text-gray-400 max-w-[200px] truncate" :title="a.note">{{ a.note || '—'
                                }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="adjustments.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="(link, idx) in adjustments.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label"
                        class="px-3 py-1.5 text-sm rounded-lg transition"
                        :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'" />
                    <span v-else v-html="link.label" class="px-3 py-1.5 text-sm rounded-lg text-gray-300" />
                </template>
            </div>

        </div>
    </AdminLayout>
</template>