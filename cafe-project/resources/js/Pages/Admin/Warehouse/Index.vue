<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
    lowStockAlerts: { type: Array, default: () => [] },
})

const flash = computed(() => usePage().props.flash ?? {})

const searchQuery = ref('')

const filteredMaterials = computed(() => {
    if (!searchQuery.value.trim()) return props.materials
    const q = searchQuery.value.trim().toLowerCase()
    return props.materials.filter(m =>
        m.material_name?.toLowerCase().includes(q)
    )
})

function formatQty(val) {
    return Number(val).toLocaleString('vi-VN')
}

/**
 * Chuyển đổi tồn kho từ base_unit → input_unit để hiển thị
 * VD: 20000 ml / 1000 (exchange_rate) = 20 Lít
 *     500 g / 500 = 1 Kg
 */
function displayStock(m) {
    const exchangeRate = Number(m.exchange_rate)
    if (!exchangeRate || exchangeRate === 0) return formatQty(m.quantity_in_stock)
    const converted = Number(m.quantity_in_stock) / exchangeRate
    // Nếu số thập phân quá dài thì làm tròn 2 chữ số, ngược lại hiển thị nguyên
    return Number(converted.toFixed(2)).toLocaleString('vi-VN')
}

/**
 * Chuyển đổi min_stock từ base_unit → input_unit để hiển thị
 */
function displayMinStock(m) {
    const exchangeRate = Number(m.exchange_rate)
    if (!exchangeRate || exchangeRate === 0) return formatQty(m.min_stock)
    const converted = Number(m.min_stock) / exchangeRate
    return Number(converted.toFixed(2)).toLocaleString('vi-VN')
}

function stockStatus(m) {
    if (Number(m.quantity_in_stock) <= 0) {
        return { label: 'Hết hàng', class: 'bg-red-100 text-red-600' }
    }
    if (Number(m.min_stock) > 0 && Number(m.quantity_in_stock) <= Number(m.min_stock)) {
        return { label: 'Sắp hết', class: 'bg-amber-100 text-amber-600' }
    }
    return { label: 'Đủ hàng', class: 'bg-green-50 text-green-600' }
}
</script>

<template>
    <AdminLayout title="Kho Nguyên Liệu">
        <div class="space-y-6 relative">

            <div v-if="flash.success"
                class="flex items-center gap-3 bg-primary-container text-on-primary-container border border-primary/20 rounded-2xl px-5 py-3 font-sans text-body-medium shadow-sm">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                {{ flash.success }}
            </div>

            <!-- Banner cảnh báo tồn kho thấp -->
            <div v-if="lowStockAlerts.length > 0" class="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-3">
                <div class="flex items-center gap-2 text-amber-700 font-bold font-sans text-body-large">
                    <span class="material-symbols-outlined">warning</span>
                    {{ lowStockAlerts.length }} nguyên liệu cần đặt hàng lại
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    <div v-for="m in lowStockAlerts" :key="m.id"
                        class="flex items-center justify-between bg-white border rounded-xl px-4 py-2.5 shadow-sm"
                        :class="Number(m.quantity_in_stock) <= 0 ? 'border-red-200' : 'border-amber-200'">
                        <div>
                            <p class="font-sans text-label-large font-bold text-on-surface">{{ m.material_name }}</p>
                            <p class="font-sans text-label-small text-on-surface-variant font-mono">
                                Tồn:
                                <span
                                    :class="Number(m.quantity_in_stock) <= 0 ? 'text-red-600 font-bold' : 'text-amber-600 font-bold'">
                                    {{ displayStock(m) }}
                                </span>
                                / Tối thiểu: {{ displayMinStock(m) }} {{ m.input_unit }}
                            </p>
                        </div>
                        <span class="material-symbols-outlined text-[20px] flex-shrink-0"
                            :class="Number(m.quantity_in_stock) <= 0 ? 'text-red-500' : 'text-amber-500'">
                            {{ Number(m.quantity_in_stock) <= 0 ? 'remove_shopping_cart' : 'production_quantity_limits'
                                }} </span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl">Kho nguyên liệu</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">
                        {{ filteredMaterials.length }} / {{ materials.length }} nguyên liệu đang quản lý.
                        <span v-if="lowStockAlerts.length > 0" class="text-amber-600 font-bold">
                            · {{ lowStockAlerts.length }} cần chú ý
                        </span>
                    </p>
                </div>
                <Link :href="route('admin.kho.nhap.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full cursor-pointer transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-md">add</span>Tạo phiếu nhập kho
                </Link>
            </div>

            <!-- Ô tìm kiếm -->
            <div class="relative">
                <span
                    class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-xl">search</span>
                <input v-model="searchQuery" type="text" placeholder="Tìm nguyên liệu theo tên..."
                    class="w-full pl-12 pr-4 py-3 bg-surface-container-low border border-outline-variant/20 rounded-2xl font-sans text-body-medium text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all" />
                <button v-if="searchQuery" @click="searchQuery = ''"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-b-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">#</th>
                                <th class="p-4 text-left">Tên nguyên liệu</th>
                                <th class="p-4 text-right w-40">Tồn kho</th>
                                <!-- <th class="p-4 text-right w-36 hidden md:table-cell">Tối thiểu</th>
                                <th class="p-4 text-center w-32 hidden md:table-cell">Đơn vị nhập</th> -->
                                <th class="p-4 text-right w-48 hidden md:table-cell">Tỉ lệ quy đổi</th>
                                <!-- <th class="p-4 text-center w-28">Trạng thái</th> -->
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="m in filteredMaterials" :key="m.id"
                                class="hover:bg-surface-container-low/50 transition-colors"
                                :class="Number(m.quantity_in_stock) <= 0 ? 'bg-red-50/30' :
                                    (Number(m.min_stock) > 0 && Number(m.quantity_in_stock) <= Number(m.min_stock) ? 'bg-amber-50/30' : '')">
                                <td class="p-4 text-center text-on-surface-variant text-body-small">{{ m.id }}</td>
                                <td class="p-4 text-left">
                                    <div
                                        class="font-bold text-primary hover:text-primary-dark transition-colors cursor-pointer inline-block">
                                        {{ m.material_name }}
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <!-- Hiển thị theo input_unit (kg, lít...) thay vì base_unit (ml, g...) -->
                                    <span class="font-mono font-bold text-label-large"
                                        :class="Number(m.quantity_in_stock) <= 0 ? 'text-red-600' :
                                            (Number(m.min_stock) > 0 && Number(m.quantity_in_stock) <= Number(m.min_stock) ? 'text-amber-600' : 'text-on-surface')">
                                        {{ displayStock(m) }}
                                    </span>
                                    <span class="text-label-small text-on-surface-variant ml-1">{{ m.input_unit
                                        }}</span>
                                </td>
                                <!-- <td class="p-4 text-right hidden md:table-cell">
                                    <span class="font-mono text-on-surface-variant text-body-small">
                                        {{ Number(m.min_stock) > 0 ? `${displayMinStock(m)} ${m.input_unit}` : '—' }}
                                    </span>
                                </td>
                                <td class="p-4 text-center hidden md:table-cell">
                                    <span
                                        class="px-3 py-1 bg-surface-container-high rounded-full text-label-medium text-on-surface-variant">
                                        {{ m.input_unit }}
                                    </span>
                                </td> -->
                                <td
                                    class="p-4 text-right hidden md:table-cell text-body-small text-on-surface-variant font-mono">
                                    1 {{ m.input_unit }} = {{ formatQty(m.exchange_rate) }} {{ m.base_unit }}
                                </td>
                                <!-- <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold"
                                        :class="stockStatus(m).class">
                                        {{ stockStatus(m).label }}
                                    </span>
                                </td> -->
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredMaterials.length === 0"
                    class="p-12 text-center text-on-surface-variant font-sans text-body-medium">
                    {{ searchQuery ? 'Không tìm thấy nguyên liệu phù hợp.' : 'Chưa có nguyên liệu nào trong kho.' }}
                </div>
            </div>

        </div>
    </AdminLayout>
</template>