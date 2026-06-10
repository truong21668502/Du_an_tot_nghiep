<script setup>
import { Head } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import StatCards from './Partials/StatCards.vue';
</script>

<template>
<Head title="Bảng điều khiển - Nắng Coffee" />

<StaffLayout>
    <div class="mb-10">
        <p class="text-label-md text-primary tracking-wider mb-2">NẮNG COFFEE</p>
        <h2 class="text-headline-md text-on-background">Bảng điều khiển</h2>
    </div>

    <StatCards />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <section class="lg:col-span-2">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-headline-sm text-on-background">Đơn Đang Chờ</h3>
                <button class="text-label-md text-primary hover:text-secondary transition-colors">Xem tất cả</button>
            </div>
            
            <div class="flex flex-col gap-4">
                <div class="bg-surface-container-lowest rounded-xl p-5 shadow-soft flex items-center justify-between border-l-4 border-primary">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-primary text-headline-sm">
                            #42
                        </div>
                        <div>
                            <h4 class="text-label-md text-on-surface">Bàn 04 • 2 món</h4>
                            <p class="text-body-md text-on-surface-variant mt-1">1x Pour Over, 1x Bánh sừng bò</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="text-label-sm text-error bg-error-container text-on-error-container px-3 py-1 rounded-full">5 phút trước</span>
                        <button class="text-label-md text-primary hover:underline">Chi tiết</button>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-xl p-5 shadow-soft flex items-center justify-between border-l-4 border-secondary-container">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-secondary text-headline-sm">
                            #43
                        </div>
                        <div>
                            <h4 class="text-label-md text-on-surface">Mang đi • 1 món</h4>
                            <p class="text-body-md text-on-surface-variant mt-1">1x Cold Brew</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="text-label-sm text-on-tertiary-container bg-tertiary-container px-3 py-1 rounded-full">Vừa xong</span>
                        <button class="text-label-md text-primary hover:underline">Chi tiết</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="lg:col-span-1">
            <h3 class="text-headline-sm text-on-background mb-6">Trạng Thái Bàn</h3>
            <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft">
                <div class="grid grid-cols-3 gap-4">
                    <div class="aspect-square rounded-lg border border-primary bg-primary-container/20 flex flex-col items-center justify-center cursor-pointer hover:bg-primary-container/40 transition-colors">
                        <span class="text-headline-sm text-primary">01</span>
                        <span class="text-label-sm text-primary/70">Có khách</span>
                    </div>
                    <div class="aspect-square rounded-lg bg-surface-container flex flex-col items-center justify-center cursor-pointer hover:bg-surface-container-high transition-colors">
                        <span class="text-headline-sm text-on-surface-variant">02</span>
                        <span class="text-label-sm text-on-surface-variant/70">Trống</span>
                    </div>
                    <div class="aspect-square rounded-lg bg-surface-container flex flex-col items-center justify-center cursor-pointer hover:bg-surface-container-high transition-colors">
                        <span class="text-headline-sm text-on-surface-variant">03</span>
                        <span class="text-label-sm text-on-surface-variant/70">Trống</span>
                    </div>
                    <div class="aspect-square rounded-lg border border-primary bg-primary-container/20 flex flex-col items-center justify-center cursor-pointer hover:bg-primary-container/40 transition-colors">
                        <span class="text-headline-sm text-primary">04</span>
                        <span class="text-label-sm text-primary/70">Có khách</span>
                    </div>
                    <div class="aspect-square rounded-lg bg-secondary-container flex flex-col items-center justify-center cursor-pointer hover:bg-secondary-container/80 transition-colors">
                        <span class="text-headline-sm text-on-secondary-container">05</span>
                        <span class="text-label-sm text-on-secondary-container/70">Đặt trước</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</StaffLayout>
</template>