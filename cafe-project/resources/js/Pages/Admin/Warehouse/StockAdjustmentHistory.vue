<script setup>
import { reactive, computed } from 'vue'
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

const reasonIcons = {
    kiem_ke: 'fact_check',
    that_thoat: 'report',
    het_han: 'event_busy',
    hu_hong: 'broken_image',
    khac: 'more_horiz',
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
    return new Date(val).toLocaleString('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

function userDisplayName(user) {
    if (!user) return '—'
    return user.name || user.full_name || user.username || user.email || '—'
}

function toInputUnit(value, material) {
    if (!material) return formatNum(value)
    const rate = Number(material.exchange_rate)
    if (!rate) return formatNum(value)
    return Number((Number(value) / rate).toFixed(2)).toLocaleString('vi-VN')
}

function unitLabel(material) {
    return material?.input_unit || material?.base_unit || ''
}

// Tổng hợp số liệu nhanh cho summary bar
const summary = computed(() => {
    const data = props.adjustments.data
    const total = data.length
    const increases = data.filter(a => Number(a.change_amount) > 0).length
    const decreases = data.filter(a => Number(a.change_amount) < 0).length
    return { total, increases, decreases }
})

const hasActiveFilters = computed(() =>
    filters.material_id || filters.reason || filters.from_date || filters.to_date
)
</script>

<template>
    <AdminLayout title="Lịch Sử Điều Chỉnh Tồn Kho">
        <div class="space-y-5 font-sans">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.kho.index')"
                            class="inline-flex items-center gap-1 text-label-medium text-on-surface-variant hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                            Kho
                        </Link>
                        <span class="text-outline-variant text-label-small">/</span>
                        <span class="text-label-medium text-on-surface-variant">Điều chỉnh tồn kho</span>
                    </div>
                    <h1 class="text-2xl font-bold text-on-surface">Lịch Sử Điều Chỉnh</h1>
                </div>
                <Link :href="route('admin.kho.dieu-chinh.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full font-bold transition-colors shadow-sm self-start sm:self-auto">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Điều chỉnh mới
                </Link>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-surface border border-outline-variant/20 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-primary text-[20px]">history</span>
                    </div>
                    <div>
                        <p class="text-label-small text-on-surface-variant">Trang này</p>
                        <p class="text-title-large font-bold text-on-surface font-mono">{{ summary.total }}</p>
                    </div>
                </div>
                <div class="bg-surface border border-outline-variant/20 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-tertiary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-tertiary text-[20px]">trending_up</span>
                    </div>
                    <div>
                        <p class="text-label-small text-on-surface-variant">Tăng tồn</p>
                        <p class="text-title-large font-bold text-tertiary font-mono">{{ summary.increases }}</p>
                    </div>
                </div>
                <div class="bg-surface border border-outline-variant/20 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-error/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-error text-[20px]">trending_down</span>
                    </div>
                    <div>
                        <p class="text-label-small text-on-surface-variant">Giảm tồn</p>
                        <p class="text-title-large font-bold text-error font-mono">{{ summary.decreases }}</p>
                    </div>
                </div>
            </div>

            <!-- Bộ lọc -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-label-medium font-bold text-on-surface-variant flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">filter_list</span>
                        Lọc kết quả
                    </p>
                    <button v-if="hasActiveFilters" @click="resetFilters"
                        class="text-label-small text-primary hover:text-primary/70 transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">close</span>
                        Xoá bộ lọc
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-small text-on-surface-variant font-bold">Nguyên liệu</label>
                        <select v-model="filters.material_id" @change="applyFilters"
                            class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
                            <option value="">Tất cả</option>
                            <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.material_name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-small text-on-surface-variant font-bold">Lý do</label>
                        <select v-model="filters.reason" @change="applyFilters"
                            class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
                            <option value="">Tất cả</option>
                            <option v-for="(label, value) in reasonLabels" :key="value" :value="value">{{ label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-small text-on-surface-variant font-bold">Từ ngày</label>
                        <input v-model="filters.from_date" @change="applyFilters" type="date"
                            class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-small text-on-surface-variant font-bold">Đến ngày</label>
                        <input v-model="filters.to_date" @change="applyFilters" type="date"
                            class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                </div>
            </div>

            <!-- Danh sách dạng card thay vì bảng ngang khó đọc -->
            <div class="space-y-2">

                <div v-if="adjustments.data.length === 0"
                    class="bg-surface border border-outline-variant/20 rounded-2xl p-16 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[48px] mb-3 block text-outline">manage_search</span>
                    <p class="text-body-large font-medium">Chưa có điều chỉnh nào.</p>
                    <p class="text-body-medium mt-1">
                        {{ hasActiveFilters
                            ? 'Thử thay đổi bộ lọc để xem kết quả khác.' :
                            'Bắt đầu bằng cách tạo điều chỉnh đầu tiên.'
                        }}
                    </p>
                </div>

                <div v-for="a in adjustments.data" :key="a.id"
                    class="bg-surface border border-outline-variant/20 rounded-2xl p-5 hover:border-outline-variant/40 hover:shadow-sm transition-all">

                    <div class="flex items-start justify-between gap-4">

                        <!-- Trái: Icon chênh lệch + tên nguyên liệu -->
                        <div class="flex items-start gap-4 min-w-0">

                            <!-- Icon chênh lệch -->
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" :class="Number(a.change_amount) > 0
                                ? 'bg-tertiary/10'
                                : (Number(a.change_amount) < 0 ? 'bg-error/10' : 'bg-surface-container-high')">
                                <span class="material-symbols-outlined text-[22px]" :class="Number(a.change_amount) > 0
                                    ? 'text-tertiary'
                                    : (Number(a.change_amount) < 0 ? 'text-error' : 'text-on-surface-variant')">
                                    {{ Number(a.change_amount) > 0 ? 'trending_up' : (Number(a.change_amount) < 0
                                        ? 'trending_down' : 'check_circle') }} </span>
                            </div>

                            <div class="min-w-0">
                                <!-- Tên nguyên liệu -->
                                <p class="text-body-large font-bold text-on-surface truncate">
                                    {{ a.material?.material_name || '—' }}
                                </p>

                                <!-- Số liệu: Trước → Sau -->
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="font-mono text-body-medium text-on-surface-variant">
                                        {{ toInputUnit(a.quantity_before, a.material) }} {{ unitLabel(a.material) }}
                                    </span>
                                    <span
                                        class="material-symbols-outlined text-[16px] text-outline">arrow_forward</span>
                                    <span class="font-mono text-body-medium font-bold text-on-surface">
                                        {{ toInputUnit(a.quantity_after, a.material) }} {{ unitLabel(a.material) }}
                                    </span>

                                    <!-- Chênh lệch badge -->
                                    <span
                                        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-label-small font-bold"
                                        :class="Number(a.change_amount) > 0
                                            ? 'bg-tertiary/10 text-tertiary'
                                            : (Number(a.change_amount) < 0 ? 'bg-error/10 text-error' : 'bg-surface-container-high text-on-surface-variant')">
                                        {{ Number(a.change_amount) > 0 ? '+' : '' }}{{ toInputUnit(a.change_amount,
                                            a.material) }} {{ unitLabel(a.material) }}
                                    </span>
                                </div>

                                <!-- Meta: lý do + người thực hiện + ghi chú -->
                                <div class="flex items-center gap-3 mt-2 flex-wrap">
                                    <span
                                        class="inline-flex items-center gap-1 text-label-small text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[14px]">{{ reasonIcons[a.reason] ||
                                            'info' }}</span>
                                        {{ reasonLabels[a.reason] || a.reason }}
                                    </span>
                                    <span class="w-1 h-1 rounded-full bg-outline-variant flex-shrink-0"></span>
                                    <span
                                        class="inline-flex items-center gap-1 text-label-small text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[14px]">person</span>
                                        {{ userDisplayName(a.user) }}
                                    </span>
                                    <template v-if="a.note">
                                        <span class="w-1 h-1 rounded-full bg-outline-variant flex-shrink-0"></span>
                                        <span
                                            class="text-label-small text-on-surface-variant/70 italic truncate max-w-[200px]"
                                            :title="a.note">
                                            "{{ a.note }}"
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Phải: Thời gian -->
                        <div class="text-right flex-shrink-0">
                            <p class="text-label-small text-on-surface-variant whitespace-nowrap">
                                {{ formatDate(a.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phân trang -->
            <div v-if="adjustments.links?.length > 3" class="flex items-center justify-center gap-1 pb-2">
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