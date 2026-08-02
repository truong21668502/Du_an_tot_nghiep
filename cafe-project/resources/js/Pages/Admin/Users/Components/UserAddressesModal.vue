<script setup>
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    user: { type: Object, default: null }
});

const emit = defineEmits(['close']);

const addresses = ref([]);
const editingAddress = ref(null);

// Lấy lỗi validation từ Inertia page props
const page = usePage();
const errors = computed(() => page.props.errors || {});

watch(() => props.user, (newUser) => {
    if (newUser && newUser.addresses) {
        addresses.value = JSON.parse(JSON.stringify(newUser.addresses));
    } else {
        addresses.value = [];
    }
    editingAddress.value = null;
}, { immediate: true });

const startEdit = (addr) => {
    editingAddress.value = { ...addr };
};

const cancelEdit = () => {
    editingAddress.value = null;
};

const updateAddress = () => {
    if (!editingAddress.value) return;

    router.put(`/quan-tri/nguoi-dung/dia-chi/${editingAddress.value.id}`, editingAddress.value, {
        preserveScroll: true,
        onSuccess: () => {
            // Cập nhật lại danh sách local sau khi lưu thành công (nếu không reload trang)
            const index = addresses.value.findIndex(a => a.id === editingAddress.value.id);
            if (index !== -1) {
                addresses.value[index] = { ...editingAddress.value };
            }
            editingAddress.value = null;
        }
    });
};

const closeModal = () => {
    editingAddress.value = null;
    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4 font-sans">
        <div class="bg-surface w-full max-w-3xl rounded-3xl shadow-xl border border-outline-variant/20 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 bg-surface-container flex items-center justify-between border-b border-outline-variant/20">
                <div>
                    <h2 class="text-headline-sm font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">location_on</span>
                        Địa chỉ nhận hàng của: {{ user?.full_name }}
                    </h2>
                    <p class="text-body-small text-on-surface-variant">Chỉ có quyền xem và cập nhật thông tin địa chỉ.</p>
                </div>
                <button @click="closeModal" class="p-2 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <div v-if="addresses.length === 0" class="text-center py-8 text-on-surface-variant">
                    Người dùng này chưa có địa chỉ nhận hàng nào.
                </div>

                <div v-for="addr in addresses" :key="addr.id" class="p-4 rounded-2xl border border-outline-variant/30 bg-surface-container-low/50 space-y-3">
                    <!-- Form cập nhật khi đang chọn dòng này -->
                    <div v-if="editingAddress && editingAddress.id === addr.id" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-label-small text-on-surface-variant">Tên người nhận <span class="text-error">*</span></label>
                                <input v-model="editingAddress.receiver_name" type="text" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium focus:outline-none focus:ring-1 focus:ring-primary" />
                                <div v-if="errors.receiver_name" class="text-error text-body-small mt-1">{{ errors.receiver_name }}</div>
                            </div>
                            <div>
                                <label class="text-label-small text-on-surface-variant">Số điện thoại <span class="text-error">*</span></label>
                                <input v-model="editingAddress.receiver_phone" type="text" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium focus:outline-none focus:ring-1 focus:ring-primary" />
                                <div v-if="errors.receiver_phone" class="text-error text-body-small mt-1">{{ errors.receiver_phone }}</div>
                            </div>
                        </div>
                        <div>
                            <label class="text-label-small text-on-surface-variant">Chi tiết địa chỉ (Số nhà, tên đường) <span class="text-error">*</span></label>
                            <input v-model="editingAddress.address_detail" type="text" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium focus:outline-none focus:ring-1 focus:ring-primary" />
                            <div v-if="errors.address_detail" class="text-error text-body-small mt-1">{{ errors.address_detail }}</div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-label-small text-on-surface-variant">Phường / Xã</label>
                                <input v-model="editingAddress.ward" type="text" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium focus:outline-none focus:ring-1 focus:ring-primary" />
                                <div v-if="errors.ward" class="text-error text-body-small mt-1">{{ errors.ward }}</div>
                            </div>
                            <div>
                                <label class="text-label-small text-on-surface-variant">Tỉnh / Thành phố</label>
                                <input v-model="editingAddress.city" type="text" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium focus:outline-none focus:ring-1 focus:ring-primary" />
                                <div v-if="errors.city" class="text-error text-body-small mt-1">{{ errors.city }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <button @click="updateAddress" class="px-4 py-2 bg-primary text-on-primary text-label-large rounded-xl hover:bg-primary/90 cursor-pointer">Lưu thay đổi</button>
                            <button @click="cancelEdit" class="px-4 py-2 bg-surface-container-high text-on-surface text-label-large rounded-xl hover:bg-surface-container-highest cursor-pointer">Hủy</button>
                        </div>
                    </div>

                    <!-- Hiển thị thông tin thông thường -->
                    <div v-else class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-primary">{{ addr.receiver_name }}</span>
                                <span class="text-on-surface-variant font-mono text-body-small">({{ addr.receiver_phone }})</span>
                                <span v-if="addr.is_default" class="px-2 py-0.5 bg-primary-container text-on-primary-container text-[10px] font-bold rounded-full">Mặc định</span>
                            </div>
                            <div class="text-body-medium text-on-surface">
                                {{ addr.address_detail }}{{ addr.ward ? ', ' + addr.ward : '' }}{{ addr.city ? ', ' + addr.city : '' }}
                            </div>
                        </div>
                        <button @click="startEdit(addr)" class="p-2 text-secondary hover:bg-surface-container rounded-full cursor-pointer transition-colors" title="Cập nhật địa chỉ">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-surface-container border-t border-outline-variant/20 flex justify-end">
                <button @click="closeModal" class="px-5 py-2 bg-surface-container-high text-on-surface text-label-large rounded-xl hover:bg-surface-container-highest cursor-pointer">Đóng</button>
            </div>
        </div>
    </div>
</template>