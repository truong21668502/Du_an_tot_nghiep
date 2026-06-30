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

const form = useForm({
    supplier_name: props.receipt.supplier_name || '',
    note: props.receipt.note || '',
    items: props.receipt.details.map(d => ({
        material_id: d.material_id,
        quantity: d.quantity,
        unit_price: d.unit_price,
    })),
})

function getOtherSelectedIds(currentIndex) {
    return form.items
        .filter((item, idx) => idx !== currentIndex && item.material_id !== '')
        .map(item => Number(item.material_id))
}

function addItem() {
    form.items.push({ material_id: '', quantity: '', unit_price: '' })
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
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <div class="flex items-center gap-3">
                <Link :href="route('admin.kho.nhap.index')"
                    class="text-sm text-gray-400 hover:text-gray-600 transition">
                    ← Quay lại lịch sử
                </Link>
                <span class="text-gray-200">/</span>
                <h1 class="text-xl font-bold text-gray-800">Sửa Phiếu Nhập #{{ receipt.id }}</h1>
            </div>

            <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl p-3">
                ⚠️ Khi lưu, hệ thống sẽ hoàn lại tồn kho theo phiếu cũ rồi áp lại theo dữ liệu mới.
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nhà cung cấp</label>
                        <input v-model="form.supplier_name" type="text"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        <p v-if="form.errors.supplier_name" class="text-xs text-red-500 mt-1">{{
                            form.errors.supplier_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <input v-model="form.note" type="text"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                    </div>
                </div>

                <hr class="border-gray-100" />

                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-700">Danh sách nguyên liệu nhập</h2>
                        <button type="button" @click="addItem"
                            class="text-sm bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium px-3 py-1.5 rounded-lg transition">
                            + Thêm dòng
                        </button>
                    </div>

                    <p v-if="form.errors.items" class="text-sm text-red-500 mb-3">{{ form.errors.items }}</p>

                    <div class="space-y-3">
                        <div v-for="(item, index) in form.items" :key="index"
                            class="bg-gray-50 rounded-xl p-4 grid grid-cols-12 gap-3 items-start">

                            <div class="col-span-12 md:col-span-5">
                                <label class="text-xs text-gray-500 mb-1 block">Nguyên liệu</label>
                                <select v-model="item.material_id" @change="onMaterialSelectChange(index, $event)"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                    <option value="">-- Chọn nguyên liệu --</option>
                                    <option v-for="m in materialsList" :key="m.id" :value="m.id"
                                        :disabled="getOtherSelectedIds(index).includes(m.id)">
                                        {{ m.material_name }}{{ getOtherSelectedIds(index).includes(m.id) ? ' (đã chọn)'
                                        : '' }}
                                    </option>
                                    <option value="__new__" class="text-indigo-600 font-medium">+ Thêm nguyên liệu
                                        mới...</option>
                                </select>

                                <div v-if="getMaterial(item.material_id)"
                                    class="mt-1.5 text-xs text-gray-400 space-y-0.5">
                                    <p>
                                        Nhập theo: <strong class="text-gray-600">{{
                                            getMaterial(item.material_id).input_unit }}</strong>
                                        · Tồn (sau khi hoàn lại phiếu cũ): <span class="font-mono">{{
                                            formatNum(getBaseline(item.material_id)) }} {{
                                            getMaterial(item.material_id).base_unit }}</span>
                                    </p>
                                    <p v-if="stockAfter(item)" class="text-indigo-500">
                                        → Sau khi lưu: <span class="font-mono font-semibold">{{
                                            formatNum(stockAfter(item)) }} {{ getMaterial(item.material_id).base_unit
                                            }}</span>
                                    </p>
                                </div>

                                <p v-if="form.errors[`items.${index}.material_id`]" class="text-xs text-red-500 mt-1">
                                    {{ form.errors[`items.${index}.material_id`] }}
                                </p>
                            </div>

                            <div class="col-span-5 md:col-span-3">
                                <label class="text-xs text-gray-500 mb-1 block">
                                    Số lượng
                                    <span v-if="getMaterial(item.material_id)" class="text-indigo-400">({{
                                        getMaterial(item.material_id).input_unit }})</span>
                                </label>
                                <input v-model="item.quantity" type="number" min="0.01" step="0.01"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                                <p v-if="form.errors[`items.${index}.quantity`]" class="text-xs text-red-500 mt-1">
                                    {{ form.errors[`items.${index}.quantity`] }}
                                </p>
                            </div>

                            <div class="col-span-5 md:col-span-3">
                                <label class="text-xs text-gray-500 mb-1 block">Đơn giá (₫)</label>
                                <input v-model="item.unit_price" type="number" min="0" step="1000"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                                <p v-if="form.errors[`items.${index}.unit_price`]" class="text-xs text-red-500 mt-1">
                                    {{ form.errors[`items.${index}.unit_price`] }}
                                </p>
                            </div>

                            <div class="col-span-2 md:col-span-1 flex flex-col items-end justify-between h-full pt-5">
                                <p class="text-sm font-bold text-indigo-600 font-mono whitespace-nowrap">{{
                                    formatNum(lineTotal(item)) }}₫</p>
                                <button type="button" @click="removeItem(index)"
                                    class="text-xs text-red-400 hover:text-red-600 mt-2 transition">Xoá</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="form.items.length > 0"
                    class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Tổng chi phí phiếu nhập</p>
                        <p class="text-2xl font-bold text-indigo-600 font-mono">{{ formatNum(grandTotal) }}₫</p>
                    </div>
                    <button type="button" @click="submit" :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold px-8 py-3 rounded-xl transition">
                        {{ form.processing ? 'Đang lưu...' : '💾 Lưu thay đổi' }}
                    </button>
                </div>

            </div>
        </div>

        <!-- Modal thêm nguyên liệu mới (giống ImportCreate.vue) -->
        <Teleport to="body">
            <div v-if="showAddMaterialModal" @click.self="closeAddMaterialModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-800">Thêm nguyên liệu mới</h2>
                        <button @click="closeAddMaterialModal" class="text-gray-400 hover:text-gray-600 transition">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên nguyên liệu</label>
                            <input v-model="newMaterialForm.material_name" type="text"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                            <p v-if="materialErrors.material_name" class="text-xs text-red-500 mt-1">{{
                                materialErrors.material_name[0] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn vị nhập</label>
                                <input v-model="newMaterialForm.input_unit" type="text"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn vị tồn (gốc)</label>
                                <input v-model="newMaterialForm.base_unit" type="text"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ lệ quy đổi</label>
                            <input v-model="newMaterialForm.exchange_rate" type="number" min="0.000001" step="0.01"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tồn kho ban đầu</label>
                            <input v-model="newMaterialForm.quantity_in_stock" type="number" min="0" step="0.01"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
                        <button @click="closeAddMaterialModal"
                            class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-600 font-medium px-4 py-2 rounded-xl transition">Huỷ</button>
                        <button @click="submitNewMaterial" :disabled="savingMaterial"
                            class="text-sm bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-xl transition">
                            {{ savingMaterial ? 'Đang lưu...' : 'Lưu nguyên liệu' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>