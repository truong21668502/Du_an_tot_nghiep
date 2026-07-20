<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
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
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl">Kho nguyên liệu</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">{{ filteredMaterials.length }} / {{
                        materials.length }} nguyên liệu đang quản lý.</p>
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

            <!-- 1 bảng duy nhất, không nhóm theo danh mục -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">

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
                            <tr v-for="m in filteredMaterials" :key="m.id"
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

                <div v-if="filteredMaterials.length === 0"
                    class="p-12 text-center text-on-surface-variant font-sans text-body-medium">
                    {{ searchQuery ? 'Không tìm thấy nguyên liệu phù hợp.' : 'Chưa có nguyên liệu nào trong kho.' }}
                </div>
            </div>

        </div>
    </AdminLayout>
</template>