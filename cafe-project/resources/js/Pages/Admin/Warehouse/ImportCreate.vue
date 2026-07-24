<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
})

const materialsList = ref([...props.materials])

const page = usePage()
const prefillId = Number(page.props.ziggy?.query?.prefill_material)



const form = useForm({
    supplier_name: '',
    note: '',
    items: [],
})

function getOtherSelectedIds(currentIndex) {
    return form.items
        .filter((item, idx) => idx !== currentIndex && item.material_id !== '')
        .map(item => Number(item.material_id))
}

function addItem() {
    form.items.push({
        material_id: '',
        quantity: '',
        unit_price: '',
        expiry_date: '',
    })
}

function stockInInputUnit(material) {
    const rate = Number(material.exchange_rate)
    if (!rate) return Number(material.quantity_in_stock)
    return Number((Number(material.quantity_in_stock) / rate).toFixed(2))
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

const grandTotal = computed(() =>
    form.items.reduce((sum, item) => sum + lineTotal(item), 0)
)

function stockAfter(item) {
    const mat = getMaterial(item.material_id)
    if (!mat || !item.quantity) return null
    const rate = Number(mat.exchange_rate) || 1
    const addedInBaseUnit = parseFloat(item.quantity) * rate
    const totalInBaseUnit = Number(mat.quantity_in_stock) + addedInBaseUnit
    return Number((totalInBaseUnit / rate).toFixed(2))   // quy đổi lại về input_unit để hiển thị
}

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function submit() {
    form.post(route('admin.kho.nhap.store'))
}

// ====== Xử lý chọn "+ Thêm nguyên liệu mới" trong dropdown ======
const addMaterialTargetIndex = ref(null) // dòng nào đang yêu cầu thêm nguyên liệu mới

function onMaterialSelectChange(index, event) {
    const value = event.target.value
    if (value === '__new__') {
        // Reset lại select về rỗng, mở modal thêm nguyên liệu
        form.items[index].material_id = ''
        openAddMaterialModal(index)
    }
}

// ====== Modal thêm nguyên liệu mới ======
const showAddMaterialModal = ref(false)
const savingMaterial = ref(false)
const materialErrors = ref({})

const newMaterialForm = reactive({
    material_name: '',
    base_unit: '',
    input_unit: '',
    exchange_rate: '',
    quantity_in_stock: '',
})

function openAddMaterialModal(index) {
    addMaterialTargetIndex.value = index
    newMaterialForm.material_name = ''
    newMaterialForm.base_unit = ''
    newMaterialForm.input_unit = ''
    newMaterialForm.exchange_rate = ''
    newMaterialForm.quantity_in_stock = ''
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

        // Thêm vào danh sách local để dropdown các dòng đều thấy ngay
        materialsList.value.push(newMaterial)

        // Tự động chọn nguyên liệu mới vào dòng đã yêu cầu
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
    <AdminLayout title="TẠO PHIẾU NHẬP KHO">
        <div class="p-6 max-w-6xl mx-auto space-y-6 font-sans">

            <!-- Breadcrumb + Header -->
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.kho.index')"
                        class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors duration-200">
                        <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại kho
                    </Link>
                    <span class="text-outline-variant">/</span>
                    <span class="text-label-large text-on-surface-variant">Phiếu nhập kho</span>
                </div>

                <div class="flex items-center gap-3.5">
                    <div
                        class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center shadow-md shadow-primary/20 shrink-0">
                        <span class="material-symbols-outlined text-on-primary text-[24px]">local_shipping</span>
                    </div>
                    <div>
                        <h1 class="text-headline-md font-bold text-on-surface text-2xl leading-tight tracking-tight">
                            Tạo phiếu nhập kho
                        </h1>
                        <p class="text-body-small text-on-surface-variant mt-0.5">
                            Ghi nhận nguyên liệu nhập vào kho từ nhà cung cấp
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-surface w-full rounded-3xl border border-outline-variant/15 shadow-sm p-6 space-y-6">

                <!-- Bước 1 -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-6 h-6 rounded-full bg-primary text-on-primary text-[12px] font-bold flex items-center justify-center shrink-0">1</span>
                        <h4 class="text-label-large text-on-surface font-bold uppercase tracking-wider">
                            Thông tin phiếu nhập
                        </h4>
                    </div>

                    <div
                        class="bg-surface-container-low p-5 rounded-2xl border border-outline-variant/15 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold">Nhà cung cấp *</label>
                            <input v-model="form.supplier_name" type="text"
                                placeholder="VD: Anh Hùng Coffee, Vinamilk..."
                                class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium" />
                            <span v-if="form.errors.supplier_name"
                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.supplier_name
                                }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold">Ghi chú</label>
                            <input v-model="form.note" type="text" placeholder="Ghi chú thêm (tuỳ chọn)"
                                class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium" />
                        </div>
                    </div>
                </div>

                <!-- Bước 2 -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-6 h-6 rounded-full bg-primary text-on-primary text-[12px] font-bold flex items-center justify-center shrink-0">2</span>
                            <h4 class="text-label-large text-on-surface font-bold uppercase tracking-wider">
                                Danh sách nguyên liệu nhập
                            </h4>
                            <span v-if="form.items.length"
                                class="text-body-small text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded-full font-mono">
                                {{ form.items.length }}
                            </span>
                        </div>
                        <button type="button" @click="addItem"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-primary-container text-on-primary-container hover:bg-primary-container/80 rounded-full font-bold text-label-medium transition-colors duration-200 cursor-pointer shrink-0">
                            <span class="material-symbols-outlined text-sm">add</span> Thêm dòng
                        </button>
                    </div>

                    <div v-if="form.items.length === 0"
                        class="flex flex-col items-center justify-center gap-2 text-center py-14 border-2 border-dashed border-outline-variant/30 rounded-2xl text-on-surface-variant bg-surface-container-low/40">
                        <span class="material-symbols-outlined text-3xl text-outline-variant">inventory_2</span>
                        <p class="text-body-medium">Chưa có dòng nào trong phiếu nhập.</p>
                        <button type="button" @click="addItem"
                            class="text-primary font-bold text-body-medium hover:underline cursor-pointer">
                            + Thêm dòng đầu tiên
                        </button>
                    </div>

                    <span v-if="form.errors.items" class="text-body-small text-error flex items-center gap-0.5">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.items }}
                    </span>

                    <TransitionGroup tag="div" class="space-y-4" enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        leave-active-class="transition duration-150 ease-in absolute" leave-to-class="opacity-0">
                        <div v-for="(item, index) in form.items" :key="index"
                            class="relative bg-surface rounded-2xl border border-outline-variant/15 hover:border-primary/25 hover:shadow-md shadow-sm transition-all duration-200 overflow-hidden">

                            <div class="flex flex-col lg:flex-row">

                                <!-- Cột trái: thông tin nguyên liệu + input -->
                                <div class="flex-1 p-5 space-y-4">

                                    <div class="flex items-start gap-3">
                                        <span
                                            class="w-6 h-6 rounded-full bg-surface-container-high text-on-surface-variant text-[11px] font-bold flex items-center justify-center shrink-0 mt-1">
                                            {{ index + 1 }}
                                        </span>

                                        <div class="flex-1 min-w-0 space-y-1.5">
                                            <label class="text-label-medium text-on-surface-variant font-bold">Nguyên
                                                liệu</label>
                                            <select v-model="item.material_id"
                                                @change="onMaterialSelectChange(index, $event)"
                                                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 cursor-pointer">
                                                <option value="">-- Chọn nguyên liệu --</option>
                                                <option v-for="m in materialsList" :key="m.id" :value="m.id"
                                                    :disabled="getOtherSelectedIds(index).includes(m.id)">
                                                    {{ m.material_name }}{{ getOtherSelectedIds(index).includes(m.id) ?
                                                        ' (đã chọn)' : '' }}
                                                </option>
                                                <option value="__new__" class="text-primary font-bold">
                                                    + Thêm nguyên liệu mới...
                                                </option>
                                            </select>

                                            <div v-if="getMaterial(item.material_id)"
                                                class="flex flex-wrap items-center gap-x-4 gap-y-1 bg-primary/5 border border-primary/15 rounded-lg px-3 py-2 mt-1.5">
                                                <p
                                                    class="text-body-small text-on-surface-variant flex items-center gap-1">
                                                    <span
                                                        class="material-symbols-outlined text-primary text-[15px]">inventory</span>
                                                    Tồn hiện tại:
                                                    <span class="font-mono font-bold text-on-surface">{{
                                                        formatNum(stockInInputUnit(getMaterial(item.material_id))) }} {{
                                                            getMaterial(item.material_id).input_unit }}</span>
                                                </p>
                                                <p v-if="stockAfter(item)"
                                                    class="text-body-small text-tertiary font-bold flex items-center gap-1">
                                                    <span
                                                        class="material-symbols-outlined text-[15px]">trending_up</span>
                                                    Sau nhập:
                                                    <span class="font-mono">{{ formatNum(stockAfter(item)) }} {{
                                                        getMaterial(item.material_id).input_unit }}</span>
                                                </p>
                                            </div>

                                            <span v-if="form.errors[`items.${index}.material_id`]"
                                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                                <span class="material-symbols-outlined text-sm">error</span>{{
                                                    form.errors[`items.${index}.material_id`] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3 pl-9">
                                        <div class="flex flex-col gap-1.5">
                                            <label
                                                class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1">
                                                Số lượng
                                                <span v-if="getMaterial(item.material_id)"
                                                    class="text-primary bg-primary/10 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                                    {{ getMaterial(item.material_id).input_unit }}
                                                </span>
                                            </label>
                                            <input v-model="item.quantity" type="number" min="0.01" step="0.01"
                                                placeholder="0"
                                                class="px-3.5 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium font-mono" />
                                            <span v-if="form.errors[`items.${index}.quantity`]"
                                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                                <span class="material-symbols-outlined text-sm">error</span>
                                                {{ form.errors[`items.${index}.quantity`] }}
                                            </span>
                                        </div>

                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-label-medium text-on-surface-variant font-bold">Đơn giá
                                                (₫)</label>
                                            <input v-model="item.unit_price" type="number" min="0" step="1000"
                                                placeholder="0"
                                                class="px-3.5 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium font-mono" />
                                            <span v-if="form.errors[`items.${index}.unit_price`]"
                                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                                <span class="material-symbols-outlined text-sm">error</span>
                                                {{ form.errors[`items.${index}.unit_price`] }}
                                            </span>
                                        </div>

                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-label-medium text-on-surface-variant font-bold">Hạn
                                                SD</label>
                                            <input v-model="item.expiry_date" type="date"
                                                class="px-3.5 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200 text-body-medium" />
                                            <span v-if="form.errors[`items.${index}.expiry_date`]"
                                                class="text-body-small text-error flex items-center gap-0.5 mt-1">
                                                <span class="material-symbols-outlined text-sm">error</span>
                                                {{ form.errors[`items.${index}.expiry_date`] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cột phải: Thành tiền — tách thành khối riêng, đầy đủ thông tin -->
                                <div
                                    class="lg:w-[220px] shrink-0 bg-surface-container-low/70 lg:border-l border-t lg:border-t-0 border-outline-variant/15 p-5 flex lg:flex-col items-center lg:items-stretch justify-between gap-3">
                                    <div class="flex-1 lg:flex-none">
                                        <p
                                            class="text-label-small text-on-surface-variant font-semibold uppercase tracking-wide">
                                            Thành tiền
                                        </p>
                                        <p
                                            class="text-title-large font-bold text-primary font-mono leading-tight mt-1 break-all">
                                            {{ formatNum(lineTotal(item)) }}₫
                                        </p>
                                        <p v-if="item.quantity && item.unit_price"
                                            class="text-label-small text-on-surface-variant/60 font-mono mt-1">
                                            {{ formatNum(item.quantity) }} × {{ formatNum(item.unit_price) }}₫
                                        </p>
                                    </div>
                                    <button type="button" @click="removeItem(index)" title="Xoá dòng này"
                                        class="inline-flex items-center justify-center gap-1.5 lg:w-full p-2 lg:px-3 lg:py-2 text-on-surface-variant hover:text-error hover:bg-error-container/30 rounded-full lg:rounded-xl transition-colors duration-200 cursor-pointer shrink-0">
                                        <span class="material-symbols-outlined text-[19px]">delete</span>
                                        <span class="hidden lg:inline text-label-medium font-bold">Xoá dòng</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>

            </div>
        </div>

        <!-- Thanh tổng tiền dính đáy -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-4"
            leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0 translate-y-4">
            <div v-if="form.items.length > 0"
                class="sticky bottom-0 w-full z-40 mt-6 border-t border-outline-variant/20 bg-surface/95 backdrop-blur-md shadow-[0_-4px_16px_rgba(0,0,0,0.06)] overflow-hidden">
                <div
                    class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-2 min-w-0">
                        <p class="text-label-medium text-on-surface-variant font-bold shrink-0">Tổng chi phí phiếu nhập
                        </p>
                        <p
                            class="text-xl sm:text-[26px] leading-tight sm:leading-none font-bold text-primary font-mono truncate">
                            {{ formatNum(grandTotal) }}₫
                        </p>
                    </div>
                    <button type="button" @click="submit" :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 sm:px-8 py-2.5 sm:py-3 bg-primary text-on-primary rounded-full shadow-sm shadow-primary/20 hover:shadow-lg hover:shadow-primary/25 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 font-bold disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-sm shrink-0 whitespace-nowrap">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined">save</span>
                        {{ form.processing ? 'Đang lưu phiếu...' : 'Lưu phiếu nhập' }}
                    </button>
                </div>
            </div>
        </Transition>

        <Teleport to="body">
            <div v-if="showAddMaterialModal" @click.self="closeAddMaterialModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4 animate-fade-in font-sans">

                <div
                    class="bg-surface w-full max-w-md rounded-3xl border border-outline-variant/20 shadow-xl overflow-hidden flex flex-col">

                    <div
                        class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/20 bg-surface-container-low">
                        <h2 class="font-bold text-headline-small text-on-surface flex items-center gap-3">
                            <span
                                class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-[20px]">add_circle</span>
                            </span>
                            Thêm nguyên liệu mới
                        </h2>
                        <button @click="closeAddMaterialModal"
                            class="p-2 text-on-surface-variant hover:bg-surface-container-high hover:text-error rounded-full transition-colors duration-200 flex items-center justify-center">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 flex-1 overflow-y-auto">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-medium text-on-surface-variant font-bold">Tên nguyên liệu</label>
                            <input v-model="newMaterialForm.material_name" type="text"
                                placeholder="VD: Sữa tươi, Trà đen..."
                                class="w-full border border-outline-variant/60 bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
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
                                    class="w-full border border-outline-variant/60 bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
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
                                    class="w-full border border-outline-variant/60 bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
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
                                class="w-full border border-outline-variant/60 bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 font-mono" />
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
                                class="w-full border border-outline-variant/60 bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 font-mono" />
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-outline-variant/20 flex justify-end gap-3 bg-surface">
                        <button @click="closeAddMaterialModal"
                            class="px-6 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold rounded-full transition-colors duration-200 font-sans text-label-large">
                            Huỷ bỏ
                        </button>
                        <button @click="submitNewMaterial" :disabled="savingMaterial"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded-full hover:bg-primary/90 transition-colors duration-200 font-bold text-label-large disabled:opacity-50 disabled:cursor-not-allowed shadow-md">
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