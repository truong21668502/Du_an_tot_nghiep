<script setup>
// Panel công thức pha chế – hiển thị bên phải khi chọn món
defineProps({
    detail: { type: Object, default: null },
});

defineEmits(['advance', 'close']);

const btnConfig = {
    PENDING:   { label: 'Bắt đầu pha', icon: 'coffee',       bg: 'bg-tertiary text-on-tertiary' },
    PREPARING: { label: 'Hoàn thành',  icon: 'check_circle', bg: 'bg-secondary text-on-secondary' },
};
</script>

<template>
    <!-- Trạng thái rỗng -->
    <div v-if="!detail"
        class="flex flex-col items-center justify-center h-full text-center p-10 bg-surface-container-lowest rounded-2xl border border-dashed border-outline-variant/30">
        <div class="w-16 h-16 rounded-2xl bg-surface-container flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-[32px] text-on-surface-variant/40" style="font-variation-settings:'FILL' 1">menu_book</span>
        </div>
        <p class="text-[14px] font-bold text-on-surface-variant/60">Chọn một món để xem công thức</p>
        <p class="text-[12px] text-on-surface-variant/40 mt-1">Nhấn vào thẻ bên trái để xem chi tiết</p>
    </div>

    <!-- Panel chi tiết công thức -->
    <div v-else class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 overflow-hidden flex flex-col h-full">

        <!-- Header -->
        <div class="px-5 py-4 bg-surface border-b border-outline-variant/15 flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-[16px] text-primary" style="font-variation-settings:'FILL' 1">menu_book</span>
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-primary">Công thức pha chế</p>
                </div>
                <div class="flex items-center gap-3">
                    <img v-if="detail.product?.image_url" :src="detail.product.image_url" class="w-12 h-12 rounded-lg object-cover flex-shrink-0 border border-outline-variant/20" alt="" />
                    <div class="min-w-0">
                        <h2 class="text-[16px] font-bold text-on-surface truncate">{{ detail.product?.product_name }}</h2>
                        <p class="text-[11px] text-on-surface-variant mt-0.5">
                            Size {{ detail.variant?.size || '---' }} •
                            {{ detail.order?.table?.table_name || 'Mang đi' }} •
                            ×{{ detail.quantity }}
                        </p>
                    </div>
                </div>
            </div>
            <button @click="$emit('close')" class="ml-2 w-7 h-7 flex items-center justify-center rounded-full hover:bg-surface-container text-on-surface-variant transition-colors flex-shrink-0">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-5 space-y-4 hide-scrollbar">

            <!-- Lưu ý từ khách -->
            <div v-if="detail.note"
                class="flex items-start gap-3 p-3.5 rounded-xl bg-error/5 border border-error/15">
                <span class="material-symbols-outlined text-[18px] text-error mt-0.5" style="font-variation-settings:'FILL' 1">campaign</span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-error mb-0.5">Lưu ý từ khách</p>
                    <p class="text-[13px] text-error font-medium">{{ detail.note }}</p>
                </div>
            </div>

            <!-- Công thức (từ bảng recipes) -->
            <div v-if="detail.variant?.recipes && detail.variant.recipes.length > 0">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">science</span>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Định lượng nguyên liệu</p>
                </div>
                <div class="rounded-xl border border-outline-variant/20 overflow-hidden">
                    <div class="bg-surface-container-low px-4 py-2 flex justify-between text-[10px] font-bold uppercase tracking-wider text-on-surface-variant border-b border-outline-variant/15">
                        <span>Nguyên liệu</span>
                        <span>Định lượng</span>
                    </div>
                    <ul class="divide-y divide-outline-variant/10">
                        <li v-for="recipe in detail.variant.recipes" :key="recipe.id"
                            class="px-4 py-3 flex items-center justify-between bg-surface-container-lowest hover:bg-surface-container-low/50 transition-colors">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                                <span class="text-[13px] text-on-surface font-medium">{{ recipe.material?.material_name }}</span>
                            </div>
                            <span class="text-[13px] font-bold text-primary">
                                <span class="text-[12px] font-normal text-on-surface-variant/60">≈</span> {{ recipe.quantity_needed }} <span class="text-[11px] font-normal text-on-surface-variant">{{ recipe.material?.base_unit }}</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Không có công thức -->
            <div v-else
                class="p-6 text-center rounded-xl border border-dashed border-outline-variant/30 bg-surface-container-lowest">
                <span class="material-symbols-outlined text-[28px] text-on-surface-variant/40 mb-2 block">menu_book</span>
                <p class="text-[12px] text-on-surface-variant italic">Chưa có công thức cho size này.</p>
            </div>
        </div>

        <!-- Footer: nút hành động -->
        <div v-if="btnConfig[detail.barista_status]"
            class="px-5 py-4 bg-surface border-t border-outline-variant/15">
            <button @click="$emit('advance', detail)"
                :class="btnConfig[detail.barista_status].bg"
                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-[13px] hover:opacity-90 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">
                    {{ btnConfig[detail.barista_status].icon }}
                </span>
                {{ btnConfig[detail.barista_status].label }}
            </button>
        </div>
        <div v-else class="px-5 py-4 bg-secondary/5 border-t border-secondary/15 flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-secondary" style="font-variation-settings:'FILL' 1">check_circle</span>
            <span class="text-[13px] font-bold text-secondary">Đã hoàn thành</span>
        </div>
    </div>
</template>

<style scoped>
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>
