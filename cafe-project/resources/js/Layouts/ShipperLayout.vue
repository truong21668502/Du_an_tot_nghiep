<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const currentUrl = computed(() => usePage().url);
const isMobileMenuOpen = ref(false);

const navLinks = [
    { label: 'Đơn chờ giao', href: '/giao-hang', icon: 'local_shipping' },
    // Có thể thêm 'Lịch sử' sau
];

const isActiveLink = (path) => {
    if (path === '#') return false;
    return currentUrl.value.startsWith(path);
};

// Đồng hồ
const currentTime = ref('');
const currentHour = ref('');
let timeInterval = null;

const updateTime = () => {
    const now = new Date();
    currentTime.value = new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(now);
    currentHour.value = new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(now);
};

onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
});
onUnmounted(() => clearInterval(timeInterval));

router.on('navigate', () => {
    isMobileMenuOpen.value = false;
});

const props = defineProps({
    pendingCount: { type: Number, default: 0 },
});
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-background text-on-background antialiased">
        <!-- Sidebar -->
        <aside class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-surface-container-low shadow-soft py-6 z-40 border-r border-outline-variant/30">
            <div class="px-6 mb-6">
                <Link href="/giao-hang" class="text-headline-sm text-primary tracking-tight hover:opacity-80 transition-opacity font-serif font-bold">
                    Nắng Coffee
                </Link>
                <p class="text-label-sm text-on-surface-variant/70 mt-1">Shipper Panel</p>
            </div>

            <nav class="flex-1 flex flex-col gap-2 text-label-md px-2 overflow-y-auto hide-scrollbar">
                <Link v-for="link in navLinks" :key="link.label" :href="link.href" :class="[
                    'flex items-center gap-4 rounded-xl px-4 py-3 transition-all duration-200 group',
                    isActiveLink(link.href)
                        ? 'bg-primary-container text-on-primary-container font-bold'
                        : 'text-on-surface-variant hover:bg-surface-container-high hover:text-primary',
                ]">
                    <span :class="['material-symbols-outlined transition-colors duration-300', isActiveLink(link.href) ? 'icon-fill' : 'group-hover:text-primary']">
                        {{ link.icon }}
                    </span>
                    {{ link.label }}
                </Link>
            </nav>

            <div class="mt-auto flex flex-col gap-2 text-label-md px-2 pt-4 border-t border-outline-variant/30">
                <Link :href="route('logout')" method="post" as="button" class="flex items-center w-full text-left gap-4 text-on-surface-variant hover:bg-surface-container-high rounded-xl px-4 py-3 hover:text-error transition-colors duration-300 group">
                    <span class="material-symbols-outlined group-hover:text-error transition-colors duration-300">logout</span>
                    Đăng xuất
                </Link>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex-1 md:ml-64 flex flex-col h-screen overflow-hidden">
            <!-- Topbar -->
            <header class="h-16 bg-surface/90 backdrop-blur-md border-b border-outline-variant/20 flex items-center justify-between px-4 md:px-6 sticky top-0 z-30">
                <div class="flex items-center gap-4">
                    <button class="md:hidden p-2 text-primary hover:bg-primary-container/20 rounded-xl transition-colors" @click="isMobileMenuOpen = !isMobileMenuOpen">
                        <span class="material-symbols-outlined text-[22px]">{{ isMobileMenuOpen ? 'close' : 'menu' }}</span>
                    </button>
                    <div class="hidden md:flex items-center gap-3">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-[22px] font-bold text-on-surface tabular-nums tracking-tight leading-none">{{ currentHour }}</span>
                        </div>
                        <div class="w-px h-6 bg-outline-variant/40"></div>
                        <div>
                            <p class="text-[13px] text-on-surface-variant capitalize">{{ currentTime }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-px h-6 bg-outline-variant/30"></div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl overflow-hidden ring-2 ring-primary/20 bg-primary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-[18px]">local_shipping</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-[14px] font-bold text-on-surface leading-none">Shipper</p>
                            <p class="text-[12px] text-on-surface-variant mt-0.5">Giao hàng</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile menu -->
            <Transition name="slide-down">
                <div v-if="isMobileMenuOpen" class="md:hidden absolute top-16 left-0 w-full bg-surface border-b border-outline-variant/30 px-4 py-4 shadow-lg z-20">
                    <Link v-for="link in navLinks" :key="link.label" :href="link.href" :class="[
                        'flex items-center gap-4 py-3.5 px-4 rounded-xl text-label-md transition-all duration-200',
                        isActiveLink(link.href)
                            ? 'text-primary font-bold bg-primary-container/20'
                            : 'text-on-surface-variant hover:text-primary hover:bg-surface-container',
                    ]">
                        <span :class="['material-symbols-outlined', isActiveLink(link.href) ? 'icon-fill text-primary' : '']">{{ link.icon }}</span>
                        {{ link.label }}
                    </Link>
                </div>
            </Transition>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap");
.icon-fill {
    font-variation-settings: 'FILL' 1;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.2s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>