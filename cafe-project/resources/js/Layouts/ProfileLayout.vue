<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'

const page = usePage()
const user = computed(() => page.props.user || null)

const tabs = [
    { key: 'info', label: 'Thông tin cá nhân', icon: 'person', route: 'profile.info' },
    { key: 'password', label: 'Đổi mật khẩu', icon: 'lock', route: 'profile.password' },
    { key: 'orders', label: 'Đơn hàng của tôi', icon: 'receipt_long', route: 'profile.orders' },
    { key: 'addresses', label: 'Địa chỉ giao hàng', icon: 'location_on', route: 'profile.addresses' },
    { key: 'vouchers', label: 'Ví voucher', icon: 'local_offer', route: 'profile.vouchers' },
]

const currentUrl = computed(() => page.url)

const activeTab = computed(() => {
    if (currentUrl.value.includes('/mat-khau')) return 'password'
    if (currentUrl.value.includes('/don-hang')) return 'orders'
    if (currentUrl.value.includes('/dia-chi')) return 'addresses'
    if (currentUrl.value.includes('/voucher')) return 'vouchers'


    return 'info'
})

const navigateTo = (tab) => {
    router.get(route(tab.route), {}, { preserveScroll: true })
}

const logout = () => {
    router.post('/logout', {}, {
        onFinish: () => window.location.href = '/'
    })
}
</script>

<template>
    <MainLayout>
        <div class="w-full">
            <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
                <AnimateOnScroll animation="fade-up" :duration="700">
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-primary-container/30 overflow-hidden border-2 border-primary/20 flex-shrink-0 flex items-center justify-center">
                                <img
                                    v-if="user?.avatar"
                                    :src="user.avatar"
                                    :alt="user?.full_name"
                                    class="w-full h-full object-cover"
                                />

                                <span
                                    v-else
                                    class="material-symbols-outlined text-3xl text-primary"
                                >
                                    person
                                </span>
                            </div>

                            <div>
                                <h1 class="font-serif text-headline-md text-primary">
                                    {{ user?.full_name || 'Người dùng' }}
                                </h1>

                                <p class="font-sans text-body-md text-on-surface-variant">
                                    {{ user?.email }}
                                </p>
                            </div>
                        </div>
                    </div>
                </AnimateOnScroll>

                <div class="flex flex-col md:flex-row gap-8">
                    <AnimateOnScroll
                        animation="fade-right"
                        :duration="700"
                        :delay="100"
                        class="md:w-64 flex-shrink-0"
                    >
                        <nav class="bg-surface rounded-xl border border-outline-variant/20 p-2 space-y-1 sticky top-24">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                @click="navigateTo(tab)"
                                :class="[
                                    'w-full flex items-center gap-3 px-4 py-3 rounded-lg font-sans text-label-md transition-all duration-200 text-left',
                                    activeTab === tab.key
                                        ? 'bg-primary-container/30 text-on-primary-container font-bold'
                                        : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary'
                                ]"
                            >
                                <span class="material-symbols-outlined text-lg">
                                    {{ tab.icon }}
                                </span>

                                {{ tab.label }}
                            </button>

                            <hr class="border-outline-variant/20 my-2" />

                            <button
                                @click="logout"
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-lg font-sans text-label-md transition-all duration-200 text-left text-error hover:bg-red-50"
                            >
                                <span class="material-symbols-outlined text-lg">
                                    logout
                                </span>

                                Đăng xuất
                            </button>
                        </nav>
                    </AnimateOnScroll>

                    <div class="flex-1 min-w-0">
                        <AnimateOnScroll
                            animation="fade-left"
                            :duration="700"
                            :delay="200"
                        >
                            <slot />
                        </AnimateOnScroll>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>