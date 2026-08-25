<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from 'vue3-toastify';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    editMode: { type: Boolean, default: false },
    userData: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const form = useForm({
    full_name: "",
    phone_number: "",
    email: "",
    password: "",
    role: "STAFF",
    gender: "Khác",
    date_of_birth: "",
    status: "active",
    status_note: "",
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
        
        if (props.editMode && props.userData) {
            form.full_name = props.userData.full_name;
            form.phone_number = props.userData.phone_number || "";
            form.email = props.userData.email || "";
            form.role = props.userData.role;
            form.gender = props.userData.gender;
            if (props.userData.date_of_birth) {
                // Cắt lấy 10 ký tự đầu tiên (YYYY-MM-DD) để trình duyệt hiểu được
                form.date_of_birth = props.userData.date_of_birth.substring(0, 10);
            } else {
                form.date_of_birth = "";
            }
            form.status = props.userData.status;
            form.status_note = props.userData.status_note || "";
        } else {
            form.role = "STAFF";
        }
    }
});

const submitForm = () => {
    if (props.editMode) {
        form.put(`/quan-tri/nguoi-dung/${props.userData.id}`, {
            onSuccess: () => {
                emit("close");
            },
        });
    } else {
        form.post("/quan-tri/nguoi-dung", {
            onSuccess: () => {
                emit("close");
            },
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
        <div class="bg-surface w-full max-w-lg rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-5">
            
            <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
                <h2 class="font-serif text-on-surface text-title-large font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">
                        {{ editMode ? 'manage_accounts' : 'person_add' }}
                    </span>
                    {{ editMode ? 'Cấu hình tài khoản' : 'Cấp tài khoản nhân sự mới' }}
                </h2>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 font-sans text-body-medium">
                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Họ và tên nhân sự *</label>
                    <input v-model="form.full_name" type="text" placeholder="Nhập tên nhân viên..." class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" required />
                    <span v-if="form.errors.full_name" class="text-body-small text-error">{{ form.errors.full_name }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Số điện thoại</label>
                        <input v-model="form.phone_number" type="text" placeholder="Nhập số điện thoại..." class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary" />
                        <span v-if="form.errors.phone_number" class="text-body-small text-error">{{ form.errors.phone_number }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Địa chỉ Email *</label>
                        <input v-model="form.email" type="email" placeholder="username@nangcoffee.vn" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary" required />
                        <span v-if="form.errors.email" class="text-body-small text-error">{{ form.errors.email }}</span>
                    </div>
                </div>

                <div v-if="!editMode" class="flex flex-col gap-1 bg-surface-container-low p-3 rounded-xl border border-outline-variant/30">
                    <label class="text-label-large text-primary font-bold">Mật khẩu khởi tạo hệ thống *</label>
                    <input v-model="form.password" type="text" placeholder="Mật khẩu tối thiểu 6 ký tự..." class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-mono text-on-surface focus:outline-none focus:border-primary" required />
                    <span v-if="form.errors.password" class="text-body-small text-error">{{ form.errors.password }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Vai trò *</label>
                        <select v-model="form.role" class="px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium cursor-pointer focus:outline-none focus:border-primary">
                            <option value="STAFF">Phục vụ (STAFF)</option>
                            <option value="BARISTA">Pha chế (BARISTA)</option>
                            <option value="ADMIN">Quản trị (ADMIN)</option>
                            <option v-if="editMode" value="CUSTOMER">Khách hàng</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Trạng thái *</label>
                        <select v-model="form.status" class="px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium cursor-pointer focus:outline-none focus:border-primary" :disabled="!editMode">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm ngưng</option>
                            <option value="banned">Bị khóa (Banned)</option>
                        </select>
                    </div>
                </div>

                <div v-if="form.status !== 'active'" class="flex flex-col gap-1 bg-warning/10 p-3 rounded-xl border border-warning/30 animate-fade-in">
                    <label class="text-label-large text-warning font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        Lý do Tạm ngưng / Bị khóa tài khoản *
                    </label>
                    <input v-model="form.status_note" type="text" placeholder="Ví dụ: Nghỉ chế độ thai sản từ ngày 26/06 đến hết năm..." class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary text-body-medium" :required="form.status !== 'active'" />
                    <span v-if="form.errors.status_note" class="text-body-small text-error">{{ form.errors.status_note }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Giới tính</label>
                        <select v-model="form.gender" class="px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-medium cursor-pointer focus:outline-none focus:border-primary">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Ngày tháng năm sinh</label>
                        <input v-model="form.date_of_birth" type="date" class="px-4 py-1.5 rounded-xl border border-outline-variant bg-surface text-body-small font-mono text-on-surface focus:outline-none focus:border-primary" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button type="button" @click="$emit('close')" class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full cursor-pointer">Hủy bỏ</button>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all disabled:opacity-50 cursor-pointer">
                        {{ form.processing ? 'Đang xử lý...' : (editMode ? 'Cập nhật tài khoản' : 'Cấp tài khoản') }}
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