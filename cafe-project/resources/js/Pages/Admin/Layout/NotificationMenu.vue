<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const isLoading = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);
const isAudioUnlocked = ref(false);

let notificationAudio = null;

// Unlock Audio khi người dùng tương tác lần đầu
const unlockAudio = () => {
    if (notificationAudio && !isAudioUnlocked.value) {
        notificationAudio.play().then(() => {
            notificationAudio.pause();
            notificationAudio.currentTime = 0;
            isAudioUnlocked.value = true;
            window.removeEventListener('click', unlockAudio);
        }).catch(() => {});
    }
};

onMounted(() => {
    notificationAudio = new Audio('/sounds/notification.mp3');
    notificationAudio.load();

    window.addEventListener('click', unlockAudio);

    // Lần đầu mount component (do chuyển trang hoặc load lại)
    // Chuyển tham số isInterval = false để KHÔNG kêu
    fetchNotifications(false);

    // Chu kỳ quét ngầm 30s/lần
    // Chuyển tham số isInterval = true để KÊU NHẮC LẠI nếu vẫn còn thông báo chưa đọc
    const interval = setInterval(() => {
        fetchNotifications(true);
    }, 1000000);

    onUnmounted(() => {
        clearInterval(interval);
        window.removeEventListener('click', unlockAudio);
    });
});

const playNotificationSound = () => {
    if (!notificationAudio) return;

    notificationAudio.currentTime = 0;
    const playPromise = notificationAudio.play();
    
    if (playPromise !== undefined) {
        playPromise.catch((error) => {
            console.warn("Trình duyệt chặn autoplay âm thanh:", error);
        });
    }
};

/**
 * @param {boolean} isInterval - true nếu là quét ngầm 30s, false nếu là chuyển trang/bấm chuông
 */
const fetchNotifications = async (isInterval = false) => {
    isLoading.value = true;
    try {
        const res = await axios.get('/quan-tri/api/notifications');
        const newUnreadCount = res.data.unread_count || 0;

        // LOGIC PHÁT CHUÔNG:
        // Chỉ phát âm thanh trong các chu kỳ quét ngầm (isInterval = true) VÀ còn thông báo chưa đọc (> 0)
        if (isInterval && newUnreadCount > 0) {
            playNotificationSound();
        }

        notifications.value = res.data.notifications || [];
        unreadCount.value = newUnreadCount;

    } catch (err) {
        console.error("Lỗi lấy thông báo:", err);
    } finally {
        isLoading.value = false;
    }
};

const toggleNotification = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications(false); // Bấm vào chuông xem thì không cần kêu
    }
};
</script>

<template>
    <div class="relative">
        <!-- Nút Icon Quả Chuông -->
        <button 
            @click="toggleNotification" 
            class="relative p-2 rounded-full hover:bg-surface-container-high transition-colors focus:outline-none"
            title="Thông báo"
        >
            <svg class="w-6 h-6 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <span 
                v-if="unreadCount > 0" 
                class="absolute top-1 right-1 bg-error text-on-error text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center animate-bounce"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Danh Sách Thông Báo -->
        <div 
            v-if="isOpen" 
            class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/30 z-50 overflow-hidden"
        >
            <div class="p-3 px-4 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-low">
                <h4 class="font-bold text-title-small text-on-surface">Thông báo mới</h4>
                <button 
                    @click="playNotificationSound" 
                    class="text-[11px] text-primary underline hover:opacity-80"
                >
                    Test chuông 🔔
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-outline-variant/10">
                <div v-if="isLoading" class="p-6 text-center text-outline text-body-small animate-pulse">
                    Đang tải thông báo...
                </div>

                <div v-else-if="notifications.length === 0" class="p-6 text-center text-outline text-body-small">
                    🎉 Không có thông báo nào !
                </div>

                <a 
                    v-else 
                    v-for="item in notifications" 
                    :key="item.id"
                    :href="item.link"
                    class="block p-3 hover:bg-surface-container-high transition-colors flex gap-3 items-start"
                >
                    <span 
                        class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0"
                        :class="{
                            'bg-error': item.type === 'danger',
                            'bg-tertiary': item.type === 'warning',
                            'bg-primary': item.type === 'info'
                        }"
                    ></span>

                    <div class="flex-1 space-y-0.5">
                        <div class="flex justify-between items-center">
                            <p class="text-label-medium font-bold text-on-surface">{{ item.title }}</p>
                            <span class="text-[10px] text-outline">{{ item.time }}</span>
                        </div>
                        <p class="text-body-small text-on-surface-variant line-clamp-2">{{ item.message }}</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</template>