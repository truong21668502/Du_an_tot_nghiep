<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
})

const flash = computed(() => usePage().props.flash ?? {})

// Nhóm nguyên liệu theo danh mục dựa vào ID (theo cấu trúc seeder)
const groups = [
    { label: 'Cà Phê', ids: [1, 2] },
    { label: 'Sữa, Kem & Topping Sữa', ids: [3, 4, 5, 6, 7, 8] },
    { label: 'Cốt Trà Nền', ids: [9, 10] },
    { label: 'Syrup & Đường', ids: [11, 12, 13, 14] },
    { label: 'Trái Cây Tươi', ids: [15, 16, 17, 18, 19, 20] },
    { label: 'Topping & Đóng Hộp', ids: [21, 22, 23, 24, 25, 26] },
    { label: 'Khác', ids: [27, 28, 29, 30, 31, 32, 33, 34, 35, 36] },
]

function getMaterialsByGroup(ids) {
    return props.materials.filter(m => ids.includes(m.id))
}

function formatQty(val) {
    return Number(val).toLocaleString('vi-VN')
}
</script>

<template>
    <AdminLayout title="Kho Nguyên Liệu">
        <div class="p-6 space-y-6">

            <!-- Flash success -->
            <div v-if="flash.success"
                class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
                ✅ {{ flash.success }}
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Kho Nguyên Liệu</h1>
                    <p class="text-sm text-gray-400 mt-0.5">{{ materials.length }} nguyên liệu đang quản lý</p>
                </div>
                <Link :href="route('admin.kho.nhap.create')"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition">
                    + Tạo phiếu nhập kho
                </Link>
            </div>

            <!-- Bảng theo nhóm -->
            <div v-for="group in groups" :key="group.label"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">
                        {{ group.label }}
                    </h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="px-5 py-2.5 text-left w-8">#</th>
                            <th class="px-5 py-2.5 text-left">Tên nguyên liệu</th>
                            <th class="px-5 py-2.5 text-right">Tồn kho</th>
                            <th class="px-5 py-2.5 text-center w-24">Đơn vị</th>
                            <th class="px-5 py-2.5 text-center w-28">Đơn vị nhập</th>
                            <th class="px-5 py-2.5 text-right w-32">Tỉ lệ quy đổi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="m in getMaterialsByGroup(group.ids)" :key="m.id" class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3 text-gray-300 text-xs">{{ m.id }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ m.material_name }}</td>
                            <td class="px-5 py-3 text-right font-mono font-semibold text-gray-700">
                                {{ formatQty(m.quantity_in_stock) }}
                            </td>
                            <td class="px-5 py-3 text-center text-gray-500">{{ m.base_unit }}</td>
                            <td class="px-5 py-3 text-center text-gray-400 text-xs">{{ m.input_unit }}</td>
                            <td class="px-5 py-3 text-right text-gray-400 text-xs font-mono">
                                1 {{ m.input_unit }} = {{ formatQty(m.exchange_rate) }} {{ m.base_unit }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AdminLayout>
</template>