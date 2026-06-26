<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    materials: { type: Array, default: () => [] },
})

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
    // const materialIds = form.items.map(i => i.material_id)

    // if (new Set(materialIds).size !== materialIds.length) {
    //     alert('Không được nhập trùng nguyên liệu trong cùng một phiếu')
    //     return
    // }
    form.items.push({
        material_id: '',
        quantity: '',
        unit_price: '',
    })
}

function removeItem(index) {
    form.items.splice(index, 1)
}

function getMaterial(id) {
    return props.materials.find(m => m.id === Number(id)) ?? null
}

function lineTotal(item) {
    return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)
}

const grandTotal = computed(() =>
    form.items.reduce((sum, item) => sum + lineTotal(item), 0)
)

// Tính tồn kho sau khi nhập (preview realtime)
function stockAfter(item) {
    const mat = getMaterial(item.material_id)
    if (!mat || !item.quantity) return null
    const added = parseFloat(item.quantity) * mat.exchange_rate
    return Number(mat.quantity_in_stock) + added
}

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function submit() {
    form.post(route('admin.kho.nhap.store'))
}
</script>

<template>
    <AdminLayout title="Tạo Phiếu Nhập Kho">
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link :href="route('admin.kho.index')" class="text-sm text-gray-400 hover:text-gray-600 transition">
                    ← Quay lại kho
                </Link>
                <span class="text-gray-200">/</span>
                <h1 class="text-xl font-bold text-gray-800">Tạo Phiếu Nhập Kho</h1>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">

                <!-- Thông tin phiếu -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nhà cung cấp
                        </label>
                        <input v-model="form.supplier_name" type="text" placeholder="VD: Anh Hùng Coffee, Vinamilk..."
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        <p v-if="form.errors.supplier_name" class="text-xs text-red-500 mt-1">
                            {{ form.errors.supplier_name }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ghi chú
                        </label>
                        <input v-model="form.note" type="text" placeholder="Ghi chú thêm (tuỳ chọn)"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                    </div>
                </div>

                <hr class="border-gray-100" />

                <!-- Danh sách dòng nguyên liệu -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-700">Danh sách nguyên liệu nhập</h2>
                        <button type="button" @click="addItem"
                            class="text-sm bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium px-3 py-1.5 rounded-lg transition">
                            + Thêm dòng
                        </button>
                    </div>

                    <!-- Empty -->
                    <div v-if="form.items.length === 0"
                        class="text-center py-12 border-2 border-dashed border-gray-200 rounded-xl text-gray-400 text-sm">
                        Chưa có dòng nào. Nhấn <strong class="text-indigo-500">+ Thêm dòng</strong> để bắt đầu.
                    </div>

                    <!-- Lỗi items chung -->
                    <p v-if="form.errors.items" class="text-sm text-red-500 mb-3">
                        {{ form.errors.items }}
                    </p>

                    <!-- Các dòng -->
                    <div class="space-y-3">
                        <div v-for="(item, index) in form.items" :key="index"
                            class="bg-gray-50 rounded-xl p-4 grid grid-cols-12 gap-3 items-start">
                            <!-- Chọn nguyên liệu -->
                            <div class="col-span-12 md:col-span-5">
                                <label class="text-xs text-gray-500 mb-1 block">Nguyên liệu</label>
                                <select v-model="item.material_id"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                    <option value="">-- Chọn nguyên liệu --</option>
                                    <option v-for="m in materials" :key="m.id" :value="m.id"
                                        :disabled="getOtherSelectedIds(index).includes(m.id)">
                                        {{ m.material_name }}{{ getOtherSelectedIds(index).includes(m.id) ? ' (đã chọn)'
                                        : '' }}
                                    </option>
                                </select>

                                <!-- Thông tin tồn kho hiện tại + preview sau nhập -->
                                <div v-if="getMaterial(item.material_id)"
                                    class="mt-1.5 text-xs text-gray-400 space-y-0.5">
                                    <p>
                                        Nhập theo: <strong class="text-gray-600">{{
                                            getMaterial(item.material_id).input_unit }}</strong>
                                        · Tồn hiện tại: <span class="font-mono">{{
                                            formatNum(getMaterial(item.material_id).quantity_in_stock) }} {{
                                                getMaterial(item.material_id).base_unit }}</span>
                                    </p>
                                    <p v-if="stockAfter(item)" class="text-indigo-500">
                                        → Sau nhập: <span class="font-mono font-semibold">{{ formatNum(stockAfter(item))
                                            }} {{ getMaterial(item.material_id).base_unit }}</span>
                                    </p>
                                </div>

                                <p v-if="form.errors[`items.${index}.material_id`]" class="text-xs text-red-500 mt-1">
                                    {{ form.errors[`items.${index}.material_id`] }}
                                </p>
                            </div>

                            <!-- Số lượng -->
                            <div class="col-span-5 md:col-span-3">
                                <label class="text-xs text-gray-500 mb-1 block">
                                    Số lượng
                                    <span v-if="getMaterial(item.material_id)" class="text-indigo-400">
                                        ({{ getMaterial(item.material_id).input_unit }})
                                    </span>
                                </label>
                                <input v-model="item.quantity" type="number" min="0.01" step="0.01" placeholder="0"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                                <p v-if="form.errors[`items.${index}.quantity`]" class="text-xs text-red-500 mt-1">
                                    {{ form.errors[`items.${index}.quantity`] }}
                                </p>
                            </div>

                            <!-- Đơn giá -->
                            <div class="col-span-5 md:col-span-3">
                                <label class="text-xs text-gray-500 mb-1 block">Đơn giá (₫)</label>
                                <input v-model="item.unit_price" type="number" min="0" step="1000" placeholder="0"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                                <p v-if="form.errors[`items.${index}.unit_price`]" class="text-xs text-red-500 mt-1">
                                    {{ form.errors[`items.${index}.unit_price`] }}
                                </p>
                            </div>

                            <!-- Thành tiền + Xoá -->
                            <div class="col-span-2 md:col-span-1 flex flex-col items-end justify-between h-full pt-5">
                                <p class="text-sm font-bold text-indigo-600 font-mono whitespace-nowrap">
                                    {{ formatNum(lineTotal(item)) }}₫
                                </p>
                                <button type="button" @click="removeItem(index)"
                                    class="text-xs text-red-400 hover:text-red-600 mt-2 transition">
                                    Xoá
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer: Tổng tiền + Submit -->
                <div v-if="form.items.length > 0"
                    class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Tổng chi phí phiếu nhập</p>
                        <p class="text-2xl font-bold text-indigo-600 font-mono">
                            {{ formatNum(grandTotal) }}₫
                        </p>
                    </div>
                    <button type="button" @click="submit" :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold px-8 py-3 rounded-xl transition">
                        {{ form.processing ? 'Đang lưu...' : '💾 Lưu phiếu nhập' }}
                    </button>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>