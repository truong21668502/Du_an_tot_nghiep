<script setup>
import { reactive, watch, computed, ref } from "vue";
import BaseButton from "@/Components/Base/BaseButton.vue";

const props = defineProps({
    show: Boolean,
    editingAddress: Object,
    wards: Array,
    loading: Boolean,
    errors: Object,
});

const emit = defineEmits(["close", "submit"]);

const form = reactive({
    receiver_name: "",
    receiver_phone: "",
    address_detail: "",
    ward: "",
    city: "Thành phố Đà Nẵng",
    is_default: false,
});

const searchWard = ref("");
const showWardDropdown = ref(false);

console.log(props.editingAddress)

watch(
    () => [props.show, props.editingAddress],
    ([newShow, newEditing]) => {
        if (newShow) {
            if (newEditing && Object.keys(newEditing).length > 0) {
                Object.assign(form, {
                    receiver_name: newEditing.receiver_name || "",
                    receiver_phone: newEditing.receiver_phone || "",
                    address_detail: newEditing.address_detail || "",
                    ward: newEditing.ward || "",
                    city: newEditing.city || "Thành phố Đà Nẵng",
                    is_default: newEditing.is_default || false,
                });
            } else {
                Object.assign(form, {
                    receiver_name: "",
                    receiver_phone: "",
                    address_detail: "",
                    ward: "",
                    city: "Thành phố Đà Nẵng",
                    is_default: false,
                });
            }
        }
    },
    { immediate: true, deep: true }
);

const filteredWards = computed(() =>
    props.wards.filter((w) =>
        w.name.toLowerCase().includes(searchWard.value.toLowerCase())
    )
);

const selectWard = (w) => {
    form.ward = w.name;
    showWardDropdown.value = false;
};

const handleSubmit = () => {
    emit("submit", { ...form });
};
</script>

<template>
    <Teleport to="body">
    <!-- Thêm lớp overlay cố định full màn hình, căn giữa hoặc full viewport -->
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 md:p-6 backdrop-blur-sm">
        
        <!-- Khung modal chính: full màn hình trên mobile, giới hạn width/height đẹp mắt trên desktop, có thanh cuộn nếu nội dung dài -->
        <div class="bg-surface w-full h-full md:h-auto md:max-h-[90vh] md:max-w-2xl rounded-none md:rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6 overflow-y-auto flex flex-col shadow-2xl">
            
            <!-- Header của Modal (Có tiêu đề và nút đóng X) -->
            <div class="flex justify-between items-center border-b border-outline-variant/20 pb-4">
                <h3 class="font-serif text-headline-sm text-primary font-semibold">
                    {{ editingAddress ? "Sửa địa chỉ nhận hàng" : "Thêm địa chỉ mới" }}
                </h3>
                <button
                    @click="emit('close')"
                    class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-low rounded-full transition-colors"
                >
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Nội dung form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1">
                <div class="space-y-1">
                    <input
                        v-model="form.receiver_name"
                        placeholder="Tên người nhận"
                        class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                        :class="errors?.receiver_name ? 'border-red-500' : 'border-outline-variant/30'"
                    />
                    <p v-if="errors?.receiver_name" class="text-red-500 text-xs font-sans pl-1">
                        {{ Array.isArray(errors.receiver_name) ? errors.receiver_name[0] : errors.receiver_name }}
                    </p>
                </div>

                <div class="space-y-1">
                    <input
                        v-model="form.receiver_phone"
                        placeholder="Số điện thoại"
                        class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                        :class="errors?.receiver_phone ? 'border-red-500' : 'border-outline-variant/30'"
                    />
                    <p v-if="errors?.receiver_phone" class="text-red-500 text-xs font-sans pl-1">
                        {{ Array.isArray(errors.receiver_phone) ? errors.receiver_phone[0] : errors.receiver_phone }}
                    </p>
                </div>

                <div class="md:col-span-2 space-y-1">
                    <input
                        v-model="form.address_detail"
                        placeholder="Địa chỉ chi tiết (Số nhà, tên đường...)"
                        class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                        :class="errors?.address_detail ? 'border-red-500' : 'border-outline-variant/30'"
                    />
                    <p v-if="errors?.address_detail" class="text-red-500 text-xs font-sans pl-1">
                        {{ Array.isArray(errors.address_detail) ? errors.address_detail[0] : errors.address_detail }}
                    </p>
                </div>

                <div class="relative space-y-1">
                    <div class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/10 rounded-xl font-sans text-body-md cursor-not-allowed opacity-75 flex justify-between items-center min-h-[46px]">
                        <span class="text-on-surface">{{ form.city }}</span>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant/40">lock</span>
                    </div>
                </div>

                <div class="relative space-y-1">
                    <div
                        @click="showWardDropdown = !showWardDropdown"
                        class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md cursor-pointer flex justify-between items-center min-h-[46px]"
                        :class="errors?.ward ? 'border-red-500' : 'border-outline-variant/30'"
                    >
                        <span :class="form.ward ? 'text-on-surface' : 'text-on-surface-variant/60'">
                            {{ form.ward || "Phường / Xã" }}
                        </span>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant">arrow_drop_down</span>
                    </div>
                    <p v-if="errors?.ward" class="text-red-500 text-xs font-sans pl-1">
                        {{ Array.isArray(errors.ward) ? errors.ward[0] : errors.ward }}
                    </p>

                    <div v-if="showWardDropdown" class="absolute z-50 left-0 right-0 mt-1 bg-surface border border-outline-variant/30 rounded-xl shadow-lg max-h-60 overflow-hidden flex flex-col">
                        <div class="p-2 border-b border-outline-variant/20 bg-surface">
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
                            <div v-if="filteredWards.length === 0" class="px-4 py-3 text-sm text-on-surface-variant text-center">
                                Không tìm thấy kết quả
                            </div>
                        </div>
                    </div>
                </div>

                <label class="md:col-span-2 flex items-center gap-2 cursor-pointer pt-2">
                    <input
                        v-model="form.is_default"
                        type="checkbox"
                        class="w-4 h-4 rounded border-outline-variant/50 text-primary focus:ring-secondary/30"
                    />
                    <span class="font-sans text-label-sm text-on-surface-variant">Đặt làm địa chỉ mặc định</span>
                </label>
            </div>

            <!-- Footer nút bấm -->
            <div class="flex gap-3 justify-end pt-4 border-t border-outline-variant/20">
                <button
                    @click="emit('close')"
                    type="button"
                    class="px-5 py-2.5 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors"
                >
                    Hủy
                </button>
                <BaseButton @click="handleSubmit" variant="primary" :disabled="loading" class="px-6">
                    {{ loading ? "Đang lưu..." : editingAddress ? "Cập nhật" : "Lưu địa chỉ" }}
                </BaseButton>
            </div>
        </div>
        
    </div>
    </Teleport>
</template>