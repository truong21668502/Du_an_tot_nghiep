<script setup>
import { reactive, ref } from 'vue'
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

// ====== MODAL CHI TIẾT PHIẾU NHẬP ======
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

// Lấy tên người dùng linh hoạt, tránh lỗi nếu chưa biết tên cột chính xác
function userDisplayName(user) {
    if (!user) return '—'
    return user.name || user.full_name || user.username || user.email || '—'
}

// ====== HUỶ PHIẾU NHẬP ======
const cancelling = ref(false)
const showCancelForm = ref(false)
const cancelReason = ref('')

function openCancelForm() {
    console.log('openCancelForm — selectedReceipt hiện tại:', selectedReceipt.value)
    showCancelForm.value = true
    cancelReason.value = ''
}

async function confirmCancel() {

    if (!selectedReceipt.value) {
        console.warn('selectedReceipt là null/undefined, dừng lại')
        return
    }

    cancelling.value = true
    try {
        const url = route('admin.kho.nhap.destroy', selectedReceipt.value.id)

        await axios.delete(url, {
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

const statusLabel = (status) => status === 'cancelled' ? 'Đã huỷ' : 'Đang hiệu lực'
</script>

<template>
    <AdminLayout title="Lịch Sử Nhập Hàng">
        <div class="space-y-6 relative font-sans">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.kho.index')"
                        class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại kho
                    </Link>
                    <span class="text-outline-variant">/</span>
                    <h1 class="text-headline-md font-bold text-on-surface">Lịch Sử Nhập Hàng</h1>
                </div>
                <Link :href="route('admin.kho.nhap.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full font-bold transition-colors shadow-sm cursor-pointer">
                    <span class="material-symbols-outlined text-md">add</span>Tạo phiếu nhập
                </Link>
            </div>

            <div
                class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-5 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Nhà cung cấp</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                        <input v-model="filters.supplier_name" @input="onSupplierInput" type="text"
                            placeholder="Tìm theo NCC..."
                            class="w-full border border-outline-variant bg-surface rounded-xl pl-10 pr-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Từ ngày</label>
                    <input v-model="filters.from_date" @change="applyFilters" type="date"
                        class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Đến ngày</label>
                    <input v-model="filters.to_date" @change="applyFilters" type="date"
                        class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                </div>
                <div class="flex flex-col">
                    <button @click="resetFilters"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface rounded-xl transition-colors font-bold w-full">
                        <span class="material-symbols-outlined text-sm">filter_alt_off</span> Xoá lọc
                    </button>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-container border-b-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-28">Mã phiếu</th>
                                <th class="p-4">Ngày nhập</th>
                                <th class="p-4">Nhà cung cấp</th>
                                <th class="p-4">Người tạo</th>
                                <th class="p-4 text-right">Tổng tiền</th>
                                <th class="px-4 py-3 font-medium text-center">Trạng thái</th>
                                <th class="p-4 text-center w-32">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-if="receipts.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-on-surface-variant font-medium">
                                    Không có phiếu nhập nào.
                                </td>
                            </tr>
                            <tr v-for="r in receipts.data" :key="r.id"
                                class="hover:bg-surface-container-low/50 transition-colors"
                                :class="r.status === 'cancelled' ? 'opacity-60' : ''">
                                <td class="p-4 font-mono font-bold text-on-surface-variant">#{{ r.id }}</td>
                                <td class="p-4">{{ formatDate(r.created_at) }}</td>
                                <td class="p-4 font-bold text-primary hover:text-primary-dark">{{ r.supplier_name || '—'
                                    }}</td>
                                <td class="p-4">{{ userDisplayName(r.user) }}</td>
                                <td class="p-4 text-right font-bold text-primary font-mono text-label-large">
                                    {{ formatNum(r.total_cost) }}₫
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="r.status === 'cancelled' ? 'bg-gray-100 text-gray-500' : 'bg-green-50 text-green-600'">
                                        {{ statusLabel(r.status) }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <button @click="openDetail(r.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary/20 rounded-full text-label-medium font-bold transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span> Xem
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="receipts.links?.length > 3" class="flex items-center justify-center gap-1 mt-6 mb-3 font-sans">
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

        <Teleport to="body">
            <div v-if="showModal" @click.self="closeModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4 animate-fade-in">

                <div
                    class="bg-surface rounded-2xl shadow-xl border border-outline-variant/20 w-full max-w-3xl max-h-[90vh] flex flex-col font-sans overflow-hidden">

                    <div
                        class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/20 flex-shrink-0 bg-surface">
                        <h2 class="font-bold text-headline-small text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">receipt_long</span>
                            Chi tiết phiếu nhập <span v-if="selectedReceipt" class="text-primary ml-1">#{{
                                selectedReceipt.id
                                }}</span>
                        </h2>
                        <button @click="closeModal"
                            class="p-2 text-on-surface-variant hover:bg-surface-container-high hover:text-error rounded-full transition-colors flex items-center justify-center">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div v-if="loadingDetail"
                            class="py-16 flex flex-col items-center justify-center text-on-surface-variant text-body-medium gap-2">
                            <span class="material-symbols-outlined animate-spin text-[32px] text-primary">sync</span>
                            Đang tải chi tiết phiếu nhập...
                        </div>

                        <div v-else-if="detailError"
                            class="py-16 text-center text-error text-body-medium flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">error</span> {{ detailError }}
                        </div>

                        <div v-else-if="selectedReceipt" class="p-6 space-y-6">

                            <!-- Trạng thái phiếu đã huỷ -->
                            <div v-if="selectedReceipt.status === 'cancelled'"
                                class="bg-surface-container-low border border-error/20 rounded-xl p-4 flex items-start gap-3">
                                <span class="material-symbols-outlined text-error">cancel</span>
                                <div>
                                    <p class="text-body-medium font-bold text-on-surface">Phiếu nhập này đã bị huỷ</p>
                                    <p class="text-body-small text-on-surface-variant mt-0.5">
                                        Lý do: {{ selectedReceipt.cancel_reason || '—' }}
                                    </p>
                                    <p class="text-label-small text-on-surface-variant/70 mt-0.5">
                                        Huỷ lúc: {{ formatDate(selectedReceipt.cancelled_at) }}
                                        <span v-if="selectedReceipt.cancelled_by">
                                            bởi {{ userDisplayName(selectedReceipt.cancelled_by) }}</span>
                                    </p>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-2 md:grid-cols-4 gap-5 bg-surface-container-low border border-outline-variant/20 rounded-xl p-5 shadow-sm">
                                <div class="flex flex-col gap-1">
                                    <p class="text-label-medium font-bold text-on-surface-variant">Ngày nhập</p>
                                    <p class="text-body-medium text-on-surface">{{
                                        formatDate(selectedReceipt.created_at) }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-label-medium font-bold text-on-surface-variant">Nhà cung cấp</p>
                                    <p class="text-body-medium text-on-surface font-bold">{{
                                        selectedReceipt.supplier_name ||
                                        '—' }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-label-medium font-bold text-on-surface-variant">Người tạo</p>
                                    <p class="text-body-medium text-on-surface">{{ userDisplayName(selectedReceipt.user)
                                        }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-label-medium font-bold text-on-surface-variant">Tổng chi phí</p>
                                    <p class="text-title-large font-bold text-primary font-mono leading-none">
                                        {{ formatNum(selectedReceipt.total_cost) }}₫
                                    </p>
                                </div>
                                <div class="col-span-2 md:col-span-4 flex flex-col gap-1 pt-2 border-t border-outline-variant/20"
                                    v-if="selectedReceipt.note">
                                    <p class="text-label-medium font-bold text-on-surface-variant">Ghi chú</p>
                                    <p class="text-body-medium text-on-surface italic">"{{ selectedReceipt.note }}"</p>
                                </div>
                            </div>

                            <div>
                                <h3
                                    class="text-label-large font-bold text-on-surface uppercase tracking-wider mb-3 px-1">
                                    Danh
                                    sách nguyên liệu</h3>
                                <div
                                    class="border border-outline-variant/20 rounded-xl overflow-hidden shadow-sm bg-surface">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr
                                                class="bg-surface-container border-b-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                                <th class="p-3">Nguyên liệu</th>
                                                <th class="p-3 text-right">SL</th>
                                                <th class="p-3 text-center">ĐV</th>
                                                <th class="p-3 text-right">Đơn giá</th>
                                                <th class="p-3 text-right">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant/10 text-body-medium">
                                            <tr v-for="item in selectedReceipt.details" :key="item.id"
                                                class="hover:bg-surface-container-low/50">
                                                <td class="p-3 font-bold text-on-surface">
                                                    {{ item.material?.material_name || '— (đã xoá)' }}
                                                </td>
                                                <td class="p-3 text-right font-mono text-on-surface-variant">
                                                    {{ formatNum(item.quantity) }}
                                                </td>
                                                <td class="p-3 text-center">
                                                    <span
                                                        class="px-2 py-0.5 bg-surface-container-high rounded text-label-small text-on-surface-variant">
                                                        {{ item.material?.input_unit || '—' }}
                                                    </span>
                                                </td>
                                                <td class="p-3 text-right font-mono text-on-surface-variant">
                                                    {{ formatNum(item.unit_price) }}₫
                                                </td>
                                                <td class="p-3 text-right font-mono font-bold text-primary">
                                                    {{ formatNum(lineTotal(item)) }}₫
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-surface-container-low border-t-2 border-outline-variant/20">
                                            <tr>
                                                <td colspan="4"
                                                    class="p-3 text-right font-bold text-on-surface-variant uppercase text-label-medium">
                                                    Tổng cộng:
                                                </td>
                                                <td
                                                    class="p-3 text-right font-bold text-primary font-mono text-title-medium">
                                                    {{ formatNum(calculatedTotal(selectedReceipt)) }}₫
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Form xác nhận huỷ phiếu -->
                            <div v-if="showCancelForm"
                                class="bg-surface-container-low border border-error/30 rounded-xl p-5 space-y-3">
                                <p class="text-body-medium font-bold text-error flex items-center gap-2">
                                    <span class="material-symbols-outlined">warning</span>
                                    Xác nhận huỷ phiếu nhập #{{ selectedReceipt.id }}?
                                </p>
                                <p class="text-body-small text-on-surface-variant">
                                    Toàn bộ tồn kho đã cộng từ phiếu này sẽ được hoàn lại. Hành động này không thể tự
                                    động hoàn tác.
                                </p>
                                <input v-model="cancelReason" type="text" placeholder="Lý do huỷ (tuỳ chọn)"
                                    class="w-full border border-outline-variant bg-surface rounded-xl px-4 py-2.5 text-body-medium focus:outline-none focus:ring-2 focus:ring-error/20 transition-all" />
                                <div class="flex justify-end gap-2 pt-1">
                                    <button @click="showCancelForm = false"
                                        class="px-4 py-2 text-label-medium font-bold text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-colors">
                                        Không, giữ lại
                                    </button>
                                    <button @click="confirmCancel" :disabled="cancelling"
                                        class="px-4 py-2 bg-error text-on-error font-bold text-label-medium rounded-xl hover:bg-error/90 disabled:opacity-50 transition-colors">
                                        {{ cancelling ? 'Đang huỷ...' : 'Xác nhận huỷ phiếu' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="px-6 py-4 border-t border-outline-variant/20 flex items-center justify-between flex-shrink-0 bg-surface">
                        <div v-if="selectedReceipt?.status === 'active' && !showCancelForm" class="flex gap-2">
                            <Link :href="route('admin.kho.nhap.edit', selectedReceipt.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-primary/10 text-primary hover:bg-primary/20 font-bold rounded-full transition-colors font-sans text-label-large">
                                <span class="material-symbols-outlined text-[18px]">edit</span> Sửa phiếu
                            </Link>
                            <button @click="openCancelForm"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-error/10 text-error hover:bg-error/20 font-bold rounded-full transition-colors font-sans text-label-large">
                                <span class="material-symbols-outlined text-[18px]">delete</span> Huỷ phiếu
                            </button>
                        </div>
                        <div v-else></div>

                        <button @click="closeModal"
                            class="px-6 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold rounded-full transition-colors font-sans text-label-large">
                            Đóng cửa sổ
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

/* Custom scrollbar cho nội dung modal */
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