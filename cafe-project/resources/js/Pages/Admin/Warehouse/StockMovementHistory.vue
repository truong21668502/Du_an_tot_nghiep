<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    movements: { type: Object, required: true },
    materials: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const materialId = ref(props.filters.material_id ?? '')
const movementType = ref(props.filters.movement_type ?? '')
const fromDate = ref(props.filters.from_date ?? '')
const toDate = ref(props.filters.to_date ?? '')

const typeMeta = {
    import: { label: 'Nhập kho', icon: 'arrow_downward', classes: 'bg-emerald-500 text-white' },
    export: { label: 'Xuất kho', icon: 'arrow_upward', classes: 'bg-error text-white' },
    adjust: { label: 'Điều chỉnh', icon: 'tune', classes: 'bg-amber-400 text-amber-950' },
}

const referenceLabel = {
    order: 'Đơn hàng',
    import_receipt: 'Phiếu nhập',
    stock_adjustment: 'Phiếu kiểm kê',
}

function applyFilters() {
    router.get(route('admin.kho.chuyen-dong.index'), {
        material_id: materialId.value || undefined,
        movement_type: movementType.value || undefined,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    materialId.value = ''
    movementType.value = ''
    fromDate.value = ''
    toDate.value = ''
    applyFilters()
}

function formatDateTime(val) {
    return new Date(val).toLocaleString('vi-VN')
}

function formatQty(m) {
    const val = Number(m.quantity_change)
    const sign = val > 0 ? '+' : ''
    return `${sign}${val.toLocaleString('vi-VN')} ${m.material?.base_unit ?? ''}`
}

const hasActiveFilters = computed(() =>
    materialId.value || movementType.value || fromDate.value || toDate.value
)
</script>

<template>
    <AdminLayout title="Lịch Sử Chuyển Động Kho">
        <div class="space-y-6 font-sans">

            <div class="flex items-center gap-3">
                <Link :href="route('admin.kho.index')"
                    class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại kho
                </Link>
            </div>

            <div>
                <h1 class="text-2xl font-bold text-on-surface">Lịch Sử Chuyển Động Kho</h1>
                <p class="text-body-medium text-on-surface-variant mt-1">
                    Toàn bộ nhập / xuất / điều chỉnh tồn kho, có thể lọc theo nguyên liệu và thời gian
                </p>
            </div>

            <!-- Bộ lọc -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Nguyên liệu</label>
                        <select v-model="materialId" @change="applyFilters"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface outline-none text-body-medium focus:ring-2 focus:ring-primary/20 transition-all">
                            <option value="">Tất cả</option>
                            <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.material_name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Loại chuyển động</label>
                        <select v-model="movementType" @change="applyFilters"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface outline-none text-body-medium focus:ring-2 focus:ring-primary/20 transition-all">
                            <option value="">Tất cả</option>
                            <option value="import">Nhập kho</option>
                            <option value="export">Xuất kho</option>
                            <option value="adjust">Điều chỉnh</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Từ ngày</label>
                        <input v-model="fromDate" @change="applyFilters" type="date"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface outline-none text-body-medium focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Đến ngày</label>
                        <input v-model="toDate" @change="applyFilters" type="date"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface outline-none text-body-medium focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                </div>
                <button v-if="hasActiveFilters" @click="resetFilters"
                    class="mt-3 text-label-small font-bold text-primary hover:underline">
                    Xoá tất cả bộ lọc
                </button>
            </div>

            <!-- Bảng -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="bg-surface-container border-b-2 border-outline-variant/20 text-label-large text-on-surface-variant">
                                <th class="px-4 py-3.5 font-bold">Thời gian</th>
                                <th class="px-4 py-3.5 font-bold">Nguyên liệu</th>
                                <th class="px-4 py-3.5 font-bold text-center">Loại</th>
                                <th class="px-4 py-3.5 font-bold text-right">Thay đổi</th>
                                <th class="px-4 py-3.5 font-bold">Tham chiếu</th>
                                <th class="px-4 py-3.5 font-bold">Người thực hiện</th>
                                <th class="px-4 py-3.5 font-bold">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-if="movements.data.length === 0">
                                <td colspan="7" class="py-16 text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-[40px] block mb-2 text-outline">history</span>
                                    Không có chuyển động kho nào khớp với bộ lọc hiện tại.
                                </td>
                            </tr>
                            <tr v-for="m in movements.data" :key="m.id"
                                class="hover:bg-surface-container-low/50 transition-colors">
                                <td
                                    class="px-4 py-3.5 text-label-small font-mono text-on-surface-variant whitespace-nowrap">
                                    {{ formatDateTime(m.moved_at) }}
                                </td>
                                <td class="px-4 py-3.5 font-bold">{{ m.material?.material_name ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-label-small font-bold whitespace-nowrap"
                                        :class="typeMeta[m.movement_type]?.classes">
                                        <span class="material-symbols-outlined text-[14px] leading-none">
                                            {{ typeMeta[m.movement_type]?.icon }}
                                        </span>
                                        {{ typeMeta[m.movement_type]?.label ?? m.movement_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono font-bold"
                                    :class="Number(m.quantity_change) > 0 ? 'text-emerald-600' : 'text-error'">
                                    {{ formatQty(m) }}
                                </td>
                                <td class="px-4 py-3.5 text-label-small text-on-surface-variant">
                                    <span v-if="m.reference_id">
                                        {{ referenceLabel[m.reference_type] ?? m.reference_type }} #{{ m.reference_id }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3.5 text-label-small text-on-surface-variant">
                                    {{ m.moved_by?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-label-small text-on-surface-variant/70 max-w-xs truncate">
                                    {{ m.note || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <div v-if="movements.links.length > 3"
                    class="px-5 py-3.5 border-t border-outline-variant/20 flex items-center justify-center gap-1 flex-wrap">
                    <template v-for="(link, i) in movements.links" :key="i">
                        <Link v-if="link.url" :href="link.url" preserve-scroll preserve-state
                            class="px-3 py-1.5 rounded-full text-label-small font-bold transition-colors"
                            :class="link.active ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-high'"
                            v-html="link.label" />
                        <span v-else class="px-3 py-1.5 text-label-small text-on-surface-variant/30"
                            v-html="link.label" />
                    </template>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>