<script setup>
defineProps({
    isOpen: { type: Boolean, required: true },
    productName: { type: String, default: "" },
    variants: { type: Array, default: () => [] },
});

defineEmits(["close"]);

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${hours}:${minutes} — ${day}/${month}/${d.getFullYear()}`;
};
</script>

<template>
    <div 
        v-if="isOpen" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div class="bg-surface w-full max-w-lg rounded-3xl border border-outline-variant/20 shadow-2xl overflow-hidden flex flex-col font-sans">
            <div class="flex items-center justify-between p-6 border-b border-outline-variant/20 bg-surface flex-shrink-0">
                <div>
                    <h3 class="text-title-large font-bold text-on-surface">Biến thể hiện có</h3>
                    <p class="text-body-small text-on-surface-variant mt-1">{{ productName }}</p>
                </div>
                <button @click="$emit('close')" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        
            <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto bg-surface-container-lowest">
                <div v-for="v in variants" :key="v.id" class="flex flex-col gap-3 p-4 rounded-2xl bg-surface border border-outline-variant/40 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex flex-col">
                            <span class="font-bold text-title-medium text-primary text-lg">{{ v.size }}</span>
                            <div class="flex items-center gap-2 mt-1 text-body-small text-on-surface-variant">
                                <span class="text-outline">|</span>
                                <span>Đã bán: <strong class="text-on-surface">{{ v.sold || 0 }}</strong></span>
                            </div>
                        </div>
                    
                        <div class="text-right">
                            <div v-if="v.discount_price" class="font-mono text-title-large text-error font-bold">
                                {{ Number(v.discount_price).toLocaleString('vi-VN') }}đ
                            </div>
                            <div :class="[v.discount_price ? 'text-body-small text-outline line-through' : 'text-title-medium font-bold text-on-surface font-mono']">
                                {{ Number(v.price).toLocaleString('vi-VN') }}đ
                            </div>
                        </div>
                    </div>
                
                    <div v-if="v.discount_price && v.sale_date_start" class="flex flex-col gap-2 p-3 bg-error-container/10 text-on-error-container rounded-xl border border-error/10 text-[13px] font-mono">
                        <div class="flex items-center gap-2 text-error">
                            <span class="material-symbols-outlined text-[18px]">schedule</span>
                            <span class="font-sans font-bold uppercase tracking-widest text-[10px]">Lịch trình giảm giá</span>
                        </div>
                        <div class="flex flex-col gap-1 text-on-surface-variant">
                            <div class="flex items-center justify-between">
                                <span class="font-sans text-[11px] text-outline">BẮT ĐẦU</span>
                                <span>{{ formatDate(v.sale_date_start) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-sans text-[11px] text-outline">KẾT THÚC</span>
                                <span>{{ v.sale_date_end ? formatDate(v.sale_date_end) : 'Vô thời hạn' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            
                <div v-if="!variants.length" class="text-center py-10">
                    <span class="material-symbols-outlined text-5xl text-outline/30 mb-2 block">layers_clear</span>
                    <p class="text-on-surface-variant">Sản phẩm này chưa cấu hình biến thể.</p>
                </div>
            </div>
        
            <div class="p-4 border-t border-outline-variant/20 flex justify-end bg-surface flex-shrink-0">
                <button @click="$emit('close')" class="px-8 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-md">
                    Đóng lại
                </button>
            </div>
        </div>
    </div>
</template>