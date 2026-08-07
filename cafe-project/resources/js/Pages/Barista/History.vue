<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BaristaLayout from '../../Layouts/BaristaLayout.vue';

const props = defineProps({
    history: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
let searchTimeout = null;

watch(search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('barista.history'),
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false });
};
</script>

<template>
    <Head title="Lịch sử pha chế - Barista" />

    <BaristaLayout>
        <div class="p-4 md:p-8 w-full">

            <!-- Header -->
            <div class="mb-6 flex items-end justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-block w-1.5 h-5 rounded-full bg-secondary"></span>
                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-secondary">Lịch sử</p>
                    </div>
                    <h2 class="text-[28px] font-bold text-on-surface font-serif leading-tight">Lịch sử pha chế</h2>
                    <p class="text-[13px] text-on-surface-variant mt-1">
                        Đã hoàn thành <strong class="text-secondary">{{ history.total || 0 }}</strong> món
                    </p>
                </div>

                <!-- Tìm kiếm -->
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant">search</span>
                    <input v-model="search" type="text" placeholder="Tìm món, bàn..."
                        class="pl-9 pr-4 py-2 rounded-xl bg-surface-container border border-outline-variant/25 text-[13px] text-on-surface placeholder:text-on-surface-variant/50 focus:outline-none focus:border-primary/50 w-52 transition-all"/>
                </div>
            </div>

            <!-- Trống -->
            <div v-if="history.data.length === 0"
                class="flex flex-col items-center justify-center py-20 rounded-2xl border border-dashed border-outline-variant/30 bg-surface-container-lowest text-center">
                <span class="material-symbols-outlined text-[52px] text-on-surface-variant/30 mb-3" style="font-variation-settings:'FILL' 1">history</span>
                <p class="text-[15px] font-bold text-on-surface-variant/60">Chưa có lịch sử hoặc không tìm thấy</p>
                <p class="text-[12px] text-on-surface-variant/40 mt-1">Các món đã hoàn thành sẽ hiện tại đây</p>
            </div>

            <!-- Danh sách lịch sử -->
            <div v-else class="rounded-2xl border border-outline-variant/20 overflow-hidden">
                <div class="bg-surface-container-low px-4 py-2.5 flex gap-4 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant border-b border-outline-variant/15">
                    <span class="flex-1">Món</span>
                    <span class="w-24 text-center">Vị trí</span>
                    <span class="w-20 text-center">Size</span>
                    <span class="w-20 text-right">Hoàn thành</span>
                </div>

                <ul class="divide-y divide-outline-variant/10">
                    <li v-for="detail in history.data" :key="detail.id"
                        class="px-4 py-3.5 flex items-center gap-4 bg-surface-container-lowest hover:bg-surface-container-low/40 transition-colors">
                        <!-- Tên món -->
                        <div class="flex-1 min-w-0 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-lg bg-secondary/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-[13px] text-secondary" style="font-variation-settings:'FILL' 1">check</span>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-on-surface truncate">{{ detail.product?.product_name }}</p>
                                <p v-if="detail.note" class="text-[11px] text-tertiary italic truncate">{{ detail.note }}</p>
                            </div>
                        </div>

                        <!-- Bàn -->
                        <span class="w-24 text-center text-[12px] text-on-surface-variant">
                            {{ detail.order?.table?.table_name || (detail.order?.order_type === 'DELIVERY' ? 'Giao hàng' : 'Mang đi') }}
                        </span>

                        <!-- Size -->
                        <span class="w-20 text-center text-[12px] font-bold text-on-surface">
                            {{ detail.variant?.size || '---' }}
                        </span>

                        <!-- Thời gian hoàn thành -->
                        <span class="w-20 text-right text-[12px] text-on-surface-variant font-mono">
                            {{ formatTime(detail.updated_at) }}
                        </span>
                    </li>
                </ul>

                <!-- Pagination -->
                <div v-if="history.links?.length > 3" class="p-4 bg-surface-container-lowest border-t border-outline-variant/20 flex items-center justify-center gap-1 font-sans">
                    <Component :is="link.url ? Link : 'span'" v-for="(link, index) in history.links" :key="index"
                        :href="link.url" v-html="link.label" :preserve-scroll="true"
                        :class="[
                            'px-3 py-1.5 text-label-medium rounded-lg transition-all',
                            link.active ? 'bg-secondary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high',
                            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                        ]" />
                </div>
            </div>
        </div>
    </BaristaLayout>
</template>
