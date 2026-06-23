<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="bg-background text-on-background font-body-md min-h-screen flex flex-col md:flex-row">
        
        <header class="md:hidden flex justify-between items-center w-full px-5 py-4 bg-surface/60 backdrop-blur-xl sticky top-0 z-50 shadow-sm">
            <div class="font-headline-lg text-headline-lg font-bold text-primary">Nắng Coffee</div>
            <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-primary text-2xl">account_circle</span>
            </div>
        </header>

        <nav class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-surface-container-low py-6 shadow-soft z-40 border-r border-outline-variant/30">
            <div class="px-8 mb-10">
                <h1 class="font-headline-md text-headline-md text-primary font-bold">Nắng Coffee</h1>
                <p class="font-label-md text-label-md text-on-surface-variant mt-1 tracking-wider uppercase">Barista Station</p>
            </div>
            
            <div class="flex-1 overflow-y-auto space-y-2 px-4">
                <Link :href="route('barista.queue')" class="flex items-center gap-4 bg-primary-container text-on-primary-container rounded-xl px-4 py-3 transition-all font-bold shadow-sm">
                    <span class="material-symbols-outlined icon-fill">coffee_maker</span>
                    <span class="font-label-md text-label-md">Đơn Chờ Pha</span>
                </Link>
                <Link :href="route('barista.dashboard')" class="flex items-center gap-4 text-on-surface-variant hover:bg-surface-variant/50 rounded-xl px-4 py-3 transition-all font-medium">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span class="font-label-md text-label-md">Nguyên Liệu</span>
                </Link>
            </div>

            <div class="mt-auto space-y-2 pt-10 px-4">
                <a href="#" class="flex items-center gap-4 text-on-surface-variant hover:bg-surface-variant/50 rounded-xl px-4 py-3 transition-all font-medium">
                    <span class="material-symbols-outlined">settings</span>
                    <span class="font-label-md text-label-md">Cài đặt</span>
                </a>
                <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-4 text-error hover:bg-error-container/30 rounded-xl px-4 py-3 transition-all font-bold text-left">
                    <span class="material-symbols-outlined">logout</span>
                    <span class="font-label-md text-label-md">Đăng xuất</span>
                </Link>
            </div>
        </nav>

        <main class="flex-1 md:ml-64 p-5 md:p-10 min-h-screen relative">
            <div class="w-full">
                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
.icon-fill { font-variation-settings: 'FILL' 1; }
</style>