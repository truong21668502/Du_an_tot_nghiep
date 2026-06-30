<!-- StockAdjustmentCreate.vue -->
<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
})

const form = useForm({
    material_id: '',
    actual_quantity: '',
    reason: 'kiem_ke',
    note: '',
})

const reasons = [
    { value: 'kiem_ke', label: 'Kiểm kê định kỳ' },
    { value: 'that_thoat', label: 'Thất thoát' },
    { value: 'het_han', label: 'Hết hạn' },
    { value: 'hu_hong', label: 'Hư hỏng' },
    { value: 'khac', label: 'Khác' },
]

const selectedMaterial = computed(() =>
    props.materials.find(m => m.id === Number(form.material_id)) ?? null
)

const diff = computed(() => {
    if (!selectedMaterial.value || form.actual_quantity === '') return null
    return Number(form.actual_quantity) - Number(selectedMaterial.value.quantity_in_stock)
})

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function submit() {
    form.post(route('admin.kho.dieu-chinh.store'))
}
</script>

<template>
    <AdminLayout title="Điều Chỉnh Tồn Kho">
        <div class="p-6 max-w-2xl mx-auto space-y-6">

            <Link :href="route('admin.kho.dieu-chinh.index')"
                class="text-sm text-gray-400 hover:text-gray-600 transition">
                ← Quay lại lịch sử điều chỉnh
            </Link>

            <h1 class="text-xl font-bold text-gray-800">Điều Chỉnh Tồn Kho (Kiểm Kê)</h1>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nguyên liệu</label>
                    <select v-model="form.material_id"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">-- Chọn nguyên liệu --</option>
                        <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.material_name }}</option>
                    </select>
                    <p v-if="form.errors.material_id" class="text-xs text-red-500 mt-1">{{ form.errors.material_id }}
                    </p>
                </div>

                <div v-if="selectedMaterial" class="bg-gray-50 rounded-xl p-4 text-sm">
                    <p class="text-gray-500">
                        Tồn hệ thống hiện tại:
                        <span class="font-mono font-bold text-gray-700">
                            {{ formatNum(selectedMaterial.quantity_in_stock) }} {{ selectedMaterial.base_unit }}
                        </span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Số lượng thực tế đếm được
                        <span v-if="selectedMaterial" class="text-gray-400">({{ selectedMaterial.base_unit }})</span>
                    </label>
                    <input v-model="form.actual_quantity" type="number" min="0" step="0.01" placeholder="0"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                    <p v-if="form.errors.actual_quantity" class="text-xs text-red-500 mt-1">{{
                        form.errors.actual_quantity }}</p>
                </div>

                <div v-if="diff !== null" class="rounded-xl p-4 text-sm font-medium"
                    :class="diff === 0 ? 'bg-gray-50 text-gray-500' : (diff > 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600')">
                    Chênh lệch:
                    <span class="font-mono font-bold">{{ diff > 0 ? '+' : '' }}{{ formatNum(diff) }} {{
                        selectedMaterial?.base_unit }}</span>
                    <span v-if="diff > 0"> (tồn thực tế nhiều hơn hệ thống)</span>
                    <span v-else-if="diff < 0"> (hao hụt so với hệ thống)</span>
                    <span v-else> (khớp với hệ thống)</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lý do điều chỉnh</label>
                    <select v-model="form.reason"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option v-for="r in reasons" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                    <p v-if="form.errors.reason" class="text-xs text-red-500 mt-1">{{ form.errors.reason }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (tuỳ chọn)</label>
                    <textarea v-model="form.note" rows="3"
                        placeholder="VD: Đếm lại kho ngày 29/06, phát hiện thiếu 2kg..."
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
                </div>

                <div class="flex justify-end pt-2">
                    <button @click="submit" :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold px-6 py-2.5 rounded-xl transition">
                        {{ form.processing ? 'Đang lưu...' : '💾 Lưu điều chỉnh' }}
                    </button>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>