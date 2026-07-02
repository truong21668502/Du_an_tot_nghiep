<script setup>
import { reactive, ref, onMounted, watch, computed } from "vue";
import ProfileLayout from "@/Layouts/ProfileLayout.vue";
import { useProfile } from "@/Composables/useProfile";
import BaseButton from "@/Components/Base/BaseButton.vue";

defineOptions({ layout: ProfileLayout });

defineProps({
    addresses: Array,
});

const {
    loading,
    successMessage,
    errors,
    storeAddress,
    updateAddress,
    deleteAddress,
    setDefaultAddress,
} = useProfile();

const showForm = ref(false);
const editingId = ref(null);

const form = reactive({
    receiver_name: "",
    receiver_phone: "",
    address_detail: "",
    ward: "",
    city: "",
    is_default: false,
});

const provinces = ref([]);
const wards = ref([]);
const selectedProvinceCode = ref(null);

const showCityDropdown = ref(false);
const showWardDropdown = ref(false);
const searchCity = ref("");
const searchWard = ref("");

onMounted(async () => {
    try {
        const res = await fetch("https://provinces.open-api.vn/api/v2/p/48?depth=2");
        provinces.value = await res.json();
    } catch (e) {
        console.error(e);
    }
});

watch(selectedProvinceCode, async (code) => {
    if (!code) {
        form.ward = "";
        searchWard.value = "";
        wards.value = [];
        return;
    }

    const currentProvince = provinces.value.find(p => p.code === code);
    if (currentProvince && form.city !== currentProvince.name) {
        form.ward = "";
        searchWard.value = "";
    }

    try {
        const res = await fetch(`https://provinces.open-api.vn/api/v2/p/${code}?depth=2`);
        const data = await res.json();
        wards.value = data.wards || [];
    } catch (e) {
        console.error(e);
    }
});

const filteredProvinces = computed(() =>
    provinces.value.filter((p) =>
        p.name.toLowerCase().includes(searchCity.value.toLowerCase()),
    ),
);

const filteredWards = computed(() =>
    wards.value.filter((w) =>
        w.name.toLowerCase().includes(searchWard.value.toLowerCase()),
    ),
);

const selectProvince = (p) => {
    form.city = p.name;
    selectedProvinceCode.value = p.code;
    showCityDropdown.value = false;
    if (errors.value?.city) delete errors.value.city;
};

const selectWard = (w) => {
    form.ward = w.name;
    showWardDropdown.value = false;
    if (errors.value?.ward) delete errors.value.ward;
};

onMounted(async () => {
    try {
        // Gọi thẳng API lấy thông tin Đà Nẵng kèm danh sách Phường/Xã (depth=2)
        const res = await fetch("https://provinces.open-api.vn/api/v2/p/48?depth=2");
        const data = await res.json();
        
        // Gán cứng tên và mã code của Đà Nẵng
        form.city = "Thành phố Đà Nẵng";
        selectedProvinceCode.value = 48;
        
        // Lưu danh sách phường xã của Đà Nẵng vào biến wards
        wards.value = data.wards || [];
    } catch (e) {
        console.error(e);
    }
});

const openAdd = () => {
    editingId.value = null;
    errors.value = {};

    Object.assign(form, {
        receiver_name: "",
        receiver_phone: "",
        address_detail: "",
        ward: "",
        city: "Thành phố Đà Nẵng", 
        is_default: false,
    });

    selectedProvinceCode.value = 48;
    searchWard.value = "";
    showForm.value = true;
};

const openEdit = async (addr) => {
    editingId.value = addr.id;
    errors.value = {};

    Object.assign(form, addr);
    form.city = "Thành phố Đà Nẵng";
    selectedProvinceCode.value = 48;
    searchWard.value = "";
    
    showForm.value = true;
};

const handleSubmit = () => {
    if (editingId.value) {
        updateAddress(editingId.value, { ...form }, () => {
            showForm.value = false;
        });
    } else {
        storeAddress({ ...form }, () => {
            showForm.value = false;
        });
    }
};

const handleDelete = (id) => {
    deleteAddress(id);
};
</script>

<template>
    <div
        class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6"
    >
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-serif text-headline-sm text-primary mb-1">
                    Sổ địa chỉ
                </h2>
                <p class="font-sans text-body-md text-on-surface-variant">
                    Quản lý địa chỉ nhận hàng
                </p>
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
                {{ editingId ? "Sửa địa chỉ" : "Thêm địa chỉ mới" }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <input
                        v-model="form.receiver_name"
                        placeholder="Tên người nhận"
                        class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                        :class="errors.receiver_name ? 'border-red-500 focus:border-red-500' : 'border-outline-variant/30'"
                    />
                    <p v-if="errors.receiver_name" class="text-red-500 text-xs font-sans pl-1">
                        {{ errors.receiver_name }}
                    </p>
                </div>

                <div class="space-y-1">
                    <input
                        v-model="form.receiver_phone"
                        placeholder="Số điện thoại"
                        class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                        :class="errors.receiver_phone ? 'border-red-500 focus:border-red-500' : 'border-outline-variant/30'"
                    />
                    <p v-if="errors.receiver_phone" class="text-red-500 text-xs font-sans pl-1">
                        {{ errors.receiver_phone }}
                    </p>
                </div>

                <div class="md:col-span-2 space-y-1">
                    <input
                        v-model="form.address_detail"
                        placeholder="Địa chỉ chi tiết"
                        class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                        :class="errors.address_detail ? 'border-red-500 focus:border-red-500' : 'border-outline-variant/30'"
                    />
                    <p v-if="errors.address_detail" class="text-red-500 text-xs font-sans pl-1">
                        {{ errors.address_detail }}
                    </p>
                </div>

                <div class="relative space-y-1">
                    <div
                        class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/10 rounded-xl font-sans text-body-md cursor-not-allowed opacity-75 flex justify-between items-center min-h-[46px]"
                        :class="errors.city ? 'border-red-500' : ''"
                    >
                        <span class="text-on-surface">
                            {{ form.city }}
                        </span>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant/40">lock</span>
                    </div>
                    <p v-if="errors.city" class="text-red-500 text-xs font-sans pl-1">
                        {{ errors.city }}
                    </p>
                </div>

                <div class="relative space-y-1">
                    <div
                        @click="
                            selectedProvinceCode &&
                                (showWardDropdown = !showWardDropdown);
                            showCityDropdown = false;
                        "
                        class="w-full px-4 py-2.5 border rounded-xl font-sans text-body-md flex justify-between items-center min-h-[46px]"
                        :class="[
                            selectedProvinceCode
                                ? 'bg-surface border cursor-pointer'
                                : 'bg-surface-container-low border-outline-variant/10 cursor-not-allowed opacity-60',
                            errors.ward ? 'border-red-500' : 'border-outline-variant/30'
                        ]"
                    >
                        <span
                            :class="
                                form.ward
                                    ? 'text-on-surface'
                                    : 'text-on-surface-variant/60'
                            "
                        >
                            {{ form.ward || "Phường / Xã" }}
                        </span>
                        <span
                            class="material-symbols-outlined text-sm text-on-surface-variant"
                            >arrow_drop_down</span
                        >
                    </div>
                    <p v-if="errors.ward" class="text-red-500 text-xs font-sans pl-1">
                        {{ errors.ward }}
                    </p>

                    <div
                        v-if="showWardDropdown && selectedProvinceCode"
                        class="absolute z-50 left-0 right-0 mt-1 bg-surface border border-outline-variant/30 rounded-xl shadow-lg max-h-60 overflow-hidden flex flex-col"
                    >
                        <div
                            class="p-2 border-b border-outline-variant/20 flex items-center bg-surface"
                        >
                            <input
                                v-model="searchWard"
                                placeholder="Tìm kiếm phường xã..."
                                class="w-full px-3 py-1.5 bg-surface border border-outline-variant/30 rounded-lg text-sm focus:outline-none focus:border-secondary"
                                @click.stop
                            />
                        </div>
                        <div class="overflow-y-auto flex-1">
                            <div
                                v-for="ward in filteredWards"
                                :key="ward.code"
                                @click="selectWard(ward)"
                                class="px-4 py-2 text-sm text-on-surface hover:bg-surface-container-low cursor-pointer transition-colors"
                            >
                                {{ ward.name }}
                            </div>
                            <div
                                v-if="filteredWards.length === 0"
                                class="px-4 py-3 text-sm text-on-surface-variant text-center"
                            >
                                Không tìm thấy kết quả
                            </div>
                        </div>
                    </div>
                </div>

                <label
                    class="md:col-span-2 flex items-center gap-2 cursor-pointer"
                >
                    <input
                        v-model="form.is_default"
                        type="checkbox"
                        class="w-4 h-4 rounded border-outline-variant/50 text-primary focus:ring-secondary/30"
                    />

                    <span
                        class="font-sans text-label-sm text-on-surface-variant"
                    >
                        Đặt làm địa chỉ mặc định
                    </span>
                </label>
            </div>

            <div class="flex gap-2 justify-end">
                <button
                    @click="
                        showForm = false;
                        showCityDropdown = false;
                        showWardDropdown = false;
                    "
                    class="px-4 py-2 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors"
                >
                    Hủy
                </button>

                <BaseButton
                    @click="handleSubmit"
                    variant="primary"
                    :disabled="loading"
                >
                    {{
                        loading ? "Đang lưu..." : editingId ? "Cập nhật" : "Lưu"
                    }}
                </BaseButton>
            </div>
        </div>

        <div v-if="!addresses?.length && !showForm" class="text-center py-12">
            <span
                class="material-symbols-outlined text-5xl text-outline-variant mb-3"
            >
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
                        <span
                            class="font-sans text-label-md text-on-surface font-semibold"
                        >
                            {{ addr.receiver_name }}
                        </span>

                        <span class="text-outline-variant">|</span>

                        <span
                            class="font-sans text-label-sm text-on-surface-variant"
                        >
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
                        {{ addr.address_detail
                        }}{{ addr.ward ? ", " + addr.ward : ""
                        }}{{ addr.city ? ", " + addr.city : "" }}
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
                        <span class="material-symbols-outlined text-lg"
                            >check_circle</span
                        >
                    </button>

                    <button
                        @click="openEdit(addr)"
                        class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-low rounded-full transition-colors"
                        title="Sửa"
                    >
                        <span class="material-symbols-outlined text-lg"
                            >edit</span
                        >
                    </button>

                    <button
                        @click="handleDelete(addr.id)"
                        :disabled="loading"
                        class="p-2 text-on-surface-variant hover:text-error hover:bg-red-50 rounded-full transition-colors"
                        title="Xóa"
                    >
                        <span class="material-symbols-outlined text-lg"
                            >delete</span
                        >
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>