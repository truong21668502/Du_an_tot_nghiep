<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from 'vue3-toastify';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    editMode: { type: Boolean, default: false },
    wordData: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const form = useForm({
    word: "",
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.editMode && props.wordData) {
            form.word = props.wordData.word;
        } else {
            form.reset();
            form.clearErrors();
        }
    }
});

const submitForm = () => {
    if (props.editMode) {
        form.put(`/quan-tri/tu-khoa-vi-pham/${props.wordData.id}`, {
            onSuccess: () => {
                emit("close");
                toast.success('Cập nhật từ khóa thành công!');
                form.reset();
            },
        });
    } else {
        form.post("/quan-tri/tu-khoa-vi-pham", {
            onSuccess: () => {
                emit("close");
                toast.success('Thêm từ khóa thành công!');
                form.reset();
            },
        });
    }
};
</script>

<template>
    <div v-if="isOpen" @click.self="$emit('close')"  class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
        <div class="bg-surface w-full max-w-md rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-6 animate-scale-up">
            
            <div class="flex items-center justify-between">
                <h2 class="font-serif text-on-surface text-title-large font-bold">
                    {{ editMode ? 'Chỉnh sửa từ khóa' : 'Thêm từ khóa mới' }}
                </h2>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 font-sans text-body-medium">
                <div class="flex flex-col gap-1">
                    <label class="text-label-large text-on-surface-variant font-bold">
                        Từ khóa vi phạm <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">text_fields</span>
                        <input 
                            v-model="form.word" 
                            type="text" 
                            placeholder="Nhập từ khóa cần cấm..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                        />
                    </div>
                    <span v-if="form.errors.word" class="text-body-small text-error flex items-center gap-1 mt-0.5">
                        <span class="material-symbols-outlined text-sm">error</span> {{ form.errors.word }}
                    </span>
                </div>

                <div class="p-3 bg-surface-container-low rounded-xl flex items-start gap-2">
                    <span class="material-symbols-outlined text-primary flex-shrink-0">info</span>
                    <p class="text-body-small text-on-surface-variant">
                        Từ khóa sẽ được sử dụng để kiểm tra và chặn nội dung vi phạm trên toàn hệ thống.
                        Có thể sử dụng tiếng Việt có dấu, chữ cái, số và khoảng trắng.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button 
                        type="button" 
                        @click="$emit('close')" 
                        class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full transition-colors cursor-pointer"
                    >
                        Hủy bỏ
                    </button>
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all disabled:opacity-50 cursor-pointer flex items-center gap-2"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-sm">sync</span>
                        {{ form.processing ? 'Đang lưu...' : (editMode ? 'Cập nhật' : 'Lưu lại') }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
.animate-scale-up {
    animation: scaleUp 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleUp {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>