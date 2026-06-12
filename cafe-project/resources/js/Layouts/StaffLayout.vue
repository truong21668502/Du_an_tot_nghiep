<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

const isMobileMenuOpen = ref(false)
const currentUrl = computed(() => usePage().url)

// Navigation links
const navLinks = [
    { label: 'Tổng quan', href: '/nhan-vien/bang-dieu-khien', icon: 'dashboard' },
    { label: 'Đơn hàng', href: '/nhan-vien/don-hang', icon: 'coffee_maker' },
    { label: 'Đặt bàn', href: '/nhan-vien/dat-ban', icon: 'table_restaurant' },
]

// Kiểm tra link active
const isActiveLink = (path) => {
    if (path === '#') return false;
    return currentUrl.value.startsWith(path);
}

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// Đóng mobile menu khi chuyển trang
router.on('navigate', () => {
    isMobileMenuOpen.value = false
})
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-background text-on-background antialiased">

        <aside
            class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-surface-container-low shadow-soft py-6 z-40 border-r border-outline-variant/30">
            <div class="px-6 mb-8">
                <Link href="/nhan-vien/bang-dieu-khien"
                    class="text-headline-sm text-primary tracking-tight hover:opacity-80 transition-opacity">
                    Nắng Coffee
                </Link>
                <p class="text-label-sm text-on-surface-variant/70 mt-1">Staff Panel</p>
            </div>

            <div class="px-6 mb-8">
                <button
                    class="w-full bg-primary text-on-primary text-label-md py-3 rounded-full shadow-soft hover:opacity-90 transition-opacity">
                    Tạo đơn mới
                </button>
            </div>

            <nav class="flex-1 flex flex-col gap-2 text-label-md px-2">
                <Link v-for="link in navLinks" :key="link.label" :href="link.href" :class="[
                    'flex items-center gap-4 rounded-xl px-4 py-3 transition-all duration-200 group',
                    isActiveLink(link.href)
                        ? 'bg-primary-container text-on-primary-container'
                        : 'text-on-surface-variant hover:bg-surface-container-high hover:text-primary'
                ]">
                    <span
                        :class="['material-symbols-outlined transition-colors duration-300', isActiveLink(link.href) ? 'icon-fill' : 'group-hover:text-primary']">
                        {{ link.icon }}
                    </span>
                    {{ link.label }}
                </Link>
            </nav>

            <div class="mt-auto flex flex-col gap-2 text-label-md px-2">
                <a href="#"
                    class="flex items-center gap-4 text-on-surface-variant hover:bg-surface-container-high rounded-xl px-4 py-3 hover:text-primary transition-colors duration-300 group">
                    <span
                        class="material-symbols-outlined group-hover:text-primary transition-colors duration-300">settings</span>
                    Cài đặt
                </a>
                <Link :href="route('logout')" method="post" as="button" class="flex items-center w-full text-left gap-4 text-on-surface-variant hover:bg-surface-container-high rounded-xl px-4 py-3 hover:text-error transition-colors duration-300 group">
                <span class="material-symbols-outlined group-hover:text-error transition-colors duration-300">logout</span>
                Đăng xuất
                </Link>
            </div>
        </aside>

        <div class="flex-1 md:ml-64 flex flex-col h-screen overflow-hidden">

            <header
                class="h-20 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 flex items-center justify-between px-margin-mobile md:px-margin-desktop sticky top-0 z-30">
                <div class="md:hidden">
                    <button @click="toggleMobileMenu"
                        class="p-2 text-primary hover:bg-primary-container/20 rounded-full transition-colors">
                        <span class="material-symbols-outlined">{{ isMobileMenuOpen ? 'close' : 'menu' }}</span>
                    </button>
                </div>

                <div class="hidden md:block">
                    <p class="text-body-md text-on-surface-variant">Thứ Năm, 24 Tháng 10, 2026</p>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        class="w-10 h-10 rounded-full text-on-surface-variant hover:text-primary hover:bg-primary-container/20 flex items-center justify-center transition-all relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <span class="absolute top-1 right-2 w-2 h-2 bg-error rounded-full"></span>
                    </button>
                    <div class="flex items-center gap-3 pl-4 border-l border-outline-variant/30">
                        <div class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold font-serif">
                            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1780751292/N%E1%BA%AFng_coffee_tbphoj.jpg" alt="Avatar" class="w-full h-full object-cover rounded-full">
                        </div>
                        <span class="text-label-md text-on-surface hidden sm:block">Staff</span>
                    </div>
                </div>
            </header>

            <Transition name="slide-down">
                <div v-if="isMobileMenuOpen"
                    class="md:hidden absolute top-20 left-0 w-full bg-surface border-b border-outline-variant/30 px-margin-mobile py-4 shadow-lg z-20">
                    <Link v-for="link in navLinks" :key="link.label" :href="link.href" :class="[
                        'flex items-center gap-4 py-4 px-4 rounded-lg text-body-md transition-all duration-200',
                        isActiveLink(link.href)
                            ? 'text-primary font-bold bg-primary-container/20'
                            : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low'
                    ]">
                        <span class="material-symbols-outlined">{{ link.icon }}</span>
                        {{ link.label }}
                    </Link>
                    <div class="mt-4 pt-4 border-t border-outline-variant/30 space-y-3">
                        <button
                            class="flex items-center gap-4 py-3 px-4 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-all w-full text-left">
                            <span class="material-symbols-outlined">settings</span>
                            <span class="text-body-md">Cài đặt</span>
                        </button>
                        <Link :href="route('logout')" method="post" as="button" class="flex items-center gap-4 py-3 px-4 rounded-lg text-error hover:bg-error-container/50 transition-all w-full text-left">
                        <span class="material-symbols-outlined">logout</span>
                        <span class="text-body-md">Đăng xuất</span>
                        </Link>
                    </div>
                </div>
            </Transition>

            <main class="flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop hide-scrollbar">
                <slot />
            </main>

        </div>
    </div>
</template>

<style scoped>
.slide-down-enter-active {
    transition: all 0.3s ease-out;
}

.slide-down-leave-active {
    transition: all 0.2s ease-in;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>