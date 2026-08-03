<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import ProfileLayout from "@/Layouts/ProfileLayout.vue";
import AddressFormModal from "./Components/AddressFormModal.vue";

defineOptions({ layout: ProfileLayout });

defineProps({
    addresses: Array,
});

const showForm = ref(false);
const editingAddress = ref(null);
const loading = ref(false);
const successMessage = ref("");
const errors = ref({});

const openAdd = () => {
    editingAddress.value = null;
    errors.value = {};
    showForm.value = true;
};

const openEdit = (addr) => {
    editingAddress.value = addr;
    errors.value = {};
    showForm.value = true;
};

const handleFormSubmit = async (formData) => {
    loading.value = true;
    errors.value = {};
    successMessage.value = "";

    try {
        let response;
        if (editingAddress.value) {
            response = await axios.put(`/profile/user-addresses/${editingAddress.value.id}`, formData);
        } else {
            response = await axios.post('/profile/user-addresses', formData);
        }

        if (response.data.success) {
            successMessage.value = response.data.message;
            showForm.value = false;
            // Force reload lại toàn bộ prop addresses để hiển thị tọa độ mới
            router.reload({ preserveScroll: true, preserveState: false, only: ['addresses'] });
        }
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
        }
    } finally {
        loading.value = false;
    }
};

const handleDelete = async (id) => {
    if (!confirm("Bạn có chắc chắn muốn xóa địa chỉ này?")) return;
    
    loading.value = true;
    try {
        const response = await axios.delete(`/profile/user-addresses/${id}`);
        if (response.data.success) {
            successMessage.value = response.data.message;
            router.reload({ preserveScroll: true, preserveState: false, only: ['addresses'] });
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const setDefaultAddress = async (id) => {
    loading.value = true;
    try {
        const response = await axios.put(`/profile/user-addresses/${id}/set-default`);
        if (response.data.success) {
            successMessage.value = response.data.message;
            router.reload({ preserveScroll: true, preserveState: false, only: ['addresses'] });
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};
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
                class="px-4 py-2 bg-primary text-on-primary rounded-full font-sans text-label-sm hover:bg-primary/95 transition-colors flex items-center gap-1"
            >
                <span class="material-symbols-outlined text-sm">add</span>
                Thêm mới
            </button>
        </div>

        <div v-if="successMessage" class="p-4 bg-green-50 border border-green-200 rounded-xl font-sans text-body-md text-green-800">
            {{ successMessage }}
        </div>

        <!-- Đã bỏ :wards không cần thiết -->
        <AddressFormModal
            :show="showForm"
            :editing-address="editingAddress"
            :loading="loading"
            :errors="errors"
            @close="showForm = false"
            @submit="handleFormSubmit"
        />

        <div v-if="!addresses?.length && !showForm" class="text-center py-12">
            <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">location_off</span>
            <p class="font-sans text-body-md text-on-surface-variant">Bạn chưa có địa chỉ nào</p>
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
                        {{ addr.address_detail }}{{ addr.ward ? ", " + addr.ward : "" }}{{ addr.city ? ", " + addr.city : "" }}
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