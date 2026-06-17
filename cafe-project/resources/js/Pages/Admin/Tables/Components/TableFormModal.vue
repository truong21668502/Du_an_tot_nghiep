<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    editMode: { type: Boolean, default: false },
    tableData: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const form = useForm({
    table_name: "",
    area: "Tầng trệt",
    capacity: 4,
    qr_code: "",
    status: "EMPTY",
});

// Watch đóng/mở modal để đồng bộ dữ liệu cũ hoặc reset form trống
watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.editMode && props.tableData) {
            form.table_name = props.tableData.table_name;
            form.area = props.tableData.area || "Tầng trệt";
            form.capacity = props.tableData.capacity;
            form.qr_code = props.tableData.qr_code || "";
            form.status = props.tableData.status;
        } else {
            form.reset();
            form.clearErrors();
        }
    }
});

const submitForm = () => {
    const url = props.editMode ? `/quan-tri/ban/${props.tableData.id}` : "/quan-tri/ban";

    if (props.editMode) {
        form.put(url, {
            onSuccess: () => {
                emit("close");
                form.reset();
            },
        });
    } else {
        form.post(url, {
            onSuccess: () => {
                emit("close");
                form.reset();
            },
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
        <div class="bg-surface w-full max-w-md rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-6 animate-scale-up">
            
            <div class="flex items-center justify-between">
                <h2 class="font-serif text-on-surface text-title-large font-bold">
                    {{ editMode ? 'Chỉnh sửa bàn ăn/cà phê' : 'Thêm bàn mới phục vụ' }}
                </h2>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 font-sans text-body-medium">
                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Tên / Số bàn *</label>
                    <input v-model="form.table_name" type="text" placeholder="Ví dụ: Bàn 01, Bàn VIP 05" class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" required />
                    <span v-if="form.errors.table_name" class="text-body-small text-error flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-sm">error</span> {{ form.errors.table_name }}</span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Khu vực vị trí</label>
                    <input v-model="form.area" type="text" placeholder="Ví dụ: Tầng trệt, Ngoài trời, Sân thượng" class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Sức chứa (Người) *</label>
                        <input v-model.number="form.capacity" type="number" min="1" class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" required />
                        <span v-if="form.errors.capacity" class="text-body-small text-error flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-sm">error</span> {{ form.errors.capacity }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Trạng thái ban đầu</label>
                        <select v-model="form.status" class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary transition-all cursor-pointer">
                            <option value="EMPTY">Bàn trống (EMPTY)</option>
                            <option value="OCCUPIED">Đang có khách (OCCUPIED)</option>
                            <option value="RESERVED">Đã đặt trước (RESERVED)</option>
                        </select>
                        <span v-if="form.errors.status" class="text-body-small text-error flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-sm">error</span> {{ form.errors.status }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Đường dẫn gọi món QR Code</label>
                    <input v-model="form.qr_code" type="text" placeholder="https://nangcoffee.vn/order/table-01" class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono text-body-small focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                    <span v-if="form.errors.qr_code" class="text-body-small text-error flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-sm">error</span> {{ form.errors.qr_code }}</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button type="button" @click="$emit('close')" class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full transition-colors cursor-pointer">Hủy bỏ</button>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all disabled:opacity-50 cursor-pointer">
                        {{ form.processing ? 'Đang lưu...' : (editMode ? 'Cập nhật' : 'Lưu lại') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
.animate-scale-up { animation: scaleUp 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>