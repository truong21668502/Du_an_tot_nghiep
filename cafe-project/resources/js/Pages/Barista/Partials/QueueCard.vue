<script setup>
// Card hiển thị 1 món trong hàng đợi pha chế
const props = defineProps({
    detail: { type: Object, required: true },
    isSelected: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'advance']);

// Nhãn và màu theo trạng thái barista
const statusConfig = {
    PENDING:   { label: 'Chờ xử lý', color: 'bg-error/10 text-error border-error/25',     dot: 'bg-error',     pulse: true  },
    PREPARING: { label: 'Đang pha',  color: 'bg-tertiary/10 text-tertiary border-tertiary/25', dot: 'bg-tertiary', pulse: true  },
    COMPLETED: { label: 'Đã xong',   color: 'bg-secondary/10 text-secondary border-secondary/25', dot: 'bg-secondary', pulse: false },
};

const btnConfig = {
    PENDING:   { label: 'Bắt đầu pha', icon: 'coffee',       bg: 'bg-tertiary text-on-tertiary hover:opacity-90' },
    PREPARING: { label: 'Hoàn thành',  icon: 'check_circle', bg: 'bg-secondary text-on-secondary hover:opacity-90' },
};

const config    = (s) => statusConfig[s] || statusConfig.PENDING;
const btnConf   = (s) => btnConfig[s];

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false });
};
</script>

<template>
    <div @click="$emit('select', detail)"
        class="group relative rounded-2xl border cursor-pointer transition-all duration-200 overflow-hidden"
        :class="[
            isSelected
                ? 'border-primary bg-primary/5 shadow-md'
                : 'border-outline-variant/20 bg-surface-container-lowest hover:border-primary/40 hover:shadow-sm',
            detail.barista_status === 'COMPLETED' ? 'opacity-60' : '',
        ]">

        <!-- Accent bar trái -->
        <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl transition-colors"
            :class="{
                'bg-error':     detail.barista_status === 'PENDING',
                'bg-tertiary':  detail.barista_status === 'PREPARING',
                'bg-secondary': detail.barista_status === 'COMPLETED',
            }">
        </div>

        <div class="pl-5 pr-4 py-4">
            <!-- Header -->
            <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex-1 min-w-0 flex items-center gap-3">
                    <img v-if="detail.product?.image_url" :src="detail.product.image_url" class="w-10 h-10 rounded-md object-cover flex-shrink-0 border border-outline-variant/20" alt="" />
                    <div class="min-w-0">
                        <p class="text-[16px] font-bold text-on-surface truncate">
                            {{ detail.product?.product_name }}
                        </p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="material-symbols-outlined text-[14px] text-on-surface-variant">table_restaurant</span>
                            <span class="text-[13px] text-on-surface-variant">
                                {{ detail.order?.table?.table_name || 'Mang đi' }}
                            </span>
                            <span class="text-[13px] text-on-surface-variant/50">•</span>
                            <span class="text-[13px] text-on-surface-variant">×{{ detail.quantity }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status badge -->
                <span class="flex-shrink-0 inline-flex items-center gap-1 text-[13px] font-bold px-2 py-0.5 rounded-full border"
                    :class="config(detail.barista_status).color">
                    <span class="w-1.5 h-1.5 rounded-full"
                        :class="[config(detail.barista_status).dot, config(detail.barista_status).pulse ? 'animate-pulse' : '']">
                    </span>
                    {{ config(detail.barista_status).label }}
                </span>
            </div>

            <!-- Ghi chú -->
            <div v-if="detail.note"
                class="mb-3 flex items-start gap-1.5 bg-error/5 border border-error/15 rounded-lg px-2.5 py-1.5">
                <span class="material-symbols-outlined text-[15px] text-error mt-0.5">campaign</span>
                <span class="text-[13px] text-error font-medium italic leading-tight">{{ detail.note }}</span>
            </div>

            <!-- Size + Thời gian -->
            <div class="flex items-center justify-between">
                <span class="text-[13px] text-on-surface-variant">
                    Size: <strong>{{ detail.variant?.size || '---' }}</strong>
                </span>
                <span class="text-[13px] text-on-surface-variant flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    {{ formatTime(detail.created_at) }}
                </span>
            </div>

            <!-- Nút hành động -->
            <button v-if="btnConf(detail.barista_status)"
                @click.stop="$emit('advance', detail)"
                :class="btnConf(detail.barista_status).bg"
                class="mt-3 w-full flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-[14px] font-bold transition-all shadow-sm">
                <span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1">
                    {{ btnConf(detail.barista_status).icon }}
                </span>
                {{ btnConf(detail.barista_status).label }}
            </button>
        </div>
    </div>
</template>
