<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import BaristaAiChatWidget from '@/Pages/Barista/Layout/BaristaAiChatWidget.vue';

const currentUrl = computed(() => usePage().url);
const isMobileMenuOpen = ref(false);

// Nav links cho barista
const navLinks = [
    { label: 'Hàng đợi', href: '/pha-che/hang-doi', icon: 'coffee_maker' },
    { label: 'Lịch sử', href: '/pha-che/lich-su', icon: 'history' },
];

const isActiveLink = (path) => {
    if (path === '#') return false;
    return currentUrl.value.startsWith(path);
};

// Đồng hồ thực — đồng nhất với StaffLayout
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

// Đóng mobile menu khi chuyển trang
router.on('navigate', () => { isMobileMenuOpen.value = false; });

// Badge số món đang chờ
const props = defineProps({
    pendingCount: { type: Number, default: 0 },
});
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-background text-on-background antialiased">

        <!-- Sidebar (desktop) — giống hệt stafflayout -->
        <aside
            class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-surface-container-low shadow-soft py-6 z-40 border-r border-outline-variant/30">

            <!-- Logo -->
            <div class="px-6 mb-6">
                <Link href="/pha-che/hang-doi"
                    class="text-headline-sm text-primary tracking-tight hover:opacity-80 transition-opacity font-serif font-bold">
                    Nắng Coffee
                </Link>
                <p class="text-label-sm text-on-surface-variant/70 mt-1">Barista Station</p>
            </div>

            <!-- Nav links -->
            <nav class="flex-1 flex flex-col gap-2 text-label-md px-2 overflow-y-auto hide-scrollbar">
                <Link v-for="link in navLinks" :key="link.label" :href="link.href" :class="[
                    'flex items-center gap-4 rounded-xl px-4 py-3 transition-all duration-200 group relative',
                    isActiveLink(link.href)
                        ? 'bg-primary-container text-on-primary-container font-bold'
                        : 'text-on-surface-variant hover:bg-surface-container-high hover:text-primary',
                ]">
                    <span
                        :class="['material-symbols-outlined transition-colors duration-300', isActiveLink(link.href) ? 'icon-fill' : 'group-hover:text-primary']">
                        {{ link.icon }}
                    </span>
                    {{ link.label }}
                    <!-- Badge đang chờ -->
                    <span v-if="link.icon === 'coffee_maker' && pendingCount > 0"
                        class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-error text-white min-w-[20px] text-center">
                        {{ pendingCount > 99 ? '99+' : pendingCount }}
                    </span>
                </Link>
            </nav>

            <!-- Footer: settings + logout -->
            <div class="mt-auto flex flex-col gap-2 text-label-md px-2 pt-4 border-t border-outline-variant/30">
                <Link :href="route('logout')" method="post" as="button"
                    class="flex items-center w-full text-left gap-4 text-on-surface-variant hover:bg-surface-container-high rounded-xl px-4 py-3 hover:text-error transition-colors duration-300 group">
                    <span
                        class="material-symbols-outlined group-hover:text-error transition-colors duration-300">logout</span>
                    Đăng xuất
                </Link>
            </div>
        </aside>

        <!-- Right: main area (topbar + content) -->
        <div class="flex-1 md:ml-64 flex flex-col h-screen overflow-hidden">

            <!-- Topbar — giống hệt stafflayout -->
            <header
                class="h-16 bg-surface/90 backdrop-blur-md border-b border-outline-variant/20 flex items-center justify-between px-4 md:px-6 sticky top-0 z-30">

                <!-- Left: Mobile menu + Datetime -->
                <div class="flex items-center gap-4">
                    <button
                        class="md:hidden p-2 text-primary hover:bg-primary-container/20 rounded-xl transition-colors"
                        @click="isMobileMenuOpen = !isMobileMenuOpen">
                        <span class="material-symbols-outlined text-[22px]">{{ isMobileMenuOpen ? 'close' : 'menu'
                            }}</span>
                    </button>
                    <div class="hidden md:flex items-center gap-3">
                        <!-- Live clock -->
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="text-[22px] font-bold text-on-surface tabular-nums tracking-tight leading-none">{{
                                currentHour }}</span>
                        </div>
                        <div class="w-px h-6 bg-outline-variant/40"></div>
                        <div>
                            <p class="text-[13px] text-on-surface-variant capitalize">{{ currentTime }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Badge + Avatar -->
                <div class="flex items-center gap-3">
                    <!-- Live badge -->
                    <div v-if="pendingCount > 0"
                        class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-error/10 border border-error/20 text-error text-[11px] font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                        {{ pendingCount }} chờ pha
                    </div>


                    <!-- User -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl overflow-hidden ring-2 ring-primary/20">
                            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1780751292/N%E1%BA%AFng_coffee_tbphoj.jpg" alt="Avatar" class="w-full h-full object-cover">
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-[14px] font-bold text-on-surface leading-none">Barista</p>
                            <p class="text-[12px] text-on-surface-variant mt-0.5">Pha chế</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile menu dropdown -->
            <Transition name="slide-down">
                <div v-if="isMobileMenuOpen"
                    class="md:hidden absolute top-16 left-0 w-full bg-surface border-b border-outline-variant/30 px-4 py-4 shadow-lg z-20">
                    <Link v-for="link in navLinks" :key="link.label" :href="link.href" :class="[
                        'flex items-center gap-4 py-3.5 px-4 rounded-xl text-label-md transition-all duration-200',
                        isActiveLink(link.href)
                            ? 'text-primary font-bold bg-primary-container/20'
                            : 'text-on-surface-variant hover:text-primary hover:bg-surface-container',
                    ]">
                        <span
                            :class="['material-symbols-outlined', isActiveLink(link.href) ? 'icon-fill text-primary' : '']">{{
                            link.icon }}</span>
                        {{ link.label }}
                    </Link>
                </div>
            </Transition>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
        
        <!-- Nhúng Chatbot AI cho Barista -->
        <BaristaAiChatWidget />
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