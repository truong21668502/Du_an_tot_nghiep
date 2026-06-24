<script setup>
import { computed } from 'vue';

const props = defineProps({
    tables: { type: Array, default: () => [] },
    orders: { type: Array, default: () => [] }
});

// Tính số bàn đang có khách
const occupiedTablesCount = computed(() => {
    return props.tables.filter(table => table.status === 'OCCUPIED').length;
});

const totalTablesCount = computed(() => {
    return props.tables.length || 0; 
});

// Tính số đơn hàng đang chờ xử lý
const newOrdersCount = computed(() => {
    return props.orders.filter(order => order.status === 'PENDING').length;
});
</script>

<template>
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        
        <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-outline-variant/30 flex flex-col justify-between min-h-[140px]">
            <div class="flex justify-between items-start">
                <span class="text-label-md text-on-surface-variant font-bold uppercase tracking-wider">Đơn hàng mới</span>
                <span class="material-symbols-outlined text-primary bg-primary-container/30 p-2 rounded-full">receipt_long</span>
            </div>
            <div class="mt-4">
                <span class="text-[36px] font-bold text-on-surface leading-none">{{ newOrdersCount }}</span>
                <span class="text-body-md text-on-surface-variant ml-2">đơn chờ xử lý</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-outline-variant/30 flex flex-col justify-between min-h-[140px]">
            <div class="flex justify-between items-start">
                <span class="text-label-md text-on-surface-variant font-bold uppercase tracking-wider">Bàn đang phục vụ</span>
                <span class="material-symbols-outlined text-primary bg-primary-container/30 p-2 rounded-full">table_restaurant</span>
            </div>
            <div class="mt-4">
                <span class="text-[36px] font-bold text-on-surface leading-none">{{ occupiedTablesCount }}</span>
                <span class="text-body-md text-on-surface-variant ml-2">/ {{ totalTablesCount }} bàn</span>
            </div>
        </div>

    </section>
</template>