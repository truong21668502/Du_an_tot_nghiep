<script setup>
import { computed, ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
})

const form = useForm({
    material_id: '',
    actual_quantity: '', // người dùng nhập theo input_unit
    reason: 'kiem_ke',
    note: '',
    min_stock: '',
    max_stock: '',
    expiry_date: '',
    import_receipt_detail_id: '',
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

// Tồn kho hiện tại hiển thị theo input_unit
const currentStockDisplay = computed(() => {
    if (!selectedMaterial.value) return null
    const rate = Number(selectedMaterial.value.exchange_rate)
    if (!rate) return Number(selectedMaterial.value.quantity_in_stock)
    return Number((Number(selectedMaterial.value.quantity_in_stock) / rate).toFixed(2))
})

// min_stock hiển thị theo input_unit (để prefill ô min_stock)
const currentMinStockDisplay = computed(() => {
    if (!selectedMaterial.value) return ''
    const rate = Number(selectedMaterial.value.exchange_rate)
    if (!rate) return Number(selectedMaterial.value.min_stock ?? 0)
    return Number((Number(selectedMaterial.value.min_stock ?? 0) / rate).toFixed(2))
})

const currentMaxStockDisplay = computed(() => {
    if (!selectedMaterial.value) return ''
    const rate = Number(selectedMaterial.value.exchange_rate)
    if (!rate) return Number(selectedMaterial.value.max_stock ?? 0)
    return Number((Number(selectedMaterial.value.max_stock ?? 0) / rate).toFixed(2))
})

// Chênh lệch tính theo input_unit để hiển thị cho dễ hiểu
const diff = computed(() => {
    if (!selectedMaterial.value || form.actual_quantity === '') return null
    return Number(form.actual_quantity) - (currentStockDisplay.value ?? 0)
})

// Prefill min_stock khi chọn nguyên liệu
function onMaterialChange() {
    if (selectedMaterial.value) {
        form.min_stock = currentMinStockDisplay.value || ''
        form.max_stock = currentMaxStockDisplay.value || ''
        form.expiry_date = selectedMaterial.value.expiry_date ?? ''
        form.import_receipt_detail_id = selectedMaterial.value.nearest_expiry_detail_id ?? ''
    } else {
        form.min_stock = ''
        form.max_stock = ''
        form.expiry_date = ''
        form.import_receipt_detail_id = ''
    }
    form.actual_quantity = ''
}

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function submit() {
    if (!selectedMaterial.value) return

    const rate = Number(selectedMaterial.value.exchange_rate) || 1
    const actualInBaseUnit = Number(form.actual_quantity) * rate
    const minStockInBaseUnit = form.min_stock !== '' ? Number(form.min_stock) * rate : null
    const maxStockInBaseUnit = form.max_stock !== '' ? Number(form.max_stock) * rate : null

    form.transform(data => ({
        ...data,
        actual_quantity: actualInBaseUnit,
        min_stock: minStockInBaseUnit,
        max_stock: maxStockInBaseUnit,
        expiry_date: data.expiry_date || null,
    })).post(route('admin.kho.dieu-chinh.store'))
}
</script>

<template>
    <AdminLayout title="Điều Chỉnh Tồn Kho">
        <div class="max-w-2xl mx-auto space-y-6 font-sans">

            <div class="flex items-center gap-3">
                <Link :href="route('admin.kho.dieu-chinh.index')"
                    class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors duration-200">
                    <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại lịch sử điều chỉnh
                </Link>
            </div>

            <div class="flex items-center gap-3.5">
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center shadow-md shadow-primary/20 shrink-0">
                    <span class="material-symbols-outlined text-on-primary text-[24px]">inventory_2</span>
                </div>
                <div>
                    <h1 class="text-headline-md font-bold text-on-surface text-2xl leading-tight tracking-tight">
                        Điều chỉnh tồn kho (kiểm kê)
                    </h1>
                    <p class="text-body-small text-on-surface-variant mt-0.5">
                        Cập nhật số lượng thực tế và ngưỡng cảnh báo cho nguyên liệu
                    </p>
                </div>
            </div>

            <div class="bg-surface w-full rounded-2xl border border-outline-variant/15 shadow-sm p-6 space-y-5">

                <!-- Chọn nguyên liệu -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Nguyên liệu</label>
                    <select v-model="form.material_id" @change="onMaterialChange"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 cursor-pointer">
                        <option value="">-- Chọn nguyên liệu --</option>
                        <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.material_name }}</option>
                    </select>
                    <span v-if="form.errors.material_id"
                        class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.material_id }}
                    </span>
                </div>

                <!-- Thông tin tồn kho hiện tại (hiển thị theo input_unit) -->
                <div v-if="selectedMaterial" class="bg-primary/5 border border-primary/15 rounded-xl p-4 space-y-1">
                    <p class="text-body-medium text-on-surface-variant flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-[18px]">database</span>
                        Tồn hệ thống hiện tại:
                        <span class="font-mono font-bold text-on-surface">
                            {{ formatNum(currentStockDisplay) }} {{ selectedMaterial.input_unit }}
                        </span>
                    </p>
                    <p class="text-body-small text-on-surface-variant/60 font-mono pl-[26px]">
                        (= {{ formatNum(selectedMaterial.quantity_in_stock) }} {{ selectedMaterial.base_unit }}
                        trong DB · tỉ lệ: 1 {{ selectedMaterial.input_unit }}
                        = {{ formatNum(selectedMaterial.exchange_rate) }} {{ selectedMaterial.base_unit }})
                    </p>
                </div>

                <!-- Số lượng thực tế (nhập theo input_unit) -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1">
                        Số lượng thực tế đếm được
                        <span v-if="selectedMaterial"
                            class="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-[10px] font-bold">
                            {{ selectedMaterial.input_unit }}
                        </span>
                    </label>
                    <input v-model="form.actual_quantity" type="number" min="0" step="0.01" placeholder="0"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium font-mono" />

                    <!-- Preview quy đổi sang base_unit để người dùng biết DB sẽ lưu gì -->
                    <p v-if="selectedMaterial && form.actual_quantity !== ''"
                        class="text-label-small text-on-surface-variant/60 font-mono">
                        = {{ formatNum(Number(form.actual_quantity) * Number(selectedMaterial.exchange_rate)) }}
                        {{ selectedMaterial.base_unit }} (giá trị lưu vào DB)
                    </p>

                    <span v-if="form.errors.actual_quantity"
                        class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.actual_quantity }}
                    </span>
                </div>

                <!-- Chênh lệch (theo input_unit) -->
                <transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                    <div v-if="diff !== null"
                        class="rounded-xl p-4 text-body-medium font-bold flex items-center gap-2.5"
                        :class="diff === 0
                            ? 'bg-surface-container-low text-on-surface-variant'
                            : (diff > 0 ? 'bg-tertiary-container/40 text-on-tertiary-container' : 'bg-error-container/30 text-error')">
                        <span class="material-symbols-outlined">
                            {{ diff === 0 ? 'check_circle' : (diff > 0 ? 'trending_up' : 'trending_down') }}
                        </span>
                        <span>
                            Chênh lệch:
                            <span class="font-mono font-bold">
                                {{ diff > 0 ? '+' : '' }}{{ formatNum(Number(diff.toFixed(2))) }} {{
                                    selectedMaterial?.input_unit }}
                            </span>
                            <span v-if="diff > 0" class="font-medium"> (thực tế nhiều hơn hệ thống)</span>
                            <span v-else-if="diff < 0" class="font-medium"> (hao hụt so với hệ thống)</span>
                            <span v-else class="font-medium"> (khớp với hệ thống)</span>
                        </span>
                    </div>
                </transition>

                <!-- Ngưỡng tồn kho tối thiểu / tối đa -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label
                            class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1 flex-wrap">
                            Ngưỡng tồn tối thiểu
                            <span v-if="selectedMaterial"
                                class="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                {{ selectedMaterial.input_unit }}
                            </span>
                            <span class="text-label-small text-on-surface-variant font-normal">(tuỳ chọn)</span>
                        </label>
                        <input v-model="form.min_stock" type="number" min="0" step="0.01"
                            :placeholder="selectedMaterial ? `Hiện tại: ${currentMinStockDisplay || 0} ${selectedMaterial.input_unit}` : '0'"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium font-mono" />
                        <p class="text-label-small text-on-surface-variant/60">
                            Để trống = giữ nguyên. Cảnh báo khi tồn ≤ ngưỡng này.
                        </p>
                        <span v-if="form.errors.min_stock"
                            class="text-body-small text-error flex items-center gap-0.5 mt-1">
                            <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.min_stock }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label
                            class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1 flex-wrap">
                            Ngưỡng tồn tối đa
                            <span v-if="selectedMaterial"
                                class="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                {{ selectedMaterial.input_unit }}
                            </span>
                            <span class="text-label-small text-on-surface-variant font-normal">(tuỳ chọn)</span>
                        </label>
                        <input v-model="form.max_stock" type="number" min="0" step="0.01"
                            :placeholder="selectedMaterial ? `Hiện tại: ${currentMaxStockDisplay || 0} ${selectedMaterial.input_unit}` : '0'"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium font-mono" />
                        <p class="text-label-small text-on-surface-variant/60">
                            Để trống = giữ nguyên. Cảnh báo khi nhập vượt mức tồn trữ.
                        </p>
                        <span v-if="form.errors.max_stock"
                            class="text-body-small text-error flex items-center gap-0.5 mt-1">
                            <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.max_stock }}
                        </span>
                    </div>
                </div>

                <!-- Hạn sử dụng -->
                <div v-if="selectedMaterial" class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1">
                        Hạn sử dụng
                        <span class="text-label-small text-on-surface-variant font-normal ml-1">(lô gần hạn nhất, tuỳ
                            chọn)</span>
                    </label>
                    <input v-model="form.expiry_date" type="date" :disabled="!form.import_receipt_detail_id"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium disabled:opacity-50 disabled:cursor-not-allowed" />

                    <p v-if="selectedMaterial.expiry_is_past"
                        class="text-label-small text-error font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">warning</span>
                        Lô này đã hết hạn ({{ selectedMaterial.expiry_date }}).
                    </p>
                    <p v-else-if="!form.import_receipt_detail_id" class="text-label-small text-on-surface-variant/60">
                        Nguyên liệu này chưa có lô nhập kho nào để gắn hạn sử dụng.
                    </p>
                </div>

                <!-- Lý do -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Lý do điều chỉnh</label>
                    <select v-model="form.reason"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 cursor-pointer">
                        <option v-for="r in reasons" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                    <span v-if="form.errors.reason" class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.reason }}
                    </span>
                </div>

                <!-- Ghi chú -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Ghi chú (tuỳ chọn)</label>
                    <textarea v-model="form.note" rows="3"
                        placeholder="VD: Đếm lại kho ngày 29/06, phát hiện thiếu 2kg..."
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium resize-none"></textarea>
                </div>

                <div class="flex justify-end pt-2 border-t border-outline-variant/15">
                    <button @click="submit" :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded-full shadow-sm shadow-primary/20 hover:shadow-lg hover:shadow-primary/25 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 font-bold disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-sm">
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