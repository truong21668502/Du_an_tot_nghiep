<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const isLoading = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);
const isAudioUnlocked = ref(false);

let notificationAudio = null;

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

    // Lấy thông báo lần đầu
    fetchNotifications(false);

    // Quét ngầm 30s/lần
    const interval = setInterval(() => {
        fetchNotifications(true);
    }, 30000);

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

const fetchNotifications = async (isInterval = false) => {
    isLoading.value = true;
    try {
        const res = await axios.get('/quan-tri/api/notifications');
        const newUnreadCount = res.data.unread_count || 0;

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

// Đánh dấu 1 thông báo đã đọc khi click
const handleNotificationClick = async (item) => {
    if (!item.is_read) {
        try {
            await axios.patch(`/quan-tri/api/notifications/${item.id}/read`);
            item.is_read = true;
            if (unreadCount.value > 0) unreadCount.value--;
        } catch (err) {
            console.error("Lỗi cập nhật trạng thái:", err);
        }
    }
    
    // Điều hướng tới link của thông báo
    if (item.link) {
        window.location.href = item.link;
    }
};

// Đánh dấu tất cả đã đọc
const markAllAsRead = async () => {
    try {
        await axios.patch('/quan-tri/api/notifications/mark-all-read');
        unreadCount.value = 0;
        notifications.value.forEach(item => item.is_read = true);
    } catch (err) {
        console.error("Lỗi đánh dấu tất cả đã đọc:", err);
    }
};

const toggleNotification = () => {
    isOpen.value = !isOpen.value;
};
</script>

<template>
    <div class="relative">
        <!-- Nút Quả Chuông -->
        <button 
            @click="toggleNotification" 
            class="relative p-2 rounded-full hover:bg-surface-container-high transition-colors focus:outline-none cursor-pointer"
            title="Thông báo"
        >
            <span class="material-symbols-outlined">notifications</span>

            <span 
                v-if="unreadCount > 0" 
                class="absolute top-1 right-1 bg-error text-on-error text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center animate-bounce"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Thông Báo -->
        <div 
            v-if="isOpen" 
            class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/30 z-50 overflow-hidden"
        >
            <div class="p-3 px-4 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-low">
                <h4 class="font-bold text-title-small text-on-surface">Thông báo</h4>
                <button 
                    v-if="unreadCount > 0"
                    @click="markAllAsRead" 
                    class="text-[11px] text-primary hover:underline font-medium cursor-pointer"
                >
                    Đọc tất cả
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-outline-variant/10">
                <div v-if="isLoading && notifications.length === 0" class="p-6 text-center text-outline text-body-small animate-pulse">
                    Đang tải thông báo...
                </div>

                <div v-else-if="notifications.length === 0" class="p-6 text-center text-outline text-body-small">
                    🎉 Không có thông báo nào !
                </div>

                <div 
                    v-else 
                    v-for="item in notifications" 
                    :key="item.id"
                    @click="handleNotificationClick(item)"
                    class="p-3 hover:bg-surface-container-high transition-colors flex gap-3 items-start cursor-pointer"
                    :class="{ 'opacity-60 bg-surface-container-low/40': item.is_read }"
                >
                    <!-- Chấm màu trạng thái: nếu đã đọc thì chuyển sang màu xám mờ -->
                    <span 
                        class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0"
                        :class="item.is_read ? 'bg-outline-variant' : {
                            'bg-error': item.type === 'danger',
                            'bg-tertiary': item.type === 'warning',
                            'bg-primary': item.type === 'info'
                        }"
                    ></span>

                    <div class="flex-1 space-y-0.5">
                        <div class="flex justify-between items-center">
                            <p 
                                class="text-label-medium text-on-surface"
                                :class="item.is_read ? 'font-normal text-on-surface-variant' : 'font-bold'"
                            >
                                {{ item.title }}
                            </p>
                            <span class="text-[10px] text-outline">{{ item.time }}</span>
                        </div>
                        <p class="text-body-small text-on-surface-variant line-clamp-2">{{ item.message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>