<script setup>
// Thanh thống kê nhanh ở đầu trang Barista
defineProps({
    pendingCount:   { type: Number, default: 0 },
    preparingCount: { type: Number, default: 0 },
    todayDone:      { type: Number, default: 0 },
});

const stats = (p, r, d) => [
    { label: 'Chờ xử lý', value: p, icon: 'hourglass_empty', color: 'text-error',     bg: 'bg-error/8 border-error/15' },
    { label: 'Đang pha',  value: r, icon: 'coffee',          color: 'text-tertiary',  bg: 'bg-tertiary/8 border-tertiary/15' },
    { label: 'Đã xong hôm nay', value: d, icon: 'task_alt', color: 'text-secondary', bg: 'bg-secondary/8 border-secondary/15' },
];
</script>

<template>
    <div class="grid grid-cols-3 gap-3">
        <div v-for="s in stats(pendingCount, preparingCount, todayDone)" :key="s.label"
            class="flex items-center gap-3 rounded-xl border p-3.5 transition-all"
            :class="s.bg">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="s.bg">
                <span class="material-symbols-outlined text-[20px]"
                    :class="s.color"
                    style="font-variation-settings:'FILL' 1">{{ s.icon }}</span>
            </div>
            <div>
                <p class="text-[24px] font-black leading-none" :class="s.color">{{ s.value }}</p>
                <p class="text-[13px] font-medium text-on-surface-variant mt-1 whitespace-nowrap">{{ s.label }}</p>
            </div>
        </div>
    </div>
</template>
