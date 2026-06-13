<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { toast } from 'vue3-toastify';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    product: { type: Object, default: null }
});

const emit = defineEmits(["close", "refreshProduct"]);
const newImageUrl = ref("");

const addRelatedImage = () => {
    if (!newImageUrl.value.trim()) {
        toast.error("Vui lòng nhập hoặc dán đường dẫn hình ảnh!");
        return;
    }
    
    router.post('/quan-tri/hinh-anh-phu', {
        product_id: props.product.id,
        image_url: newImageUrl.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newImageUrl.value = "";
            emit("refreshProduct", props.product.id);
        }
    });
};

const deleteRelatedImage = (imageId) => {
    if (confirm("Bạn có chắc chắn muốn xóa hình ảnh phụ này khỏi thư viện?")) {
        router.delete(`/quan-tri/hinh-anh-phu/${imageId}`, {
            preserveScroll: true,
            onSuccess: () => {
                emit("refreshProduct", props.product.id);
            }
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-surface w-full max-w-2xl rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
                <h3 class="font-serif text-headline-small text-on-surface">
                    Album ảnh phụ: <span class="text-primary">{{ product?.product_name }}</span>
                </h3>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="flex gap-2 font-sans text-body-medium">
                <input v-model="newImageUrl" type="text" placeholder="Dán link ảnh mới vào đây..." class="flex-1 px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-small focus:outline-none focus:border-primary font-mono" />
                <button @click="addRelatedImage" class="px-4 py-2 bg-primary text-on-primary font-bold rounded-xl hover:bg-primary/90 transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-md">cloud_upload</span> Tải lên
                </button>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-4 max-h-72 overflow-y-auto p-1">
                <div v-for="img in product?.images" :key="img.id" class="relative group aspect-square rounded-xl overflow-hidden border border-outline-variant/20 bg-surface-container-low">
                    <img :src="img.image_url" class="w-full h-full object-cover" alt="Sub-thumb" />
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <button @click="deleteRelatedImage(img.id)" class="p-2 bg-error text-on-error rounded-full hover:bg-error/90">
                            <span class="material-symbols-outlined text-md">delete</span>
                        </button>
                    </div>
                </div>
                <div v-if="!product?.images || product.images.length === 0" class="col-span-4 py-8 text-center text-on-surface-variant text-body-medium font-sans">
                    <span class="material-symbols-outlined text-3xl text-outline mb-1 block">imagesmode</span>
                    Món này chưa có hình ảnh phụ nào trong thư viện.
                </div>
            </div>
        </div>
    </div>
</template>