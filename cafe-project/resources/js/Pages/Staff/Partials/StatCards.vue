<script setup>
import { computed } from 'vue';

const props = defineProps({
    tables: { type: Array, default: () => [] },
    orders: { type: Array, default: () => [] }
});

const occupiedTablesCount = computed(() => {
    return props.tables.filter(table => table.status === 'OCCUPIED').length;
});

const totalTablesCount = computed(() => {
    return props.tables.length || 0;
});

const newOrdersCount = computed(() => {
    return props.orders.filter(order => order.status === 'PENDING').length;
});

const processingOrdersCount = computed(() => {
    return props.orders.filter(order => order.status === 'PROCESSING').length;
});

const occupiedPercent = computed(() => {
    if (!totalTablesCount.value) return 0;
    return Math.round((occupiedTablesCount.value / totalTablesCount.value) * 100);
});
</script>

<template>
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">

        <!-- Card: Đơn chờ xử lý -->
        <div class="stat-card group relative overflow-hidden rounded-2xl p-6 border border-outline-variant/20 bg-surface-container-low shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
            <!-- Accent gradient blob -->
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full bg-error/10 blur-2xl group-hover:bg-error/15 transition-all duration-500"></div>
            
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[13px] font-bold uppercase tracking-[0.12em] text-on-surface-variant mb-1">Chờ xử lý</p>
                    <div class="flex items-end gap-2 mt-1">
                        <span class="text-[42px] font-bold leading-none text-on-surface" style="font-variant-numeric: tabular-nums;">{{ newOrdersCount }}</span>
                        <span class="text-body-md text-on-surface-variant mb-1.5">đơn</span>
                    </div>
                </div>
                <div class="relative w-11 h-11 rounded-2xl bg-error/10 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-error text-[22px]">receipt_long</span>
                    <span v-if="newOrdersCount > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-error rounded-full animate-ping opacity-75"></span>
                    <span v-if="newOrdersCount > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-error rounded-full"></span>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-outline-variant/20 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full flex-shrink-0" :class="newOrdersCount > 0 ? 'bg-error animate-pulse' : 'bg-on-surface-variant/30'"></span>
                <span class="text-[13px] text-on-surface-variant">{{ newOrdersCount > 0 ? 'Cần xử lý ngay' : 'Không có đơn chờ' }}</span>
            </div>
        </div>

        <!-- Card: Đang xử lý -->
        <div class="stat-card group relative overflow-hidden rounded-2xl p-6 border border-outline-variant/20 bg-surface-container-low shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full bg-primary/10 blur-2xl group-hover:bg-primary/15 transition-all duration-500"></div>

            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[13px] font-bold uppercase tracking-[0.12em] text-on-surface-variant mb-1">Đang xử lý</p>
                    <div class="flex items-end gap-2 mt-1">
                        <span class="text-[42px] font-bold leading-none text-on-surface" style="font-variant-numeric: tabular-nums;">{{ processingOrdersCount }}</span>
                        <span class="text-body-md text-on-surface-variant mb-1.5">đơn</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-primary text-[22px]">coffee_maker</span>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-outline-variant/20 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full flex-shrink-0" :class="processingOrdersCount > 0 ? 'bg-primary animate-pulse' : 'bg-on-surface-variant/30'"></span>
                <span class="text-[13px] text-on-surface-variant">{{ processingOrdersCount > 0 ? 'Barista đang pha chế' : 'Chưa có đơn đang làm' }}</span>
            </div>
        </div>

        <!-- Card: Bàn đang phục vụ -->
        <div class="stat-card group relative overflow-hidden rounded-2xl p-6 border border-outline-variant/20 bg-surface-container-low shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 sm:col-span-2 lg:col-span-1">
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full bg-secondary/10 blur-2xl group-hover:bg-secondary/15 transition-all duration-500"></div>

            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[13px] font-bold uppercase tracking-[0.12em] text-on-surface-variant mb-1">Bàn có khách</p>
                    <div class="flex items-end gap-2 mt-1">
                        <span class="text-[42px] font-bold leading-none text-on-surface" style="font-variant-numeric: tabular-nums;">{{ occupiedTablesCount }}</span>
                        <span class="text-body-md text-on-surface-variant mb-1.5">/ {{ totalTablesCount }} bàn</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-secondary/10 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-secondary text-[22px]">table_restaurant</span>
                </div>
            </div>

            <!-- Mini progress bar -->
            <div class="mt-4 pt-4 border-t border-outline-variant/20">
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-[13px] text-on-surface-variant">Tỉ lệ lấp đầy</span>
                    <span class="text-[13px] font-bold text-on-surface">{{ occupiedPercent }}%</span>
                </div>
                <div class="h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700"
                        :class="occupiedPercent >= 80 ? 'bg-error' : occupiedPercent >= 50 ? 'bg-primary' : 'bg-secondary'"
                        :style="`width: ${occupiedPercent}%`"></div>
                </div>
            </div>
        </div>

    </section>
</template>