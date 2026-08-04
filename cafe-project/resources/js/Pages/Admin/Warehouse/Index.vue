<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Object, required: true }, // paginator: { data, links, meta, total, ... }
    lowStockAlerts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const flash = computed(() => usePage().props.flash ?? {})
const searchQuery = ref(props.filters.search ?? '')

// Server đã orderBy('material_name') + paginate, không cần sort/filter client nữa
const materialRows = computed(() => props.materials.data)

let debounceTimer = null
watch(searchQuery, (value) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get(route('admin.kho.index'), { search: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }, 350)
})

function clearSearch() {
    searchQuery.value = ''
}

// Các thẻ thống kê (tổng, hết hàng, hết hạn...) giờ phải tính trên TOÀN BẢNG,
// không thể tính từ materialRows (chỉ là 1 trang) — cần backend trả về qua props riêng.
// Tạm thời dùng lowStockAlerts (đã full) cho thẻ liên quan tồn kho thấp;
// các thẻ khác (tổng/hết hàng/hết hạn) cần thêm 1 prop "stats" từ Controller — xem ghi chú cuối.

function displayStock(m) {
    const rate = Number(m.exchange_rate)
    if (!rate) return Number(m.quantity_in_stock).toLocaleString('vi-VN')
    return Number((Number(m.quantity_in_stock) / rate).toFixed(2)).toLocaleString('vi-VN')
}

function displayMinStock(m) {
    const rate = Number(m.exchange_rate)
    if (!rate) return Number(m.min_stock ?? 0).toLocaleString('vi-VN')
    return Number((Number(m.min_stock ?? 0) / rate).toFixed(2)).toLocaleString('vi-VN')
}

function displayMaxStock(m) {
    if (!m.max_stock) return null
    const rate = Number(m.exchange_rate)
    if (!rate) return Number(m.max_stock).toLocaleString('vi-VN')
    return Number((Number(m.max_stock) / rate).toFixed(2)).toLocaleString('vi-VN')
}

function displayThreshold(m) {
    const hasMin = Number(m.min_stock) > 0
    const max = displayMaxStock(m)
    if (!hasMin && !max) return '—'
    if (hasMin && max) return `${displayMinStock(m)} – ${max}`
    if (hasMin) return `≥ ${displayMinStock(m)}`
    return `≤ ${max}`
}

function formatPrice(val) {
    if (!val) return '—'
    return Number(val).toLocaleString('vi-VN', {
        maximumFractionDigits: 0,
        minimumFractionDigits: 0,
    }) + '₫'
}

function formatDate(val) {
    if (!val) return '—'
    const [year, month, day] = val.split('-')
    return `${day}/${month}/${year}`
}

function stockStatus(m) {
    const qty = Number(m.quantity_in_stock)
    const min = Number(m.min_stock ?? 0)
    const expired = isExpired(m)
    const expiringSoon = isExpiringSoon(m)

    if (qty <= 0) {
        return { label: 'Hết hàng', icon: 'cancel', classes: 'bg-error text-white', pulse: true, severity: 'danger' }
    }
    if (expired) {
        return { label: 'Hết hạn', icon: 'event_busy', classes: 'bg-error text-white', pulse: true, severity: 'danger' }
    }
    if (min > 0 && qty <= min) {
        return { label: 'Sắp hết', icon: 'warning', classes: 'bg-amber-400 text-amber-950', pulse: true, severity: 'warning' }
    }
    if (expiringSoon) {
        return { label: 'Sắp hết hạn', icon: 'schedule', classes: 'bg-amber-400 text-amber-950', pulse: true, severity: 'warning' }
    }
    return { label: 'Còn hàng', icon: 'check_circle', classes: 'bg-emerald-500 text-white', pulse: false, severity: null }
}

function rowAccentClass(m) {
    if (Number(m.quantity_in_stock) <= 0) return 'border-l-4 border-l-error bg-error/5'
    if (Number(m.min_stock) > 0 && Number(m.quantity_in_stock) <= Number(m.min_stock)) return 'border-l-4 border-l-amber-400 bg-amber-50/40'
    if (isExpired(m)) return 'border-l-4 border-l-error bg-error/5'
    if (isExpiringSoon(m)) return 'border-l-4 border-l-amber-400 bg-amber-50/40'
    return 'border-l-4 border-l-transparent'
}

function parseDateLocal(val) {
    const [year, month, day] = val.split('-').map(Number)
    return new Date(year, month - 1, day)
}

function isExpiringSoon(m) {
    if (!m.expiry_date) return false
    const diff = (parseDateLocal(m.expiry_date) - new Date()) / (1000 * 60 * 60 * 24)
    return diff >= 0 && diff <= 7
}

function isExpired(m) {
    if (!m.expiry_date) return false
    return parseDateLocal(m.expiry_date) < new Date()
}

function goImport() {
    router.visit(route('admin.kho.nhap.create'))
}
</script>

<template>
    <AdminLayout title="Kho Nguyên Liệu">
        <div class="space-y-6 font-sans">

            <div v-if="flash.success"
                class="flex items-center gap-3 bg-primary-container text-on-primary-container border border-primary/20 rounded-2xl px-5 py-3.5 text-body-medium shadow-sm">
                <span class="material-symbols-outlined">check_circle</span>
                {{ flash.success }}
            </div>

            <Link :href="route('admin.kho.chuyen-dong.index')"
                class="inline-flex items-center gap-2 px-4 py-2.5 border border-outline-variant text-on-surface-variant hover:bg-surface-container-high rounded-full font-bold transition-colors text-label-medium">
                <span class="material-symbols-outlined text-[18px]">history</span>
                Lịch sử kho
            </Link>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-on-surface text-primary"><span
                            class="material-symbols-outlined text-primary">inventory</span> KHO NGUYÊN LIỆU</h1>
                    <p class="text-body-medium text-on-surface-variant mt-1">
                        Đang hiển thị {{ materialRows.length }}/{{ materials.total }} nguyên liệu
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.kho.dieu-chinh.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 border border-outline-variant text-on-surface-variant hover:bg-surface-container-high rounded-full font-bold transition-colors text-label-medium">
                        <span class="material-symbols-outlined text-[18px]">rule</span>
                        Kiểm kê
                    </Link>
                    <Link :href="route('admin.kho.nhap.create')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full font-bold transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Tạo phiếu nhập
                    </Link>
                </div>
            </div>

            <!-- Dải thống kê nhanh -->
            <!--
                LƯU Ý: materials.total là tổng đúng, nhưng outOfStockCount/expiredCount/expiringSoonCount
                trước đây tính client-side trên toàn bộ danh sách — giờ chỉ có 1 trang nên KHÔNG còn đúng.
                Cần Controller trả thêm 1 prop "stats" (tính bằng query riêng, không phân trang), ví dụ:

                'stats' => [
                    'total' => $totalQuery->count(),
                    'out_of_stock' => (clone $totalQuery)->where('quantity_in_stock', '<=', 0)->count(),
                    'low_stock' => $lowStockAlerts->count(),
                    'expired' => ...,
                    'expiring_soon' => ...,
                ]

                rồi thay các computed cũ bằng props.stats.xxx. Tạm thời mình để lowStockAlerts.length
                (vẫn đúng vì đó là full-table query) và ẩn 2 thẻ hết hàng/hết hạn cho tới khi có stats.
            -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-surface rounded-2xl border border-outline-variant/20 px-5 py-4 flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-[26px]">inventory_2</span>
                    <div>
                        <p class="text-label-small text-on-surface-variant font-bold uppercase tracking-wide">Tổng
                            nguyên liệu</p>
                        <p class="text-2xl font-bold text-on-surface font-mono">{{ materials.total }}</p>
                    </div>
                </div>
                <div class="bg-surface rounded-2xl border px-5 py-4 flex items-center gap-3"
                    :class="lowStockAlerts.length > 0 ? 'border-amber-300' : 'border-outline-variant/20'">
                    <span class="material-symbols-outlined text-[26px]"
                        :class="lowStockAlerts.length > 0 ? 'text-amber-500' : 'text-on-surface-variant/40'">warning</span>
                    <div>
                        <p class="text-label-small font-bold uppercase tracking-wide"
                            :class="lowStockAlerts.length > 0 ? 'text-amber-600' : 'text-on-surface-variant'">Sắp/dưới
                            ngưỡng min</p>
                        <p class="text-2xl font-bold font-mono"
                            :class="lowStockAlerts.length > 0 ? 'text-amber-600' : 'text-on-surface'">{{
                                lowStockAlerts.length }}</p>
                    </div>
                </div>
            </div>

            <!-- Banner cảnh báo -->
            <div v-if="lowStockAlerts.length > 0" class="border border-amber-200 bg-amber-50 rounded-2xl p-5 space-y-4">
                <p class="font-bold text-amber-700 flex items-center gap-2">
                    <span class="material-symbols-outlined">warning</span>
                    {{ lowStockAlerts.length }} nguyên liệu cần nhập thêm sớm
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                    <div v-for="m in lowStockAlerts" :key="m.id"
                        class="bg-white border rounded-xl px-4 py-3 flex items-center justify-between gap-3 shadow-sm"
                        :class="Number(m.quantity_in_stock) <= 0 ? 'border-red-200' : 'border-amber-200'">
                        <div>
                            <p class="font-bold text-label-large text-on-surface">{{ m.material_name }}</p>
                            <p class="text-label-small text-on-surface-variant font-mono mt-0.5">
                                Tồn:
                                <span class="font-bold"
                                    :class="Number(m.quantity_in_stock) <= 0 ? 'text-error' : 'text-amber-600'">
                                    {{ displayStock(m) }}
                                </span>
                                / Min: {{ displayMinStock(m) }} {{ m.input_unit }}
                            </p>
                        </div>
                        <button @click="goImport(m.id)"
                            class="text-label-small font-bold text-primary hover:text-primary/70 whitespace-nowrap flex items-center gap-0.5 transition-colors shrink-0">
                            <span class="material-symbols-outlined text-[14px]">add</span>
                            Nhập
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tìm kiếm: giờ gọi server (debounce 350ms) thay vì lọc client -->
            <div class="relative">
                <span
                    class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="searchQuery" type="text" placeholder="Tìm nguyên liệu theo tên..."
                    class="w-full pl-12 pr-10 py-3 bg-surface-container-low border border-outline-variant/20 rounded-2xl text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all" />
                <button v-if="searchQuery" @click="clearSearch"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Bảng -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="bg-surface-container border-b-2 border-outline-variant/20 text-label-large text-on-surface-variant">
                                <th class="px-4 py-3.5 w-14 text-center font-bold">ID</th>
                                <th class="px-4 py-3.5 font-bold">Nguyên liệu</th>
                                <th class="px-4 py-3.5 font-bold text-right w-28">Tồn kho</th>
                                <th class="px-4 py-3.5 font-bold text-right w-32 hidden lg:table-cell">Ngưỡng (Min–Max)
                                </th>
                                <th class="px-4 py-3.5 font-bold text-center w-28">Trạng thái</th>
                                <th class="px-4 py-3.5 font-bold text-center w-28 hidden md:table-cell">Hạn SD</th>
                                <th class="px-4 py-3.5 font-bold hidden xl:table-cell">Nhà cung cấp</th>
                                <th class="px-4 py-3.5 font-bold text-right w-24 hidden md:table-cell">Giá nhập</th>
                                <th class="px-4 py-3.5 font-bold text-center w-32">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-if="materialRows.length === 0">
                                <td colspan="9" class="py-16 text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-[40px] block mb-2 text-outline">inventory_2</span>
                                    <p class="mb-1">{{ searchQuery ? `Không tìm thấy nguyên liệu nào khớp với
                                        "${searchQuery}".` : 'Chưa có nguyên liệu nào.' }}</p>
                                    <button v-if="searchQuery" @click="clearSearch"
                                        class="text-primary font-bold hover:underline">Xoá bộ lọc tìm kiếm</button>
                                </td>
                            </tr>
                            <tr v-for="m in materialRows" :key="m.id"
                                class="hover:bg-surface-container-low/50 transition-colors" :class="rowAccentClass(m)">

                                <td class="px-4 py-4 text-center text-on-surface-variant text-label-small font-mono">
                                    {{ m.id }}
                                </td>

                                <td class="px-4 py-4">
                                    <p class="font-bold text-on-surface leading-snug">{{ m.material_name }}</p>
                                    <p class="text-label-small text-on-surface-variant/60 font-mono mt-0.5">
                                        1 {{ m.input_unit }} ≈ {{ Number(m.exchange_rate).toLocaleString('vi-VN') }}
                                        {{ m.base_unit }}
                                    </p>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="font-mono font-bold text-label-large"
                                        :class="Number(m.quantity_in_stock) <= 0 ? 'text-error' :
                                            (Number(m.min_stock) > 0 && Number(m.quantity_in_stock) <= Number(m.min_stock) ? 'text-amber-600' : 'text-on-surface')">
                                        {{ displayStock(m) }}
                                    </span>
                                    <span class="block text-label-small text-on-surface-variant/50">{{ m.input_unit
                                        }}</span>
                                </td>

                                <td
                                    class="px-4 py-4 text-right hidden lg:table-cell text-on-surface-variant font-mono text-label-small">
                                    {{ displayThreshold(m) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-label-small font-bold shadow-sm whitespace-nowrap"
                                        :class="[
                                            stockStatus(m).classes,
                                            stockStatus(m).pulse ? `status-blink pulse-${stockStatus(m).severity}` : ''
                                        ]">
                                        <span class="material-symbols-outlined text-[15px] leading-none">
                                            {{ stockStatus(m).icon }}
                                        </span>
                                        {{ stockStatus(m).label }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center hidden md:table-cell">
                                    <div v-if="(m.expiry_breakdown?.length ?? 0) > 1" class="space-y-1">
                                        <div v-for="(part, idx) in m.expiry_breakdown" :key="idx"
                                            class="text-label-small font-mono leading-tight"
                                            :class="isExpired({ expiry_date: part.expiry_date }) ? 'text-error font-bold' : (isExpiringSoon({ expiry_date: part.expiry_date }) ? 'text-amber-600 font-bold' : 'text-on-surface-variant')">
                                            <span v-if="part.label" class="text-[9px] font-bold uppercase mr-1"
                                                :class="part.label === 'đang mở' ? 'text-amber-600' : 'text-primary'">
                                                {{ part.label }}
                                            </span>
                                            {{ formatDate(part.expiry_date) }}
                                        </div>
                                    </div>
                                    <span v-else-if="m.expiry_date" class="text-label-small font-mono"
                                        :class="isExpired(m) ? 'text-error font-bold' : (isExpiringSoon(m) ? 'text-amber-600 font-bold' : 'text-on-surface-variant')">
                                        {{ formatDate(m.expiry_date) }}
                                        <span v-if="isExpired(m)" class="block text-[10px]">Đã hết hạn</span>
                                        <span v-else-if="isExpiringSoon(m)" class="block text-[10px]">Sắp hết hạn</span>
                                    </span>
                                    <span v-else class="text-on-surface-variant/40 text-label-small">—</span>
                                </td>

                                <td class="px-4 py-4 hidden xl:table-cell text-on-surface-variant text-label-medium">
                                    {{ m.supplier || '—' }}
                                </td>

                                <td
                                    class="px-4 py-4 text-right hidden md:table-cell font-mono text-label-medium text-on-surface-variant">
                                    {{ formatPrice(m.price) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button @click="goImport(m.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary hover:bg-primary/20 rounded-full text-label-small font-bold transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">add</span>
                                            Nhập
                                        </button>
                                        <Link :href="route('admin.kho.nguyen-lieu.edit', m.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-surface-container-high hover:bg-surface-container-highest text-on-surface-variant rounded-full text-label-small font-bold transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Sửa
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <div v-if="materials.links?.length > 3"
                    class="px-5 py-3.5 border-t border-outline-variant/20 flex items-center justify-center gap-1 flex-wrap">
                    <template v-for="(link, idx) in materials.links" :key="idx">
                        <Link v-if="link.url" :href="link.url" preserve-scroll preserve-state
                            class="px-3 py-1.5 rounded-full text-label-small font-bold transition-colors"
                            :class="link.active ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-high'"
                            v-html="link.label" />
                        <span v-else class="px-3 py-1.5 rounded-full text-label-small text-on-surface-variant/40"
                            v-html="link.label" />
                    </template>
                </div>

                <!-- Chú thích -->
                <div
                    class="px-5 py-3.5 border-t border-outline-variant/20 bg-surface-container-low/50 flex items-center gap-5 flex-wrap">
                    <p class="text-label-small text-on-surface-variant font-bold">Ghi chú:</p>
                    <span class="inline-flex items-center gap-1.5 text-label-small text-amber-700">
                        <span class="w-2.5 h-2.5 rounded-sm bg-amber-400"></span>
                        Viền vàng = Tồn kho ≤ Min hoặc sắp hết hạn
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-label-small text-error">
                        <span class="w-2.5 h-2.5 rounded-sm bg-error"></span>
                        Viền đỏ = Hết hàng hoặc đã hết hạn
                    </span>
                    <span class="inline-flex items-center gap-1 text-label-small text-tertiary">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        Xanh = Đủ hàng
                    </span>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
@keyframes status-blink {

    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 var(--pulse-color, rgba(239, 68, 68, 0.55));
    }

    50% {
        transform: scale(1.06);
        box-shadow: 0 0 0 6px var(--pulse-color, rgba(239, 68, 68, 0));
    }
}

.status-blink {
    animation: status-blink 1s ease-in-out infinite;
    position: relative;
}

/* Màu ring theo mức độ nghiêm trọng */
.status-blink.pulse-danger {
    --pulse-color: rgba(239, 68, 68, 0.55);
}

.status-blink.pulse-warning {
    --pulse-color: rgba(245, 158, 11, 0.55);
}
</style>