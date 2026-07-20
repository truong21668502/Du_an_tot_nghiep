<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    receipt: { type: Object, required: true },
})

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function formatDate(val) {
    return new Date(val).toLocaleString('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

function lineTotal(item) {
    return Number(item.quantity) * Number(item.unit_price)
}

const calculatedTotal = computed(() =>
    props.receipt.details.reduce((sum, item) => sum + lineTotal(item), 0)
)
</script>

<template>
    <AdminLayout :title="`Chi Tiết Phiếu Nhập #${receipt.id}`">
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link :href="route('admin.kho.nhap.index')"
                    class="text-sm text-gray-400 hover:text-gray-600 transition">
                    ← Quay lại lịch sử
                </Link>
                <span class="text-gray-200">/</span>
                <h1 class="text-xl font-bold text-gray-800 text-primary text-3xl">Phiếu nhập #{{ receipt.id }}</h1>
            </div>

            <!-- Thông tin chung -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Ngày nhập</p>
                    <p class="text-sm font-medium text-gray-700">{{ formatDate(receipt.created_at) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Nhà cung cấp</p>
                    <p class="text-sm font-medium text-gray-700">{{ receipt.supplier_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Người tạo</p>
                    <p class="text-sm font-medium text-gray-700">{{ receipt.user?.full_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Tổng chi phí</p>
                    <p class="text-sm font-bold text-indigo-600 font-mono">{{ formatNum(receipt.total_cost) }}₫</p>
                </div>
                <div class="md:col-span-4" v-if="receipt.note">
                    <p class="text-xs text-gray-400 mb-1">Ghi chú</p>
                    <p class="text-sm text-gray-600">{{ receipt.note }}</p>
                </div>
            </div>

            <!-- Danh sách dòng nguyên liệu -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-700">Danh sách nguyên liệu đã nhập</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-left">
                            <th class="px-4 py-3 font-medium">Nguyên liệu</th>
                            <th class="px-4 py-3 font-medium text-right">Số lượng</th>
                            <th class="px-4 py-3 font-medium">Đơn vị</th>
                            <th class="px-4 py-3 font-medium text-right">Đơn giá</th>
                            <th class="px-4 py-3 font-medium text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in receipt.details" :key="item.id" class="border-t border-gray-100">
                            <td class="px-4 py-3 text-gray-700 font-medium">
                                {{ item.material?.material_name || '— (nguyên liệu đã bị xoá)' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-600">
                                {{ formatNum(item.quantity) }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ item.material?.input_unit || '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-600">
                                {{ formatNum(item.unit_price) }}₫
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-semibold text-indigo-600">
                                {{ formatNum(lineTotal(item)) }}₫
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-gray-50">
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-600">
                                Tổng cộng
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-indigo-600 font-mono">
                                {{ formatNum(calculatedTotal) }}₫
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </AdminLayout>
</template>