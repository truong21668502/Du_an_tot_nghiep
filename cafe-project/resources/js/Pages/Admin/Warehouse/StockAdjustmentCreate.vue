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
        <div class="max-w-2xl mx-auto space-y-6 font-sans">

            <div class="flex items-center gap-3">
                <Link :href="route('admin.kho.dieu-chinh.index')"
                    class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại lịch sử điều chỉnh
                </Link>
            </div>

            <h1 class="text-headline-md font-bold text-on-surface">Điều Chỉnh Tồn Kho (Kiểm Kê)</h1>

            <div class="bg-surface w-full rounded-2xl border border-outline-variant/20 shadow-sm p-6 space-y-5">

                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Nguyên liệu</label>
                    <select v-model="form.material_id"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface outline-none text-body-medium focus:ring-2 focus:ring-primary/20 transition-all">
                        <option value="">-- Chọn nguyên liệu --</option>
                        <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.material_name }}</option>
                    </select>
                    <span v-if="form.errors.material_id"
                        class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.material_id }}
                    </span>
                </div>

                <div v-if="selectedMaterial"
                    class="bg-surface-container-low border border-outline-variant/20 rounded-xl p-4 text-body-medium">
                    <p class="text-on-surface-variant">
                        Tồn hệ thống hiện tại:
                        <span class="font-mono font-bold text-on-surface">
                            {{ formatNum(selectedMaterial.quantity_in_stock) }} {{ selectedMaterial.base_unit }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1">
                        Số lượng thực tế đếm được
                        <span v-if="selectedMaterial"
                            class="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-[10px]">
                            {{ selectedMaterial.base_unit }}
                        </span>
                    </label>
                    <input v-model="form.actual_quantity" type="number" min="0" step="0.01" placeholder="0"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-medium font-mono" />
                    <span v-if="form.errors.actual_quantity"
                        class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.actual_quantity }}
                    </span>
                </div>

                <div v-if="diff !== null" class="rounded-xl p-4 text-body-medium font-bold flex items-center gap-2"
                    :class="diff === 0 ? 'bg-surface-container-low text-on-surface-variant' : (diff > 0 ? 'bg-tertiary-container/40 text-on-tertiary-container' : 'bg-error-container/30 text-error')">
                    <span class="material-symbols-outlined">
                        {{ diff === 0 ? 'check_circle' : (diff > 0 ? 'trending_up' : 'trending_down') }}
                    </span>
                    <span>
                        Chênh lệch:
                        <span class="font-mono font-bold">{{ diff > 0 ? '+' : '' }}{{ formatNum(diff) }} {{
                            selectedMaterial?.base_unit }}</span>
                        <span v-if="diff > 0"> (tồn thực tế nhiều hơn hệ thống)</span>
                        <span v-else-if="diff < 0"> (hao hụt so với hệ thống)</span>
                        <span v-else> (khớp với hệ thống)</span>
                    </span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Lý do điều chỉnh</label>
                    <select v-model="form.reason"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface outline-none text-body-medium focus:ring-2 focus:ring-primary/20 transition-all">
                        <option v-for="r in reasons" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                    <span v-if="form.errors.reason" class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.reason }}
                    </span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Ghi chú (tuỳ chọn)</label>
                    <textarea v-model="form.note" rows="3"
                        placeholder="VD: Đếm lại kho ngày 29/06, phát hiện thiếu 2kg..."
                        class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-medium"></textarea>
                </div>

                <div class="flex justify-end pt-2 border-t border-outline-variant/20">
                    <button @click="submit" :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded-full shadow-md hover:bg-primary/90 transition-all font-bold disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined">save</span>
                        {{ form.processing ? 'Đang lưu...' : 'Lưu điều chỉnh' }}
                    </button>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>