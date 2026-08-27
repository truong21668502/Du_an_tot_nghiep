<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    editMode: { type: Boolean, default: false },
    couponData: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const form = useForm({
    code: "",
    discount_type: "FIXED",
    discount_value: "",
    max_discount_amount: "",
    min_order_value: 0,
    usage_limit: "",
    expiration_date: "",
    description: "",
    status: "ACTIVE",
});

// Đồng bộ dữ liệu cũ khi mở modal sửa
watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.editMode && props.couponData) {
            form.code = props.couponData.code;
            form.discount_type = props.couponData.discount_type;
            form.discount_value = props.couponData.discount_value;
            form.max_discount_amount = props.couponData.max_discount_amount || "";
            form.min_order_value = props.couponData.min_order_value;
            form.usage_limit = props.couponData.usage_limit || "";
            form.description = props.couponData.description || "";

            form.status = props.couponData.status;
            
            // Xử lý chuỗi ngày giờ khớp định dạng datetime-local (YYYY-MM-DDTHH:MM)
            if (props.couponData.expiration_date) {
                form.expiration_date = props.couponData.expiration_date.replace(' ', 'T').substring(0, 16);
            }
        } else {
            form.reset();
            form.clearErrors();
        }
    }
});

// Tự động viết hoa mã coupon khi gõ
watch(() => form.code, (newVal) => {
    if (newVal) form.code = newVal.toUpperCase().replace(/\s+/g, '');
});

const submitForm = () => {
    const url = props.editMode ? `/quan-tri/ma-giam-gia/${props.couponData.id}` : "/quan-tri/ma-giam-gia";

    if (props.editMode) {
        form.put(url, {
            onSuccess: () => {
                emit("close");
            }
        });
    } else {
        form.post(url, {
            onSuccess: () => {
                emit("close");
            }
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
        <div class="bg-surface w-full max-w-lg rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-5 flex flex-col max-h-[90vh]">
            
            <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3 flex-shrink-0">
                <h2 class="font-serif text-on-surface text-title-large font-bold">
                    {{ editMode ? 'Chỉnh sửa mã giảm giá' : 'Tạo mã giảm giá mới' }}
                </h2>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 font-sans text-body-medium overflow-y-auto flex-1 pr-1">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Mã Coupon *</label>
                        <input v-model="form.code" type="text" placeholder="Ví dụ: NANG20K" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono font-bold uppercase" required :disabled="editMode" />
                        <span v-if="form.errors.code" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.code }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Loại cấu hình giảm *</label>
                        <select v-model="form.discount_type" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer">
                            <option value="FIXED">Khấu trừ tiền mặt (đ)</option>
                            <option value="PERCENTAGE">Khấu trừ theo phần trăm (%)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Giá trị giảm *</label>
                        <input v-model.number="form.discount_value" type="number" step="0.01" min="0" placeholder="Số tiền hoặc số %" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" required />
                        <span v-if="form.errors.discount_value" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.discount_value }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold" :class="{'text-outline': form.discount_type === 'FIXED'}">Giảm tối đa (đ) *</label>
                        <input v-model.number="form.max_discount_amount" type="number" step="0.01" min="0" placeholder="Bắt buộc nếu chọn %" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" :required="form.discount_type === 'PERCENTAGE'" :disabled="form.discount_type === 'FIXED'" />
                        <span v-if="form.errors.max_discount_amount" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.max_discount_amount }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Giá trị đơn tối thiểu (đ)</label>
                        <input v-model.number="form.min_order_value" type="number" step="0.01" min="0" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" required />
                        <span v-if="form.errors.min_order_value" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.min_order_value }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Tổng giới hạn lượt dùng</label>
                        <input v-model.number="form.usage_limit" type="number" placeholder="Để trống nếu vô biên" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" />
                        <span v-if="form.errors.usage_limit" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.usage_limit }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Ngày giờ hết hạn *</label>
                        <input v-model="form.expiration_date" type="datetime-local" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-small font-mono" required />
                        <span v-if="form.errors.expiration_date" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.expiration_date }}</span>
                    </div>


                    <div class="flex flex-col gap-1">
                        <label class="text-label-large text-on-surface-variant font-bold">Trạng thái phát hành</label>
                        <select v-model="form.status" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer">
                            <option value="ACTIVE">ACTIVE (Mở dùng ngay)</option>
                            <option value="INACTIVE">INACTIVE (Tạm ẩn mã)</option>
                            <option value="EXPIRED">EXPIRED (Hết hiệu lực)</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1 col-span-2">
                        <label class="text-label-large text-on-surface-variant font-bold">Mô tả</label>
                        <textarea v-model="form.description" placeholder="Nhập mô tả cho mã giảm giá" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" rows="3"></textarea>
                        <span v-if="form.errors.description" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.description }}</span>
                    </div>
                </div>

                <p class="text-body-small text-on-surface-variant">
                    <span class="material-symbols-outlined">info</span> Lưu ý: 
                    <p class="ml-4">- Đối với giảm theo % thì chỉ được giảm tối đa là 50%</p>
                    <p class="ml-4">- Đơn tối thiểu phải lớn hơn giá trị giảm và giá trị giảm tối đa là 50.000đ.</p>
                    <p class="ml-4">- Khi chỉnh sửa mã giảm giá, không thể sửa code mã giảm giá vì đã được sử dụng.</p>
                </p>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20 flex-shrink-0">
                    <button type="button" @click="$emit('close')" class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full transition-colors cursor-pointer">Hủy bỏ</button>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all disabled:opacity-50 cursor-pointer">
                        {{ form.processing ? 'Đang ghi nhận...' : (editMode ? 'Cập nhật' : 'Ph phát hành') }}
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