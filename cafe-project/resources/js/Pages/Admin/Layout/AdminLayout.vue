<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";

const page = usePage();
const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user || null);
const collapsed = ref(false);
const mobileOpen = ref(false);

const isActive = (path) => {
    if (path === "/quan-tri") return currentUrl.value === "/quan-tri";
    return currentUrl.value.startsWith(path);
};

const navigateTo = (href) => {
    router.visit(href);
    mobileOpen.value = false;
};

const menuGroups = [
    {
        label: "Tổng quan",
        icon: "dashboard",
        children: [
            {
                label: "Bảng điều khiển",
                href: "/quan-tri",
                icon: "space_dashboard",
            },
        ],
    },
    {
        label: "Quản lý bán hàng",
        icon: "storefront",
        children: [
            {
                label: "Đơn hàng",
                href: "/quan-tri/don-hang",
                icon: "receipt_long",
            },
            {
                label: "Đặt bàn",
                href: "/quan-tri/dat-ban",
                icon: "table_restaurant",
            },
            { label: "Bàn", href: "/admin/ban", icon: "table_bar" },
        ],
    },
    {
        label: "Quản lý sản phẩm",
        icon: "inventory_2",
        children: [
            { label: "Sản phẩm", href: "/quan-tri/san-pham", icon: "coffee" },
            { label: "Danh mục", href: "/quan-tri/danh-muc", icon: "category" },
            { label: "Biến thể", href: "/quan-tri/bien-the", icon: "tune" },
        ],
    },
    {
        label: "Nội dung",
        icon: "article",
        children: [
            { label: "Bài viết", href: "/quan-tri/bai-viet", icon: "post" },
            {
                label: "Danh mục bài viết",
                href: "/quan-tri/danh-muc-bai-viet",
                icon: "folder",
            },
            { label: "Bình luận", href: "/quan-tri/binh-luan", icon: "chat" },
        ],
    },
    {
        label: "Người dùng",
        icon: "group",
        children: [
            { label: "Khách hàng", href: "/quan-tri/khach-hang", icon: "people" },
            { label: "Nhân viên", href: "/quan-tri/nhan-vien", icon: "badge" },
        ],
    },
    {
        label: "Hệ thống",
        icon: "settings",
        children: [
            {
                label: "Voucher",
                href: "/quan-tri/voucher",
                icon: "confirmation_number",
            },
            { label: "Thương hiệu", href: "/quan-tri/thuong-hieu", icon: "store" },
        ],
    },
];

const bottomLinks = [{ label: "Về trang chủ", href: "/", icon: "home" }];
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-surface-container-low">
        <!-- Mobile overlay -->
        <div
            v-if="mobileOpen"
            @click="mobileOpen = false"
            class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 md:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed md:sticky top-0 left-0 z-50 flex flex-col h-screen bg-surface border-r border-outline-variant/20 transition-all duration-300 overflow-y-auto',
                collapsed ? 'w-20' : 'w-72',
                mobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full md:translate-x-0',
            ]"
        >
            <!-- Logo -->
            <div
                class="flex items-center h-16 px-4 border-b border-outline-variant/20 flex-shrink-0"
            >
                <button
                    @click="collapsed = !collapsed"
                    class="hidden md:flex p-1.5 hover:bg-surface-container-low rounded-lg transition-colors mr-2"
                >
                    <span
                        class="material-symbols-outlined text-on-surface-variant"
                        >menu</span
                    >
                </button>
                <div :class="collapsed ? 'hidden' : 'flex items-center gap-2'">
                    <img
                        src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1780996916/NangCoffee_logo_fullmau_wl8jbz.png"
                        alt="Logo"
                        class="w-8 h-8 rounded-full object-cover"
                    />
                    <span
                        class="font-serif text-headline-sm text-primary truncate"
                        >Nắng Coffee</span
                    >
                </div>
                <button
                    @click="mobileOpen = false"
                    class="md:hidden ml-auto p-1.5 hover:bg-surface-container-low rounded-lg"
                >
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 py-4 px-3 space-y-6">
                <div
                    v-for="group in menuGroups"
                    :key="group.label"
                    class="space-y-1"
                >
                    <p
                        v-if="!collapsed"
                        class="px-3 font-sans text-label-sm text-outline uppercase tracking-wider mb-2"
                    >
                        {{ group.label }}
                    </p>
                    <a
                        v-for="child in group.children"
                        :key="child.href"
                        :href="child.href"
                        @click.prevent="navigateTo(child.href)"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl font-sans text-label-md transition-all duration-200',
                            isActive(child.href)
                                ? 'bg-primary-container/30 text-on-primary-container font-bold'
                                : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary',
                        ]"
                        :title="collapsed ? child.label : ''"
                    >
                        <span
                            class="material-symbols-outlined text-xl flex-shrink-0"
                            >{{ child.icon }}</span
                        >
                        <span v-if="!collapsed" class="truncate">{{
                            child.label
                        }}</span>
                    </a>
                </div>
            </nav>

            <!-- Bottom -->
            <div
                class="border-t border-outline-variant/20 p-3 space-y-1 flex-shrink-0"
            >
                <a
                    v-for="link in bottomLinks"
                    :key="link.href"
                    :href="link.href"
                    @click.prevent="navigateTo(link.href)"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-sans text-label-md text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-all duration-200"
                    :title="collapsed ? link.label : ''"
                >
                    <span
                        class="material-symbols-outlined text-xl flex-shrink-0"
                        >{{ link.icon }}</span
                    >
                    <span v-if="!collapsed">{{ link.label }}</span>
                </a>
            </div>

            <!-- User -->
            <div
                v-if="user"
                class="border-t border-outline-variant/20 p-3 flex-shrink-0"
            >
                <div
                    class="flex items-center gap-3 px-2"
                    :class="collapsed ? 'justify-center' : ''"
                >
                    <div
                        class="w-9 h-9 rounded-full bg-primary-container/30 flex items-center justify-center flex-shrink-0"
                    >
                        <span class="material-symbols-outlined text-primary"
                            >person</span
                        >
                    </div>
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p
                            class="font-sans text-label-sm text-on-surface truncate"
                        >
                            {{ user.name }}
                        </p>
                        <p
                            class="font-sans text-label-sm text-outline truncate"
                        >
                            {{ user.email }}
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <header
                class="h-16 bg-surface border-b border-outline-variant/20 flex items-center justify-between px-4 md:px-6 flex-shrink-0"
            >
                <button
                    @click="mobileOpen = true"
                    class="md:hidden p-2 hover:bg-surface-container-low rounded-lg"
                >
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="flex items-center gap-3 ml-auto">
                    <button
                        class="p-2 hover:bg-surface-container-low rounded-full transition-colors relative"
                    >
                        <span
                            class="material-symbols-outlined text-on-surface-variant"
                            >notifications</span
                        >
                        <span
                            class="absolute top-1 right-1 w-2 h-2 bg-error rounded-full"
                        ></span>
                    </button>
                    <a
                        href="/dang-xuat"
                        @click.prevent="router.post('/logout')"
                        class="p-2 hover:bg-error-container/20 rounded-full transition-colors text-on-surface-variant hover:text-error"
                        title="Đăng xuất"
                    >
                        <span class="material-symbols-outlined">logout</span>
                    </a>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
