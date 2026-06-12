<script setup>
import { reactive, ref } from 'vue'
import ProfileLayout from '@/Layouts/ProfileLayout.vue'
import { useProfile } from '@/Composables/useProfile'
import BaseButton from '@/Components/Base/BaseButton.vue'

defineOptions({ layout: ProfileLayout })

defineProps({
    addresses: Array,
})

const { loading, successMessage, storeAddress, updateAddress, deleteAddress, setDefaultAddress } = useProfile()

const showForm = ref(false)
const editingId = ref(null)

const form = reactive({
    receiver_name: '',
    receiver_phone: '',
    address_detail: '',
    ward: '',
    city: '',
    is_default: false,
})

const openAdd = () => {
    editingId.value = null
    form.receiver_name = ''
    form.receiver_phone = ''
    form.address_detail = ''
    form.ward = ''
    form.city = ''
    form.is_default = false
    showForm.value = true
}

const openEdit = (addr) => {
    editingId.value = addr.id
    form.receiver_name = addr.receiver_name
    form.receiver_phone = addr.receiver_phone
    form.address_detail = addr.address_detail
    form.ward = addr.ward || ''
    form.city = addr.city || ''
    form.is_default = addr.is_default
    showForm.value = true
}

const handleSubmit = () => {
    if (editingId.value) {
        updateAddress(editingId.value, { ...form })
    } else {
        storeAddress({ ...form })
    }

    showForm.value = false
}

const handleDelete = (id) => {
    deleteAddress(id)
}
</script>

<template>
    <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-serif text-headline-sm text-primary mb-1">Sổ địa chỉ</h2>
                <p class="font-sans text-body-md text-on-surface-variant">Quản lý địa chỉ nhận hàng</p>
            </div>

            <button
                @click="openAdd"
                class="px-4 py-2 bg-primary text-on-primary rounded-full font-sans text-label-sm hover:bg-primary/90 transition-colors flex items-center gap-1"
            >
                <span class="material-symbols-outlined text-sm">add</span>
                Thêm mới
            </button>
        </div>

        <div
            v-if="successMessage"
            class="p-4 bg-green-50 border border-green-200 rounded-xl font-sans text-body-md text-green-800"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="showForm"
            class="border border-outline-variant/20 rounded-xl p-5 space-y-4 bg-surface-container-low"
        >
            <h3 class="font-sans text-label-lg text-on-surface font-semibold">
                {{ editingId ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới' }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input
                    v-model="form.receiver_name"
                    placeholder="Tên người nhận"
                    class="w-full px-4 py-2.5 bg-surface border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                />

                <input
                    v-model="form.receiver_phone"
                    placeholder="Số điện thoại"
                    class="w-full px-4 py-2.5 bg-surface border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                />

                <input
                    v-model="form.address_detail"
                    placeholder="Địa chỉ chi tiết"
                    class="md:col-span-2 w-full px-4 py-2.5 bg-surface border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                />

                <input
                    v-model="form.ward"
                    placeholder="Phường / Xã"
                    class="w-full px-4 py-2.5 bg-surface border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                />

                <input
                    v-model="form.city"
                    placeholder="Tỉnh / Thành phố"
                    class="w-full px-4 py-2.5 bg-surface border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                />

                <label class="md:col-span-2 flex items-center gap-2 cursor-pointer">
                    <input
                        v-model="form.is_default"
                        type="checkbox"
                        class="w-4 h-4 rounded border-outline-variant/50 text-primary focus:ring-secondary/30"
                    />

                    <span class="font-sans text-label-sm text-on-surface-variant">
                        Đặt làm địa chỉ mặc định
                    </span>
                </label>
            </div>

            <div class="flex gap-2 justify-end">
                <button
                    @click="showForm = false"
                    class="px-4 py-2 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors"
                >
                    Hủy
                </button>

                <BaseButton
                    @click="handleSubmit"
                    variant="primary"
                    :disabled="loading"
                >
                    {{ loading ? 'Đang lưu...' : (editingId ? 'Cập nhật' : 'Lưu') }}
                </BaseButton>
            </div>
        </div>

        <div
            v-if="!addresses?.length && !showForm"
            class="text-center py-12"
        >
            <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">
                location_off
            </span>

            <p class="font-sans text-body-md text-on-surface-variant">
                Bạn chưa có địa chỉ nào
            </p>
        </div>

        <div class="space-y-3">
            <div
                v-for="addr in addresses"
                :key="addr.id"
                class="border border-outline-variant/20 rounded-xl p-4 flex justify-between items-start gap-3"
            >
                <div class="space-y-1 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-sans text-label-md text-on-surface font-semibold">
                            {{ addr.receiver_name }}
                        </span>

                        <span class="text-outline-variant">|</span>

                        <span class="font-sans text-label-sm text-on-surface-variant">
                            {{ addr.receiver_phone }}
                        </span>

                        <span
                            v-if="addr.is_default"
                            class="px-2 py-0.5 bg-primary-container/30 text-on-primary-container rounded-full font-sans text-label-xs"
                        >
                            Mặc định
                        </span>
                    </div>

                    <p class="font-sans text-body-md text-on-surface-variant">
                        {{ addr.address_detail }}{{ addr.ward ? ', ' + addr.ward : '' }}{{ addr.city ? ', ' + addr.city : '' }}
                    </p>
                </div>

                <div class="flex gap-1 flex-shrink-0">
                    <button
                        v-if="!addr.is_default"
                        @click="setDefaultAddress(addr.id)"
                        :disabled="loading"
                        class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary-container/20 rounded-full transition-colors"
                        title="Đặt mặc định"
                    >
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                    </button>

                    <button
                        @click="openEdit(addr)"
                        class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-low rounded-full transition-colors"
                        title="Sửa"
                    >
                        <span class="material-symbols-outlined text-lg">edit</span>
                    </button>

                    <button
                        @click="handleDelete(addr.id)"
                        :disabled="loading"
                        class="p-2 text-on-surface-variant hover:text-error hover:bg-red-50 rounded-full transition-colors"
                        title="Xóa"
                    >
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>