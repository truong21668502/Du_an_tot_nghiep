<script setup>
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
    lowStockAlerts: { type: Array, default: () => [] },
})

const flash = computed(() => usePage().props.flash ?? {})
const searchQuery = ref('')

const sortedMaterials = computed(() =>
    [...props.materials].sort((a, b) => a.id - b.id)
)

const filteredMaterials = computed(() => {
    if (!searchQuery.value.trim()) return sortedMaterials.value
    const q = searchQuery.value.trim().toLowerCase()
    return sortedMaterials.value.filter(m => m.material_name?.toLowerCase().includes(q))
})

const expiredCount = computed(() => props.materials.filter(m => isExpired(m)).length)
const expiringSoonCount = computed(() => props.materials.filter(m => !isExpired(m) && isExpiringSoon(m)).length)
const outOfStockCount = computed(() => props.materials.filter(m => Number(m.quantity_in_stock) <= 0).length)

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
    return Number(val).toLocaleString('vi-VN') + '₫'
}

function formatDate(val) {
    if (!val) return '—'
    return new Date(val).toLocaleDateString('vi-VN')
}

function stockStatus(m) {
    const qty = Number(m.quantity_in_stock)
    const min = Number(m.min_stock ?? 0)
    if (qty <= 0) return {
        label: 'Hết hàng',
        icon: 'cancel',
        classes: 'bg-error text-white',
        bar: 'bg-error',
    }
    if (min > 0 && qty <= min) return {
        label: 'Sắp hết',
        icon: 'warning',
        classes: 'bg-amber-400 text-amber-950',
        bar: 'bg-amber-400',
    }
    return {
        label: 'Còn hàng',
        icon: 'check_circle',
        classes: 'bg-emerald-500 text-white',
        bar: 'bg-emerald-500',
    }
}

function rowAccentClass(m) {
    if (Number(m.quantity_in_stock) <= 0) return 'border-l-4 border-l-error bg-error/5'
    if (Number(m.min_stock) > 0 && Number(m.quantity_in_stock) <= Number(m.min_stock)) return 'border-l-4 border-l-amber-400 bg-amber-50/40'
    if (isExpired(m)) return 'border-l-4 border-l-error bg-error/5'
    if (isExpiringSoon(m)) return 'border-l-4 border-l-amber-400 bg-amber-50/40'
    return 'border-l-4 border-l-transparent'
}

function isExpiringSoon(m) {
    if (!m.expiry_date) return false
    const diff = (new Date(m.expiry_date) - new Date()) / (1000 * 60 * 60 * 24)
    return diff >= 0 && diff <= 7
}

function isExpired(m) {
    if (!m.expiry_date) return false
    return new Date(m.expiry_date) < new Date()
}

function goImport(materialId) {
    router.visit(route('admin.kho.nhap.create'), {
        data: { prefill_material: materialId },
    })
}
</script>

<template>
    <AdminLayout title="Kho Nguyên Liệu">
        <div class="space-y-6 font-sans">

            <!-- Flash -->
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

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-on-surface">Kho Nguyên Liệu</h1>
                    <p class="text-body-medium text-on-surface-variant mt-1">
                        Đang hiển thị {{ filteredMaterials.length }}/{{ materials.length }} nguyên liệu
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
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-surface rounded-2xl border border-outline-variant/20 px-5 py-4 flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-[26px]">inventory_2</span>
                    <div>
                        <p class="text-label-small text-on-surface-variant font-bold uppercase tracking-wide">Tổng
                            nguyên liệu</p>
                        <p class="text-2xl font-bold text-on-surface font-mono">{{ materials.length }}</p>
                    </div>
                </div>
                <div class="bg-surface rounded-2xl border px-5 py-4 flex items-center gap-3"
                    :class="outOfStockCount > 0 ? 'border-error/30' : 'border-outline-variant/20'">
                    <span class="material-symbols-outlined text-[26px]"
                        :class="outOfStockCount > 0 ? 'text-error' : 'text-on-surface-variant/40'">cancel</span>
                    <div>
                        <p class="text-label-small font-bold uppercase tracking-wide"
                            :class="outOfStockCount > 0 ? 'text-error' : 'text-on-surface-variant'">Hết hàng</p>
                        <p class="text-2xl font-bold font-mono"
                            :class="outOfStockCount > 0 ? 'text-error' : 'text-on-surface'">{{ outOfStockCount }}</p>
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
                <div class="bg-surface rounded-2xl border px-5 py-4 flex items-center gap-3"
                    :class="(expiredCount + expiringSoonCount) > 0 ? 'border-amber-300' : 'border-outline-variant/20'">
                    <span class="material-symbols-outlined text-[26px]"
                        :class="expiredCount > 0 ? 'text-error' : (expiringSoonCount > 0 ? 'text-amber-500' : 'text-on-surface-variant/40')">schedule</span>
                    <div>
                        <p class="text-label-small font-bold uppercase tracking-wide"
                            :class="expiredCount > 0 ? 'text-error' : (expiringSoonCount > 0 ? 'text-amber-600' : 'text-on-surface-variant')">
                            Hết hạn / sắp hết
                        </p>
                        <p class="text-2xl font-bold font-mono"
                            :class="expiredCount > 0 ? 'text-error' : (expiringSoonCount > 0 ? 'text-amber-600' : 'text-on-surface')">
                            {{ expiredCount }} / {{ expiringSoonCount }}
                        </p>
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

            <!-- Tìm kiếm -->
            <div class="relative">
                <span
                    class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="searchQuery" type="text" placeholder="Tìm nguyên liệu theo tên..."
                    class="w-full pl-12 pr-10 py-3 bg-surface-container-low border border-outline-variant/20 rounded-2xl text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all" />
                <button v-if="searchQuery" @click="searchQuery = ''"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Bảng -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
                    <table class="w-full border-collapse text-left">
                        <thead class="sticky top-0 z-10">
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
                            <tr v-if="filteredMaterials.length === 0">
                                <td colspan="8" class="py-16 text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-[40px] block mb-2 text-outline">inventory_2</span>
                                    <p class="mb-1">{{ searchQuery ? `Không tìm thấy nguyên liệu nào khớp với
                                        "${searchQuery}".` : 'Chưa có nguyên liệu nào.' }}</p>
                                    <button v-if="searchQuery" @click="searchQuery = ''"
                                        class="text-primary font-bold hover:underline">Xoá bộ lọc tìm kiếm</button>
                                </td>
                            </tr>
                            <tr v-for="m in filteredMaterials" :key="m.id"
                                class="hover:bg-surface-container-low/50 transition-colors" :class="rowAccentClass(m)">

                                <td class="px-4 py-4 text-center text-on-surface-variant text-label-small font-mono">
                                    {{ m.id }}
                                </td>

                                <td class="px-4 py-4">
                                    <p class="font-bold text-on-surface leading-snug">{{ m.material_name }}</p>
                                    <p class="text-label-small text-on-surface-variant/60 font-mono mt-0.5">
                                        1 {{ m.input_unit }} = {{ Number(m.exchange_rate).toLocaleString('vi-VN') }}
                                        {{ m.base_unit }}
                                    </p>
                                </td>

                                <!-- Mức tồn kho: số + thanh trực quan -->
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

                                <!-- Trạng thái -->
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-label-small font-bold shadow-sm whitespace-nowrap"
                                        :class="stockStatus(m).classes">
                                        <span class="material-symbols-outlined text-[15px] leading-none">
                                            {{ stockStatus(m).icon }}
                                        </span>
                                        {{ stockStatus(m).label }}
                                    </span>
                                </td>

                                <!-- Hạn SD -->
                                <td class="px-4 py-4 text-center hidden md:table-cell">
                                    <span v-if="m.expiry_date" class="text-label-small font-mono"
                                        :class="isExpired(m) ? 'text-error font-bold' : (isExpiringSoon(m) ? 'text-amber-600 font-bold' : 'text-on-surface-variant')">
                                        {{ formatDate(m.expiry_date) }}
                                        <span v-if="isExpired(m)" class="block text-[10px]">Đã hết hạn</span>
                                        <span v-else-if="isExpiringSoon(m)" class="block text-[10px]">Sắp hết hạn</span>
                                    </span>
                                    <span v-else class="text-on-surface-variant/40 text-label-small">—</span>
                                </td>

                                <!-- Nhà cung cấp -->
                                <td class="px-4 py-4 hidden xl:table-cell text-on-surface-variant text-label-medium">
                                    {{ m.supplier || '—' }}
                                </td>

                                <!-- Giá -->
                                <td
                                    class="px-4 py-4 text-right hidden md:table-cell font-mono text-label-medium text-on-surface-variant">
                                    {{ formatPrice(m.price) }}
                                </td>

                                <!-- Hành động -->
                                <td class="px-4 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button @click="goImport(m.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary hover:bg-primary/20 rounded-full text-label-small font-bold transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">add</span>
                                            Nhập
                                        </button>
                                        <Link :href="route('admin.kho.dieu-chinh.create')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-surface-container-high hover:bg-surface-container-highest text-on-surface-variant rounded-full text-label-small font-bold transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">edit_note</span>
                                            Chi tiết
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                    <span class="inline-flex items-center gap-1.5 text-label-small text-on-surface-variant">
                        <span class="w-3 h-1.5 rounded-full bg-on-surface/30"></span>
                        Vạch xám trên thanh = ngưỡng Min
                    </span>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>