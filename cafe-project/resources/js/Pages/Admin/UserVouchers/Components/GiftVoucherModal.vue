<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    coupons: { type: Object, required: true },
    users: { type: Object, required: true },  
});

const emit = defineEmits(["close"]);

const form = useForm({
    coupon_id: "",
    user_id: "",
    send_to_all: false, // Checkbox gửi hàng loạt
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
    }
});

const submitForm = () => {
    form.post("/quan-tri/vi-voucher", {
        onSuccess: () => {
            emit("close");
            form.reset();
        },
    });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
        <div class="bg-surface w-full max-w-md rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-5">
            
            <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
                <h2 class="font-serif text-on-surface text-title-large font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">featured_seasonal_and_gifts</span>
                    Tặng & Phân phối Voucher
                </h2>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 font-sans text-body-medium">
                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Chọn mã ưu đãi chương trình *</label>
                    <select v-model="form.coupon_id" class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer focus:outline-none focus:border-primary" required>
                        <option value="" disabled selected>-- Chọn chương trình giảm giá --</option>
                        <option v-for="c in coupons" :key="c.id" :value="c.id">
                            {{ c.code }} (Giảm {{ c.discount_type === 'FIXED' ? Number(c.discount_value).toLocaleString('vi-VN') + 'đ' : c.discount_value + '%' }})
                        </option>
                    </select>
                    <span v-if="form.errors.coupon_id" class="text-body-small text-error">{{ form.errors.coupon_id }}</span>
                </div>

                <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/20 space-y-2">
                    <label class="flex items-center gap-2 font-bold text-on-surface-variant cursor-pointer select-none">
                        <input type="checkbox" v-model="form.send_to_all" class="w-4 h-4 rounded text-primary focus:ring-primary cursor-pointer" />
                        <span>Gửi hàng loạt (Toàn bộ khách hàng)</span>
                    </label>
                    <p class="text-body-small text-outline">Bật tùy chọn này để chạy chiến dịch kích cầu tiêu dùng, hệ thống tự động phát vào ví của mọi thành viên.</p>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Chọn khách hàng nhận *</label>

                    <v-select 
                        v-model="form.user_id" 
                        :options="users" 
                        :reduce="user => user.id" 
                        label="full_name"
                        placeholder="Tìm theo tên hoặc SĐT..."
                        class="style-chooser"
                    >
                        <template #option="{ full_name, phone_number }">
                            <div class="flex flex-col cursor-pointer">
                                <span class="font-bold">{{ full_name }}</span>
                                <span class="text-body-small text-outline">{{ phone_number }}</span>
                            </div>
                        </template>
                    
                        <template #selected-option="{ full_name, phone_number }">
                            {{ full_name }} ({{ phone_number }})
                        </template>
                    </v-select>
                
                    <span v-if="form.errors.user_id" class="text-body-small text-error">{{ form.errors.user_id }}</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button type="button" @click="$emit('close')" class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full cursor-pointer">Hủy bỏ</button>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all disabled:opacity-50 cursor-pointer">
                        {{ form.processing ? 'Đang thực hiện...' : 'Xác nhận phân phối' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style>
.style-chooser .vs__dropdown-toggle {
    border-radius: 0.75rem; /* Tương ứng với rounded-xl của bạn */
    padding: 5px;
    border: 1px solid #c4c6d0; /* Màu border-outline-variant */
}
.style-chooser .vs__search::placeholder {
    color: #74777f;
}
</style>