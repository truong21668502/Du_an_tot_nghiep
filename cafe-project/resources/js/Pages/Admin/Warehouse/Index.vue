<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
})

const flash = computed(() => usePage().props.flash ?? {})

// Các nhóm có ID cố định theo seeder ban đầu
const namedGroups = [
    { label: 'Cà Phê', ids: [1, 2] },
    { label: 'Sữa, Kem & Topping Sữa', ids: [3, 4, 5, 6, 7, 8] },
    { label: 'Cốt Trà Nền', ids: [9, 10] },
    { label: 'Syrup & Đường', ids: [11, 12, 13, 14] },
    { label: 'Trái Cây Tươi', ids: [15, 16, 17, 18, 19, 20] },
    { label: 'Topping & Đóng Hộp', ids: [21, 22, 23, 24, 25, 26] },
]

// Toàn bộ ID đã được gán vào 1 nhóm cụ thể
const namedIds = computed(() => namedGroups.flatMap(g => g.ids))

function getMaterialsByGroup(ids) {
    return props.materials.filter(m => ids.includes(m.id))
}

// "Khác" giờ tự động lấy MỌI nguyên liệu chưa thuộc nhóm nào,
// kể cả nguyên liệu mới thêm sau này (id 27, 37, 100... đều tự rơi vào đây)
const otherMaterials = computed(() =>
    props.materials.filter(m => !namedIds.value.includes(m.id))
)

function formatQty(val) {
    return Number(val).toLocaleString('vi-VN')
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

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Kho nguyên liệu</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">{{ materials.length }} nguyên liệu
                        đang quản lý.</p>
                </div>
                <Link :href="route('admin.kho.nhap.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full cursor-pointer transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-md">add</span>Tạo phiếu nhập kho
                </Link>
            </div>

            <!-- Các nhóm có ID cố định -->
            <div v-for="group in namedGroups" :key="group.label"
                class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">

                <div
                    class="px-5 py-4 bg-surface-container-low border-b border-outline-variant/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant text-lg">inventory_2</span>
                    <h2 class="font-sans text-title-medium font-bold text-on-surface uppercase tracking-wide">
                        {{ group.label }}
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-b-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">#</th>
                                <th class="p-4 text-left">Tên nguyên liệu</th>
                                <th class="p-4 text-right w-32">Tồn kho</th>
                                <th class="p-4 text-center w-32">Đơn vị</th>
                                <th class="p-4 text-center w-32 hidden md:table-cell">Đơn vị nhập</th>
                                <th class="p-4 text-right w-48 hidden md:table-cell">Tỉ lệ quy đổi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="m in getMaterialsByGroup(group.ids)" :key="m.id"
                                class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center text-on-surface-variant text-body-small">{{ m.id }}</td>
                                <td class="p-4 text-left">
                                    <div
                                        class="font-bold text-primary hover:text-primary-dark transition-colors cursor-pointer inline-block">
                                        {{ m.material_name }}
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <span class="font-mono font-bold text-on-surface text-label-large">
                                        {{ formatQty(m.quantity_in_stock) }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 bg-surface-container-high rounded-full text-label-medium">
                                        {{ m.base_unit }}
                                    </span>
                                </td>
                                <td class="p-4 text-center hidden md:table-cell">
                                    <span
                                        class="px-3 py-1 bg-surface-container-high rounded-full text-label-medium text-on-surface-variant">
                                        {{ m.input_unit }}
                                    </span>
                                </td>
                                <td
                                    class="p-4 text-right hidden md:table-cell text-body-small text-on-surface-variant font-mono">
                                    1 {{ m.input_unit }} = {{ formatQty(m.exchange_rate) }} {{ m.base_unit }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="getMaterialsByGroup(group.ids).length === 0"
                    class="p-6 text-center text-on-surface-variant font-sans text-body-medium">
                    Chưa có nguyên liệu nào trong nhóm này.
                </div>
            </div>

            <!-- Nhóm "Khác" — tự động chứa mọi nguyên liệu chưa được phân nhóm,
                 bao gồm cả nguyên liệu mới thêm sau này -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">

                <div
                    class="px-5 py-4 bg-surface-container-low border-b border-outline-variant/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant text-lg">inventory_2</span>
                    <h2 class="font-sans text-title-medium font-bold text-on-surface uppercase tracking-wide">
                        Khác
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-b-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">#</th>
                                <th class="p-4 text-left">Tên nguyên liệu</th>
                                <th class="p-4 text-right w-32">Tồn kho</th>
                                <th class="p-4 text-center w-32">Đơn vị</th>
                                <th class="p-4 text-center w-32 hidden md:table-cell">Đơn vị nhập</th>
                                <th class="p-4 text-right w-48 hidden md:table-cell">Tỉ lệ quy đổi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="m in otherMaterials" :key="m.id"
                                class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center text-on-surface-variant text-body-small">{{ m.id }}</td>
                                <td class="p-4 text-left">
                                    <div
                                        class="font-bold text-primary hover:text-primary-dark transition-colors cursor-pointer inline-block">
                                        {{ m.material_name }}
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <span class="font-mono font-bold text-on-surface text-label-large">
                                        {{ formatQty(m.quantity_in_stock) }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 bg-surface-container-high rounded-full text-label-medium">
                                        {{ m.base_unit }}
                                    </span>
                                </td>
                                <td class="p-4 text-center hidden md:table-cell">
                                    <span
                                        class="px-3 py-1 bg-surface-container-high rounded-full text-label-medium text-on-surface-variant">
                                        {{ m.input_unit }}
                                    </span>
                                </td>
                                <td
                                    class="p-4 text-right hidden md:table-cell text-body-small text-on-surface-variant font-mono">
                                    1 {{ m.input_unit }} = {{ formatQty(m.exchange_rate) }} {{ m.base_unit }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="otherMaterials.length === 0"
                    class="p-6 text-center text-on-surface-variant font-sans text-body-medium">
                    Chưa có nguyên liệu nào trong nhóm này.
                </div>
            </div>

        </div>
    </AdminLayout>
</template>