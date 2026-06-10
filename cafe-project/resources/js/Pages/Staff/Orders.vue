<script setup>
import { Head } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';
</script>

<template>

    <Head title="Đơn hàng - Nắng Coffee" />

    <StaffLayout>
        <div class="mb-10">
            <p class="text-label-md text-primary tracking-wider mb-2">QUẢN LÝ</p>
            <h2 class="text-headline-md text-on-background">Đơn Hàng</h2>
        </div>

        <div
            class="bg-surface-container-lowest rounded-xl p-6 shadow-soft h-[500px] flex flex-col items-center justify-center border border-outline-variant/30">
            <span class="material-symbols-outlined text-outline text-[48px] mb-4">receipt_long</span>
            <p class="text-body-md text-outline">Bảng danh sách các đơn hàng sẽ được hiển thị tại đây.</p>
        </div>
    </StaffLayout>
</template>