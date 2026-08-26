<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ChatBox from '@/Components/Chat/ChatBox.vue'

const emit = defineEmits(['callback'])

const page = usePage()

const shopInfo = computed(() => page.props.settings?.shop_info ?? {})
const websiteInfo = computed(() => page.props.settings?.website ?? {})

/* ------------------------------------------------------------------ */
/* Existing hover-menu state (UNCHANGED behavior)                      */
/* ------------------------------------------------------------------ */
const isOpen = ref(false)
const showChat = ref(false)

// Add ref for click-outside detection
const contactContainer = ref(null)

let closeTimer = null

const openMenu = () => {
    if (closeTimer) {
        clearTimeout(closeTimer)
        closeTimer = null
    }
    isOpen.value = true
}

const closeMenu = () => {
    if (closeTimer) clearTimeout(closeTimer)
    closeTimer = setTimeout(() => {
        isOpen.value = false
    }, 150)
}

const toggleMenu = () => {
    isOpen.value ? (isOpen.value = false) : openMenu()
}

const openZalo = () => {
    if (!websiteInfo.value.social_zalo) return
    window.open(websiteInfo.value.social_zalo, '_blank', 'noopener,noreferrer')
    isOpen.value = false
}

const openFacebook = () => {
    if (!websiteInfo.value.social_facebook) return
    window.open(websiteInfo.value.social_facebook, '_blank', 'noopener,noreferrer')
    isOpen.value = false
}

const callPhone = () => {
    const hotline = shopInfo.value.shop_hotline
    if (!hotline) return
    const phone = hotline.replace(/\s+/g, '').replace(/[^\d+]/g, '')
    window.location.href = `tel:${phone}`
    isOpen.value = false
}

const requestCallback = () => {
    isOpen.value = false
    emit('callback')
}

const openChat = () => {
    isOpen.value = false
    showChat.value = true
}

const hasHotline = computed(() => Boolean(shopInfo.value.shop_hotline))
const hasZalo = computed(() => Boolean(websiteInfo.value.social_zalo))
const hasFacebook = computed(() => Boolean(websiteInfo.value.social_facebook))

/* ------------------------------------------------------------------ */
/* NEW: mini action carousel + synced speech bubble                    */
/* ------------------------------------------------------------------ */
const prefersReducedMotion = ref(false)

const allActions = computed(() => [
    {
        id: 'chat',
        icon: 'chat',
        label: 'Chat với trợ lý',
        message: 'Xin chào, tôi có thể giúp gì cho bạn?',
        run: openChat,
        available: true,
    },
    // {
    //     id: 'callback',
    //     icon: 'support_agent',
    //     label: 'Yêu cầu gọi lại',
    //     message: 'Bạn cần hỗ trợ gì không?',
    //     run: requestCallback,
    //     available: true,
    // },
    {
        id: 'zalo',
        icon: 'zalo',
        label: 'Zalo Chat',
        message: 'Chat với chúng tôi qua Zalo',
        run: openZalo,
        available: hasZalo.value,
    },
    {
        id: 'hotline',
        icon: 'call',
        label: 'Gọi cho chúng tôi',
        message: `Gọi ngay ${shopInfo.value.shop_hotline ?? ''}`.trim(),
        run: callPhone,
        available: hasHotline.value,
    }
].filter(a => a.available))

const activeIndex = ref(0)
const currentAction = computed(() => allActions.value[activeIndex.value] ?? allActions.value[0])

let carouselTimer = null
const AUTO_DELAY = 3000

const nextAction = () => {
    if (!allActions.value.length) return
    activeIndex.value = (activeIndex.value + 1) % allActions.value.length
}

const startCarousel = () => {
    stopCarousel()
    if (prefersReducedMotion.value) return
    if (allActions.value.length <= 1) return
    carouselTimer = setInterval(nextAction, AUTO_DELAY)
}

const stopCarousel = () => {
    if (carouselTimer) {
        clearInterval(carouselTimer)
        carouselTimer = null
    }
}

watch(isOpen, (open) => {
    if (open) {
        stopCarousel()
    } else {
        startCarousel()
    }
})

watch(allActions, (list) => {
    if (activeIndex.value >= list.length) activeIndex.value = 0
})

const runCurrentAction = () => {
    currentAction.value?.run?.()
}

const currentWords = computed(() => (currentAction.value?.message ?? '').split(' ').filter(Boolean))
const WORD_STEP_MS = 70

/* Click-outside handler */
const handleClickOutside = (event) => {
    if (!isOpen.value) return
    if (contactContainer.value && !contactContainer.value.contains(event.target)) {
        isOpen.value = false
    }
}

onMounted(() => {
    prefersReducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    document.addEventListener('click', handleClickOutside)
    startCarousel()
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    if (closeTimer) clearTimeout(closeTimer)
    stopCarousel()
})
</script>

<template>
    <div
        class="fixed bottom-6 right-4 md:right-6 z-[60] flex items-end gap-3"
    >
        <!-- SPEECH BUBBLE -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-x-2 scale-95"
            enter-to-class="opacity-100 translate-x-0 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-x-0 scale-100"
            leave-to-class="opacity-0 translate-x-2 scale-95"
        >
            <button
                v-if="!isOpen && currentAction"
                type="button"
                class="hidden sm:flex mb-2 max-w-60 md:max-w-70 items-center bg-white rounded-2xl rounded-br-sm shadow-[0_6px_24px_rgba(0,0,0,0.14)] border border-gray-100 px-4 py-3 text-left hover:shadow-[0_8px_28px_rgba(0,0,0,0.2)] transition-shadow"
                :aria-label="`${currentAction.label}: ${currentAction.message}`"
                @click="runCurrentAction"
            >
                <Transition
                    :key="currentAction.id"
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 -translate-x-1"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-100 ease-in absolute"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                    mode="out-in"
                >
                    <p :key="currentAction.id" class="text-sm text-gray-700 leading-snug" aria-live="polite">
                        <span
                            v-for="(word, i) in currentWords"
                            :key="i"
                            class="inline-block word-reveal"
                            :style="prefersReducedMotion ? {} : { animationDelay: `${i * WORD_STEP_MS}ms` }"
                        >{{ word }}&nbsp;</span>
                    </p>
                </Transition>
            </button>
        </Transition>

        <!-- ref container for click-outside -->
        <div class="relative" ref="contactContainer">
            <!-- CONTACT MENU -->
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 translate-y-3 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-3 scale-95"
            >
                <div v-if="isOpen" class="absolute right-0 bottom-[82px] w-[290px]">
                    <div class="relative bg-white rounded-xl shadow-[0_8px_30px_rgba(0,0,0,0.18)] border border-gray-100 overflow-hidden p-2">

                        <!-- Yêu cầu gọi lại -->
                        <!-- <button
                            type="button"
                            class="cursor-pointer group w-full flex items-center gap-4 px-3 py-3 rounded-lg text-left hover:bg-gray-50 transition-colors"
                            @click="requestCallback"
                        >
                            <span class="flex items-center justify-center shrink-0 w-11 h-11 rounded-full bg-primary text-white shadow-sm group-hover:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[22px]">support_agent</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-800">Yêu cầu gọi lại</span>
                                <span class="block text-xs text-gray-400 mt-0.5">Nhân viên sẽ liên hệ với bạn</span>
                            </span>
                        </button> -->

                        <!-- ZALO -->
                        <button
                            v-if="hasZalo"
                            type="button"
                            class="group cursor-pointer w-full flex items-center gap-4 px-3 py-3 rounded-lg text-left hover:bg-gray-50 transition-colors"
                            @click="openZalo"
                        >
                            <span class="flex items-center justify-center shrink-0 w-11 h-11 rounded-full bg-primary text-white shadow-sm group-hover:scale-105 transition-transform">
                                <img class="w-8 h-8" src="https://img.icons8.com/?size=100&id=5kIaloPsNy4L&format=png&color=FFFFFF" alt="zalo"/>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-800">Zalo Chat</span>
                                <span class="block text-xs text-gray-400 mt-0.5">Chat trực tiếp qua Zalo</span>
                            </span>
                        </button>

                        <!-- GỌI ĐIỆN -->
                        <button
                            v-if="hasHotline"
                            type="button"
                            class="group cursor-pointer w-full flex items-center gap-4 px-3 py-3 rounded-lg text-left hover:bg-gray-50 transition-colors"
                            @click="callPhone"
                        >
                            <span class="flex items-center justify-center shrink-0 w-11 h-11 rounded-full bg-primary text-white shadow-sm group-hover:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[22px]">call</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-800">Gọi cho chúng tôi</span>
                                <span class="block text-xs text-gray-400 mt-0.5">{{ shopInfo.shop_hotline }}</span>
                            </span>
                        </button>

                        <!-- CHATBOX -->
                        <button
                            type="button"
                            class="group cursor-pointer w-full flex items-center gap-4 px-3 py-3 rounded-lg text-left hover:bg-gray-50 transition-colors"
                            @click="openChat"
                        >
                            <span class="flex items-center justify-center shrink-0 w-11 h-11 rounded-full bg-primary text-white shadow-sm group-hover:scale-105 transition-transform">
                                <img class="w-8 h-8 rounded-full" src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg" alt="chatbox"/>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-800">Chat với trợ lý</span>
                                <span class="block text-xs text-gray-400 mt-0.5">Hỏi đáp và tư vấn tự động</span>
                            </span>
                        </button>

                        <span class="absolute -bottom-[7px] right-7 w-4 h-4 bg-white rotate-45 border-r border-b border-gray-100" />
                    </div>
                </div>
            </Transition>

            <!-- FLOATING BUTTON with mini icon carousel (duration reduced) -->
            <button
                type="button"
                aria-label="Mở menu liên hệ"
                :aria-expanded="isOpen"
                class="relative cursor-pointer flex items-center justify-center w-[62px] h-[62px] rounded-full bg-primary text-white shadow-[0_5px_20px_rgba(0,0,0,0.2)] hover:shadow-[0_8px_28px_rgba(0,0,0,0.28)] hover:scale-105 transition-all duration-200 overflow-hidden focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                @click.stop="toggleMenu"
            >
                <span v-if="isOpen" class="material-symbols-outlined text-[28px] rotate-90 transition-transform duration-200">close</span>

                <Transition
                    v-else
                    :key="currentAction?.id"
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-x-3"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-200 ease-in absolute"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-3"
                    mode="out-in"
                >
                    <span :key="currentAction?.id" class="flex items-center justify-center">
                        <span v-if="currentAction?.icon === 'zalo'" class="flex items-center justify-center shrink-0 w-11 h-11 rounded-full bg-primary text-white shadow-sm group-hover:scale-105 transition-transform">
                            <img class="w-8 h-8" src="https://img.icons8.com/?size=100&id=5kIaloPsNy4L&format=png&color=FFFFFF" alt="zalo"/>
                        </span>
                        <span v-else-if="currentAction?.icon === 'chat'" class="flex items-center justify-center shrink-0 w-11 h-11 rounded-full bg-primary text-white shadow-sm group-hover:scale-105 transition-transform">
                            <img class="w-10 h-10 rounded-full" src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg" alt="chatbox"/>
                        </span>
                        <svg v-else-if="currentAction?.icon === 'facebook'" viewBox="0 0 24 24" class="w-8 h-8" fill="currentColor" aria-hidden="true">
                            <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94z"/>
                        </svg>
                        <span v-else class="material-symbols-outlined text-[28px]">{{ currentAction?.icon ?? 'forum' }}</span>
                    </span>
                </Transition>
            </button>
        </div>
    </div>

    <!-- CHATBOX -->
    <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 translate-y-4 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-4 scale-95"
    >
        <ChatBox
            v-if="showChat"
            class="fixed bottom-24 right-4 md:right-6 z-[70]"
            @close="showChat = false"
        />
    </Transition>
</template>

<style scoped>
.word-reveal {
    animation: wordIn 260ms ease-out both;
}

@keyframes wordIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .word-reveal {
        animation: none !important;
    }
}
</style>