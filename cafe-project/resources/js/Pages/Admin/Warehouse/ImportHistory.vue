<script setup>
import { reactive, ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    receipts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
})

const filters = reactive({
    supplier_name: props.filters.supplier_name || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
})

function applyFilters() {
    router.get(route('admin.kho.nhap.index'), filters, {
        preserveState: true,
        replace: true,
    })
}

let debounceTimer = null
function onSupplierInput() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 400)
}

function resetFilters() {
    filters.supplier_name = ''
    filters.from_date = ''
    filters.to_date = ''
    applyFilters()
}

function formatNum(val) {
    return Number(val).toLocaleString('vi-VN')
}

function formatDate(val) {
    return new Date(val).toLocaleString('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

function userDisplayName(user) {
    if (!user) return '—'
    return user.name || user.full_name || user.username || user.email || '—'
}

// Summary
const summary = computed(() => {
    const data = props.receipts.data
    const active = data.filter(r => r.status === 'active').length
    const cancelled = data.filter(r => r.status === 'cancelled').length
    const totalCost = data
        .filter(r => r.status === 'active')
        .reduce((sum, r) => sum + Number(r.total_cost), 0)
    return { active, cancelled, totalCost }
})

const hasActiveFilters = computed(() =>
    filters.supplier_name || filters.from_date || filters.to_date
)

// ====== MODAL ======
const showModal = ref(false)
const loadingDetail = ref(false)
const selectedReceipt = ref(null)
const detailError = ref(null)

async function openDetail(id) {
    showModal.value = true
    loadingDetail.value = true
    detailError.value = null
    selectedReceipt.value = null
    showCancelForm.value = false

    try {
        const res = await axios.get(route('admin.kho.nhap.show', id))
        selectedReceipt.value = res.data.receipt
    } catch (e) {
        detailError.value = 'Không tải được chi tiết phiếu nhập. Vui lòng thử lại.'
    } finally {
        loadingDetail.value = false
    }
}

function closeModal() {
    showModal.value = false
    selectedReceipt.value = null
    showCancelForm.value = false
}

function lineTotal(item) {
    return Number(item.quantity) * Number(item.unit_price)
}

function calculatedTotal(receipt) {
    return receipt.details.reduce((sum, item) => sum + lineTotal(item), 0)
}

// ====== HUỶ PHIẾU ======
const cancelling = ref(false)
const showCancelForm = ref(false)
const cancelReason = ref('')

function openCancelForm() {
    showCancelForm.value = true
    cancelReason.value = ''
}

async function confirmCancel() {
    if (!selectedReceipt.value || cancelling.value) return
    cancelling.value = true
    try {
        await axios.delete(route('admin.kho.nhap.destroy', selectedReceipt.value.id), {
            data: { cancel_reason: cancelReason.value },
        })
        closeModal()
        router.reload({ only: ['receipts'] })
    } catch (e) {
        alert('Có lỗi khi huỷ phiếu, vui lòng thử lại.')
    } finally {
        cancelling.value = false
    }
}
</script>

<template>
    <AdminLayout title="Lịch Sử Nhập Hàng">
        <div class="space-y-5 font-sans">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.kho.index')"
                            class="inline-flex items-center gap-1 text-label-medium text-on-surface-variant hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                            Kho
                        </Link>
                        <span class="text-outline-variant text-label-small">/</span>
                        <span class="text-label-medium text-on-surface-variant">Nhập hàng</span>
                    </div>
                    <h1 class="text-2xl font-bold text-on-surface">Lịch Sử Nhập Hàng</h1>
                </div>
                <Link :href="route('admin.kho.nhap.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full font-bold transition-colors shadow-sm self-start sm:self-auto">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Tạo phiếu nhập
                </Link>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-3">
                <!-- <div class="bg-surface border border-outline-variant/20 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-primary text-[20px]">receipt_long</span>
                    </div>
                    <div>
                        <p class="text-label-small text-on-surface-variant">Trang này</p>
                        <p class="text-title-large font-bold text-on-surface font-mono">{{ receipts.data.length }}</p>
                    </div>
                </div> -->
                <div class="bg-surface border border-outline-variant/20 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-tertiary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-tertiary text-[20px]">check_circle</span>
                    </div>
                    <div>
                        <p class="text-label-small text-on-surface-variant">Hiệu lực</p>
                        <p class="text-title-large font-bold text-tertiary font-mono">{{ summary.active }}</p>
                    </div>
                </div>
                <div class="bg-surface border border-outline-variant/20 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-primary text-[20px]">payments</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-label-small text-on-surface-variant">Tổng chi (hiệu lực)</p>
                        <p class="text-label-large font-bold text-primary font-mono truncate">{{
                            formatNum(summary.totalCost) }}₫</p>
                    </div>
                </div>
            </div>

            <!-- Bộ lọc -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-label-medium font-bold text-on-surface-variant flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">filter_list</span>
                        Lọc kết quả
                    </p>
                    <button v-if="hasActiveFilters" @click="resetFilters"
                        class="text-label-small text-primary hover:text-primary/70 transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">close</span>
                        Xoá bộ lọc
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-small text-on-surface-variant font-bold">Nhà cung cấp</label>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                            <input v-model="filters.supplier_name" @input="onSupplierInput" type="text"
                                placeholder="Tìm theo NCC..."
                                class="w-full border border-outline-variant bg-surface-container-low rounded-xl pl-10 pr-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-small text-on-surface-variant font-bold">Từ ngày</label>
                        <input v-model="filters.from_date" @change="applyFilters" type="date"
                            class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-small text-on-surface-variant font-bold">Đến ngày</label>
                        <input v-model="filters.to_date" @change="applyFilters" type="date"
                            class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                </div>
            </div>

            <!-- Danh sách phiếu nhập dạng card -->
            <div class="space-y-2">

                <div v-if="receipts.data.length === 0"
                    class="bg-surface border border-outline-variant/20 rounded-2xl p-16 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[48px] mb-3 block text-outline">inbox</span>
                    <p class="text-body-large font-medium">Không có phiếu nhập nào.</p>
                    <p class="text-body-medium mt-1">
                        {{ hasActiveFilters ? 'Thử thay đổi bộ lọc để xem kết quả khác.' :
                            'Bắt đầu bằng cách tạo phiếu nhập đầu tiên.'
                        }}
                    </p>
                </div>

                <div v-for="r in receipts.data" :key="r.id"
                    class="bg-surface border border-outline-variant/20 rounded-2xl p-5 transition-all cursor-pointer"
                    :class="r.status === 'cancelled'
                        ? 'opacity-60 hover:opacity-80'
                        : 'hover:border-outline-variant/40 hover:shadow-sm'" @click="openDetail(r.id)">

                    <div class="flex items-start justify-between gap-4">

                        <!-- Trái -->
                        <div class="flex items-start gap-4 min-w-0">

                            <!-- Icon trạng thái -->
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                                :class="r.status === 'cancelled' ? 'bg-surface-container-high' : 'bg-primary/10'">
                                <span class="material-symbols-outlined text-[22px]"
                                    :class="r.status === 'cancelled' ? 'text-on-surface-variant' : 'text-primary'">
                                    {{ r.status === 'cancelled' ? 'cancel' : 'receipt_long' }}
                                </span>
                            </div>

                            <div class="min-w-0 space-y-1.5">
                                <!-- Mã phiếu + NCC -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono font-bold text-on-surface-variant text-label-large">#{{ r.id
                                    }}</span>
                                    <span class="w-1 h-1 rounded-full bg-outline-variant flex-shrink-0"></span>
                                    <span class="font-bold text-on-surface">
                                        {{ r.supplier_name || 'Không có NCC' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-label-small font-bold" :class="r.status === 'cancelled'
                                        ? 'bg-surface-container-high text-on-surface-variant'
                                        : 'bg-tertiary-container/50 text-on-tertiary-container'">
                                        {{ r.status === 'cancelled' ? 'Đã huỷ' : 'Hiệu lực' }}
                                    </span>
                                </div>

                                <!-- Meta -->
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span
                                        class="inline-flex items-center gap-1 text-label-small text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[14px]">schedule</span>
                                        {{ formatDate(r.created_at) }}
                                    </span>
                                    <span class="w-1 h-1 rounded-full bg-outline-variant flex-shrink-0"></span>
                                    <span
                                        class="inline-flex items-center gap-1 text-label-small text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[14px]">person</span>
                                        {{ userDisplayName(r.user) }}
                                    </span>
                                    <span class="w-1 h-1 rounded-full bg-outline-variant flex-shrink-0"></span>
                                    <span
                                        class="inline-flex items-center gap-1 text-label-small text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                        {{ r.details_count }} nguyên liệu
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Phải: Tổng tiền + nút xem -->
                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                            <p class="font-mono font-bold text-primary text-label-large">
                                {{ formatNum(r.total_cost) }}₫
                            </p>
                            <span class="inline-flex items-center gap-1 text-label-small text-primary/70">
                                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                Xem chi tiết
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phân trang -->
            <div v-if="receipts.links?.length > 3" class="flex items-center justify-center gap-1 pb-2">
                <template v-for="(link, idx) in receipts.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" preserve-scroll
                        class="px-3 py-1.5 text-label-medium rounded-lg transition-all"
                        :class="link.active
                            ? 'bg-primary text-on-primary font-bold shadow-sm'
                            : 'bg-surface border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-high'" />
                    <span v-else v-html="link.label"
                        class="px-3 py-1.5 text-label-medium rounded-lg text-on-surface-variant/40 opacity-50 cursor-not-allowed" />
                </template>
            </div>

        </div>

        <!-- ====== MODAL CHI TIẾT ====== -->
        <Teleport to="body">
            <div v-if="showModal" @click.self="closeModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4 animate-fade-in">

                <div
                    class="bg-surface rounded-2xl shadow-xl border border-outline-variant/20 w-full max-w-3xl max-h-[90vh] flex flex-col font-sans overflow-hidden">

                    <!-- Header modal -->
                    <div
                        class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/20 flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-[20px]">receipt_long</span>
                            </div>
                            <div>
                                <p class="text-label-small text-on-surface-variant">Chi tiết phiếu nhập</p>
                                <p v-if="selectedReceipt" class="font-bold text-on-surface">
                                    #{{ selectedReceipt.id }} · {{ selectedReceipt.supplier_name || 'Không có NCC' }}
                                </p>
                            </div>
                        </div>
                        <button @click="closeModal"
                            class="p-2 text-on-surface-variant hover:bg-surface-container-high hover:text-error rounded-full transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <!-- Body modal -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar">

                        <!-- Loading -->
                        <div v-if="loadingDetail"
                            class="py-16 flex flex-col items-center justify-center gap-3 text-on-surface-variant">
                            <span class="material-symbols-outlined animate-spin text-[36px] text-primary">sync</span>
                            <p class="text-body-medium">Đang tải chi tiết...</p>
                        </div>

                        <!-- Lỗi -->
                        <div v-else-if="detailError"
                            class="py-16 flex flex-col items-center justify-center gap-2 text-error">
                            <span class="material-symbols-outlined text-[36px]">error</span>
                            <p class="text-body-medium">{{ detailError }}</p>
                        </div>

                        <div v-else-if="selectedReceipt" class="p-6 space-y-5">

                            <!-- Banner đã huỷ -->
                            <div v-if="selectedReceipt.status === 'cancelled'"
                                class="bg-error/5 border border-error/20 rounded-xl p-4 flex items-start gap-3">
                                <span class="material-symbols-outlined text-error mt-0.5">cancel</span>
                                <div class="space-y-0.5">
                                    <p class="text-body-medium font-bold text-on-surface">Phiếu nhập này đã bị huỷ</p>
                                    <p class="text-body-small text-on-surface-variant">Lý do: {{
                                        selectedReceipt.cancel_reason
                                        || '—' }}</p>
                                    <p class="text-label-small text-on-surface-variant/70">
                                        Huỷ lúc {{ formatDate(selectedReceipt.cancelled_at) }}
                                        <span v-if="selectedReceipt.cancelled_by"> · bởi {{
                                            userDisplayName(selectedReceipt.cancelled_by) }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Thông tin chung -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-surface-container-low rounded-xl p-4 space-y-1">
                                    <p class="text-label-small text-on-surface-variant font-bold">Ngày nhập</p>
                                    <p class="text-body-medium text-on-surface">{{
                                        formatDate(selectedReceipt.created_at) }}</p>
                                </div>
                                <div class="bg-surface-container-low rounded-xl p-4 space-y-1">
                                    <p class="text-label-small text-on-surface-variant font-bold">Nhà cung cấp</p>
                                    <p class="text-body-medium text-on-surface font-bold">{{
                                        selectedReceipt.supplier_name ||
                                        '—' }}</p>
                                </div>
                                <div class="bg-surface-container-low rounded-xl p-4 space-y-1">
                                    <p class="text-label-small text-on-surface-variant font-bold">Người tạo</p>
                                    <p class="text-body-medium text-on-surface">{{ userDisplayName(selectedReceipt.user)
                                    }}</p>
                                </div>
                                <div class="bg-primary/10 rounded-xl p-4 space-y-1">
                                    <p class="text-label-small text-primary font-bold">Tổng chi phí</p>
                                    <p class="text-title-medium font-bold text-primary font-mono">{{
                                        formatNum(selectedReceipt.total_cost) }}₫</p>
                                </div>
                                <div v-if="selectedReceipt.note"
                                    class="col-span-2 md:col-span-4 bg-surface-container-low rounded-xl p-4 space-y-1">
                                    <p class="text-label-small text-on-surface-variant font-bold">Ghi chú</p>
                                    <p class="text-body-medium text-on-surface italic">"{{ selectedReceipt.note }}"</p>
                                </div>
                            </div>

                            <!-- Danh sách nguyên liệu -->
                            <div>
                                <p
                                    class="text-label-medium font-bold text-on-surface-variant uppercase tracking-wider mb-3">
                                    Danh sách nguyên liệu
                                </p>
                                <div class="border border-outline-variant/20 rounded-xl overflow-hidden">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr
                                                class="bg-surface-container border-b border-outline-variant/20 text-label-large text-on-surface-variant">
                                                <th class="px-4 py-3 font-bold">Nguyên liệu</th>
                                                <th class="px-4 py-3 font-bold text-right">SL</th>
                                                <th class="px-4 py-3 font-bold text-center">ĐV</th>
                                                <th class="px-4 py-3 font-medium text-right">Hạn SD</th>

                                                <th class="px-4 py-3 font-bold text-right">Đơn giá</th>
                                                <th class="px-4 py-3 font-bold text-right">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant/10">
                                            <tr v-for="item in selectedReceipt.details" :key="item.id"
                                                class="hover:bg-surface-container-low/50 transition-colors">
                                                <td class="px-4 py-3 font-bold text-on-surface text-body-medium">
                                                    {{ item.material?.material_name || '— (đã xoá)' }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right font-mono text-body-medium text-on-surface-variant">
                                                    {{ formatNum(item.quantity) }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="px-2 py-0.5 bg-surface-container-high rounded-full text-label-small text-on-surface-variant">
                                                        {{ item.material?.input_unit || '—' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right text-gray-500">
                                                    {{ item.expiry_date ? item.expiry_date.slice(0,
                                                        10).split('-').reverse().join('/') : '—'
                                                    }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right font-mono text-body-medium text-on-surface-variant">
                                                    {{ formatNum(item.unit_price) }}₫
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right font-mono font-bold text-primary text-body-medium">
                                                    {{ formatNum(lineTotal(item)) }}₫
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t-2 border-outline-variant/20 bg-surface-container-low">
                                                <td colspan="4"
                                                    class="px-4 py-3 text-right font-bold text-on-surface-variant text-label-medium uppercase">
                                                    Tổng cộng
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right font-bold text-primary font-mono text-title-small">
                                                    {{ formatNum(calculatedTotal(selectedReceipt)) }}₫
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Form xác nhận huỷ -->
                            <div v-if="showCancelForm"
                                class="bg-error/5 border border-error/25 rounded-xl p-5 space-y-3">
                                <div class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-error mt-0.5">warning</span>
                                    <div>
                                        <p class="text-body-medium font-bold text-on-surface">Xác nhận huỷ phiếu nhập
                                            #{{
                                                selectedReceipt.id }}?</p>
                                        <p class="text-body-small text-on-surface-variant mt-0.5">Toàn bộ tồn kho đã
                                            cộng sẽ
                                            được hoàn lại. Không thể tự động hoàn tác.</p>
                                    </div>
                                </div>
                                <input v-model="cancelReason" type="text" placeholder="Lý do huỷ (tuỳ chọn)"
                                    class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-error/20 transition-all" />
                                <div class="flex justify-end gap-2">
                                    <button @click="showCancelForm = false"
                                        class="px-4 py-2 text-label-medium font-bold text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-colors">
                                        Không, giữ lại
                                    </button>
                                    <button @click="confirmCancel" :disabled="cancelling"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-error text-on-error font-bold text-label-medium rounded-xl hover:bg-error/90 disabled:opacity-50 transition-colors">
                                        <span v-if="cancelling"
                                            class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                        {{ cancelling ? 'Đang huỷ...' : 'Xác nhận huỷ phiếu' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer modal -->
                    <div
                        class="px-6 py-4 border-t border-outline-variant/20 flex items-center justify-between flex-shrink-0">
                        <div v-if="selectedReceipt?.status === 'active' && !showCancelForm" class="flex gap-2">
                            <Link :href="route('admin.kho.nhap.edit', selectedReceipt.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary/10 text-primary hover:bg-primary/20 font-bold rounded-full transition-colors text-label-medium">
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                Sửa phiếu
                            </Link>
                            <button @click="openCancelForm"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-error/10 text-error hover:bg-error/20 font-bold rounded-full transition-colors text-label-medium">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                Huỷ phiếu
                            </button>
                        </div>
                        <div v-else></div>
                        <button @click="closeModal"
                            class="px-5 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold rounded-full transition-colors text-label-medium">
                            Đóng
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

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e2e2;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cccccc;
}
</style>