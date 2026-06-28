<!-- resources/js/Pages/Admin/Brands/Components/BrandFormModal.vue -->
<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    editMode: { type: Boolean, default: false },
    brandData: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const form = useForm({
    brand_name: "",
    logo_url: "",
    description: "",
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.editMode && props.brandData) {
            form.brand_name = props.brandData.brand_name;
            form.logo_url = props.brandData.logo_url || "";
            form.description = props.brandData.description || "";
        } else {
            form.reset();
            form.clearErrors();
        }
    }
});

const submitForm = () => {
    const url = props.editMode ? `/quan-tri/thuong-hieu/${props.brandData.id}` : "/quan-tri/thuong-hieu";

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
                    {{ editMode ? 'Chỉnh sửa thương hiệu' : 'Thêm thương hiệu mới' }}
                </h2>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 font-sans text-body-medium">
                <!-- Tên thương hiệu -->
                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Tên thương hiệu *</label>
                    <input v-model="form.brand_name" type="text" placeholder="Ví dụ: Nắng Coffee, Trung Nguyên..." class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" required />
                    <span v-if="form.errors.brand_name" class="text-body-small text-error flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-sm">error</span> {{ form.errors.brand_name }}</span>
                </div>

                <!-- URL Logo -->
                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Đường dẫn hình ảnh Logo URL</label>
                    <input v-model="form.logo_url" type="text" placeholder="https://res.cloudinary.com/..." class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono text-body-small focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                    <span v-if="form.errors.logo_url" class="text-body-small text-error flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-sm">error</span> {{ form.errors.logo_url }}</span>
                </div>

                <!-- Mô tả -->
                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">Mô tả chi tiết</label>
                    <textarea v-model="form.description" rows="3" placeholder="Nhập một vài mô tả giới thiệu về thương hiệu này..." class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none"></textarea>
                </div>

                <!-- Footer nút nhấn -->
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