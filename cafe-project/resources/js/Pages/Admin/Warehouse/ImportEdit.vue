<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    receipt: { type: Object, required: true },
    materials: { type: Array, default: () => [] },
})

const materialsList = ref([...props.materials])

// Tính tồn kho "trước khi phiếu này từng tác động" để preview đúng khi sửa
const baselineStock = {}
materialsList.value.forEach(m => {
    baselineStock[m.id] = Number(m.quantity_in_stock)
})
props.receipt.details.forEach(d => {
    if (baselineStock[d.material_id] !== undefined) {
        baselineStock[d.material_id] -= Number(d.stock_change)
    }
})

function getBaseline(materialId) {
    return baselineStock[materialId] ?? getMaterial(materialId)?.quantity_in_stock ?? 0
}

// Lấy đúng phần "YYYY-MM-DD" từ chuỗi ngày trả về từ backend,
// KHÔNG dùng new Date(...).toISOString() vì sẽ bị lệch theo timezone của trình duyệt/server.
function toDateInputValue(val) {
    if (!val) return ''
    return val.slice(0, 10)
}

const form = useForm({
    supplier_name: props.receipt.supplier_name || '',
    note: props.receipt.note || '',
    items: props.receipt.details.map(d => ({
        material_id: d.material_id,
        quantity: d.quantity,
        unit_price: d.unit_price,
        expiry_date: toDateInputValue(d.expiry_date),
    })),
})

function getOtherSelectedIds(currentIndex) {
    return form.items
        .filter((item, idx) => idx !== currentIndex && item.material_id !== '')
        .map(item => Number(item.material_id))
}

function addItem() {
    form.items.push({ material_id: '', quantity: '', unit_price: '', expiry_date: '', })
}

function removeItem(index) {
    form.items.splice(index, 1)
}

function getMaterial(id) {
    return materialsList.value.find(m => m.id === Number(id)) ?? null
}

function lineTotal(item) {
    return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)
}

const grandTotal = computed(() => form.items.reduce((sum, item) => sum + lineTotal(item), 0))

function stockAfter(item) {
    const mat = getMaterial(item.material_id)
    if (!mat || !item.quantity) return null
    const added = parseFloat(item.quantity) * mat.exchange_rate
    return getBaseline(item.material_id) + added
}

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function submit() {
    form.put(route('admin.kho.nhap.update', props.receipt.id))
}

// ====== Thêm nguyên liệu mới (giống ImportCreate.vue) ======
const addMaterialTargetIndex = ref(null)

function onMaterialSelectChange(index, event) {
    if (event.target.value === '__new__') {
        form.items[index].material_id = ''
        openAddMaterialModal(index)
    }
}

const showAddMaterialModal = ref(false)
const savingMaterial = ref(false)
const materialErrors = ref({})

const newMaterialForm = reactive({
    material_name: '', base_unit: '', input_unit: '', exchange_rate: '', quantity_in_stock: '',
})

function openAddMaterialModal(index) {
    addMaterialTargetIndex.value = index
    Object.assign(newMaterialForm, { material_name: '', base_unit: '', input_unit: '', exchange_rate: '', quantity_in_stock: '' })
    materialErrors.value = {}
    showAddMaterialModal.value = true
}

function closeAddMaterialModal() {
    showAddMaterialModal.value = false
    addMaterialTargetIndex.value = null
}

async function submitNewMaterial() {
    savingMaterial.value = true
    materialErrors.value = {}
    try {
        const res = await axios.post(route('admin.kho.nguyen-lieu.quick-store'), {
            material_name: newMaterialForm.material_name,
            base_unit: newMaterialForm.base_unit,
            input_unit: newMaterialForm.input_unit,
            exchange_rate: newMaterialForm.exchange_rate,
            quantity_in_stock: newMaterialForm.quantity_in_stock || 0,
        })
        const newMaterial = res.data.material
        materialsList.value.push(newMaterial)
        baselineStock[newMaterial.id] = Number(newMaterial.quantity_in_stock)
        if (addMaterialTargetIndex.value !== null) {
            form.items[addMaterialTargetIndex.value].material_id = newMaterial.id
        }
        closeAddMaterialModal()
    } catch (e) {
        if (e.response?.status === 422) {
            materialErrors.value = e.response.data.errors || {}
        } else {
            alert('Có lỗi xảy ra, vui lòng thử lại.')
        }
    } finally {
        savingMaterial.value = false
    }
}
</script>

<template>
    <AdminLayout :title="`Sửa Phiếu Nhập #${receipt.id}`">
        <div class="p-6 max-w-5xl mx-auto space-y-6 font-sans">

            <div class="flex items-center gap-3">
                <Link :href="route('admin.kho.nhap.index')"
                    class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại lịch sử
                </Link>
                <span class="text-outline-variant">/</span>
                <h1 class="text-headline-md font-bold text-on-surface text-primary text-3xl"><span class="material-symbols-outlined text-primary">edit</span> SỬA PHIẾU NHẬP #{{
                    receipt.id }}</h1>
            </div>

            <div
                class="bg-tertiary-container/40 border border-tertiary/30 text-on-tertiary-container text-body-medium rounded-xl p-4 flex items-start gap-2">
                <span class="material-symbols-outlined text-tertiary">warning</span>
                Khi lưu, hệ thống sẽ hoàn lại tồn kho theo phiếu cũ rồi áp lại theo dữ liệu mới.
            </div>

            <div class="bg-surface w-full rounded-2xl border border-outline-variant/20 shadow-sm p-6 space-y-6">

                <div class="bg-surface-container-low p-5 rounded-xl border border-outline-variant/20 space-y-4">
                    <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">
                        1. Thông tin phiếu nhập
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold">Nhà cung cấp *</label>
                            <input v-model="form.supplier_name" type="text"
                                placeholder="VD: Anh Hùng Coffee, Vinamilk..."
                                class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-medium" />
                            <span v-if="form.errors.supplier_name"
                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.supplier_name
                                }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold">Ghi chú</label>
                            <input v-model="form.note" type="text" placeholder="Ghi chú thêm (tuỳ chọn)"
                                class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-medium" />
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-low p-5 rounded-xl border border-outline-variant/20 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">
                            2. Danh sách nguyên liệu nhập
                        </h4>
                        <button type="button" @click="addItem"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-primary-container text-on-primary-container hover:bg-primary-container/80 rounded-full font-bold text-label-medium transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-sm">add</span> Thêm dòng
                        </button>
                    </div>

                    <div v-if="form.items.length === 0"
                        class="text-center py-12 border-2 border-dashed border-outline-variant/30 rounded-xl text-on-surface-variant text-body-medium">
                        Chưa có dòng nào. Nhấn <strong class="text-primary cursor-pointer hover:underline"
                            @click="addItem">+ Thêm dòng</strong> để bắt đầu.
                    </div>

                    <span v-if="form.errors.items" class="text-body-small text-error flex items-center gap-0.5">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.items }}
                    </span>

                    <div class="space-y-4">
                        <div v-for="(item, index) in form.items" :key="index"
                            class="bg-surface rounded-xl border border-outline-variant/20 p-4 grid grid-cols-12 gap-4 items-start shadow-sm transition-all">

                            <div class="col-span-12 md:col-span-5 flex flex-col gap-1.5">
                                <label class="text-label-medium text-on-surface-variant font-bold">Nguyên liệu</label>
                                <select v-model="item.material_id" @change="onMaterialSelectChange(index, $event)"
                                    class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface outline-none text-body-medium focus:ring-2 focus:ring-primary/20">
                                    <option value="">-- Chọn nguyên liệu --</option>
                                    <option v-for="m in materialsList" :key="m.id" :value="m.id"
                                        :disabled="getOtherSelectedIds(index).includes(m.id)">
                                        {{ m.material_name }}{{ getOtherSelectedIds(index).includes(m.id) ? ' (đã chọn)'
                                            : '' }}
                                    </option>
                                    <option value="__new__" class="text-primary font-bold">
                                        + Thêm nguyên liệu mới...
                                    </option>
                                </select>

                                <div v-if="getMaterial(item.material_id)"
                                    class="mt-1 text-body-small text-on-surface-variant space-y-0.5">
                                    <p>
                                        Nhập theo: <strong class="text-on-surface">{{
                                            getMaterial(item.material_id).input_unit }}</strong>
                                        <span class="mx-1">•</span>
                                        Tồn (sau khi hoàn lại phiếu cũ): <span class="font-mono">{{
                                            formatNum(getBaseline(item.material_id)) }} {{
                                                getMaterial(item.material_id).base_unit }}</span>
                                    </p>
                                    <p v-if="stockAfter(item)" class="text-primary flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">arrow_right_alt</span>
                                        Sau khi lưu: <span class="font-mono font-bold">{{ formatNum(stockAfter(item)) }}
                                            {{
                                                getMaterial(item.material_id).base_unit }}</span>
                                    </p>
                                </div>

                                <span v-if="form.errors[`items.${index}.material_id`]"
                                    class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                    <span class="material-symbols-outlined text-sm">error</span>{{
                                        form.errors[`items.${index}.material_id`] }}
                                </span>
                            </div>

                            <div class="col-span-5 md:col-span-3 flex flex-col gap-1.5">
                                <label
                                    class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1">
                                    Số lượng
                                    <span v-if="getMaterial(item.material_id)"
                                        class="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-[10px]">
                                        {{ getMaterial(item.material_id).input_unit }}
                                    </span>
                                </label>
                                <input v-model="item.quantity" type="number" min="0.01" step="0.01" placeholder="0"
                                    class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-medium font-mono" />
                                <span v-if="form.errors[`items.${index}.quantity`]"
                                    class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                    <span class="material-symbols-outlined text-sm">error</span>{{
                                        form.errors[`items.${index}.quantity`] }}
                                </span>
                            </div>

                            <div class="col-span-5 md:col-span-3 flex flex-col gap-1.5">
                                <label class="text-label-medium text-on-surface-variant font-bold">Đơn giá (₫)</label>
                                <input v-model="item.unit_price" type="number" min="0" step="1000" placeholder="0"
                                    class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-medium font-mono" />
                                <span v-if="form.errors[`items.${index}.unit_price`]"
                                    class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                    <span class="material-symbols-outlined text-sm">error</span>{{
                                        form.errors[`items.${index}.unit_price`] }}
                                </span>
                            </div>

                            <!-- Hạn sử dụng -->
                            <div class="col-span-4 md:col-span-2 flex flex-col gap-1.5">
                                <label class="text-label-medium text-on-surface-variant font-bold">Hạn SD</label>
                                <input v-model="item.expiry_date" type="date"
                                    class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-medium" />
                                <span v-if="form.errors[`items.${index}.expiry_date`]"
                                    class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    {{ form.errors[`items.${index}.expiry_date`] }}
                                </span>
                            </div>

                            <div class="col-span-2 md:col-span-1 flex flex-col items-end justify-between h-full pt-8">
                                <p class="text-label-large font-bold text-primary font-mono whitespace-nowrap">
                                    {{ formatNum(lineTotal(item)) }}₫
                                </p>
                                <button type="button" @click="removeItem(index)" title="Xoá dòng này"
                                    class="p-2 mt-2 text-on-surface-variant hover:text-error hover:bg-error-container/30 rounded-full transition-colors flex items-center justify-center cursor-pointer">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="form.items.length > 0"
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-outline-variant/20">
                    <div>
                        <p class="text-label-medium text-on-surface-variant font-bold mb-1">Tổng chi phí phiếu nhập</p>
                        <p class="text-[28px] leading-none font-bold text-primary font-mono">
                            {{ formatNum(grandTotal) }}₫
                        </p>
                    </div>
                    <button type="button" @click="submit" :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-8 py-3 bg-primary text-on-primary rounded-full shadow-md hover:bg-primary/90 transition-all font-bold disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined">save</span>
                        {{ form.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
                    </button>
                </div>

            </div>
        </div>

        <!-- Modal thêm nguyên liệu mới (giống ImportCreate.vue) -->
        <Teleport to="body">
            <div v-if="showAddMaterialModal" @click.self="closeAddMaterialModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4 animate-fade-in font-sans">

                <div
                    class="bg-surface w-full max-w-md rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden flex flex-col">

                    <div
                        class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/20 bg-surface">
                        <h2 class="font-bold text-headline-small text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">add_circle</span>
                            Thêm nguyên liệu mới
                        </h2>
                        <button @click="closeAddMaterialModal"
                            class="p-2 text-on-surface-variant hover:bg-surface-container-high hover:text-error rounded-full transition-colors flex items-center justify-center">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 flex-1 overflow-y-auto">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold">Tên nguyên liệu</label>
                            <input v-model="newMaterialForm.material_name" type="text"
                                placeholder="VD: Sữa tươi, Trà đen..."
                                class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                            <p v-if="materialErrors.material_name"
                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                <span class="material-symbols-outlined text-sm">error</span>{{
                                    materialErrors.material_name[0] }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-label-medium text-on-surface-variant font-bold">Đơn vị nhập</label>
                                <input v-model="newMaterialForm.input_unit" type="text" placeholder="VD: Kg, Lít, Hộp"
                                    class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                                <p v-if="materialErrors.input_unit"
                                    class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                    <span class="material-symbols-outlined text-sm">error</span>{{
                                        materialErrors.input_unit[0] }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-label-medium text-on-surface-variant font-bold">Đơn vị tồn
                                    (gốc)</label>
                                <input v-model="newMaterialForm.base_unit" type="text" placeholder="VD: ml, g"
                                    class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                                <p v-if="materialErrors.base_unit"
                                    class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                    <span class="material-symbols-outlined text-sm">error</span>{{
                                        materialErrors.base_unit[0] }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold flex flex-col">
                                Tỷ lệ quy đổi
                                <span class="text-body-small text-on-surface-variant/70 font-normal mt-0.5">
                                    (1 {{ newMaterialForm.input_unit || 'đơn vị nhập' }} = ? {{
                                        newMaterialForm.base_unit || 'đơn vị gốc' }})
                                </span>
                            </label>
                            <input v-model="newMaterialForm.exchange_rate" type="number" min="0.000001" step="0.01"
                                placeholder="VD: 1000"
                                class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all font-mono" />
                            <p v-if="materialErrors.exchange_rate"
                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                <span class="material-symbols-outlined text-sm">error</span>{{
                                    materialErrors.exchange_rate[0] }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold">
                                Tồn kho ban đầu <span
                                    class="text-body-small text-on-surface-variant/70 font-normal">(tuỳ chọn, mặc định
                                    0)</span>
                            </label>
                            <input v-model="newMaterialForm.quantity_in_stock" type="number" min="0" step="0.01"
                                placeholder="0"
                                class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all font-mono" />
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-outline-variant/20 flex justify-end gap-3 bg-surface">
                        <button @click="closeAddMaterialModal"
                            class="px-6 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold rounded-full transition-colors font-sans text-label-large">
                            Huỷ bỏ
                        </button>
                        <button @click="submitNewMaterial" :disabled="savingMaterial"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded-full hover:bg-primary/90 transition-colors font-bold text-label-large disabled:opacity-50 disabled:cursor-not-allowed shadow-md">
                            <span v-if="savingMaterial"
                                class="material-symbols-outlined animate-spin text-sm">sync</span>
                            <span v-else class="material-symbols-outlined text-sm">save</span>
                            {{ savingMaterial ? 'Đang lưu...' : 'Lưu nguyên liệu' }}
                        </button>
                    </div>

                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.98);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

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