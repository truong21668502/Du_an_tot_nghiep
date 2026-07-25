<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const isLoading = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);

// Hàm lấy dữ liệu thông báo từ Backend
const fetchNotifications = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get('/quan-tri/api/notifications');
        notifications.value = res.data.notifications || [];
        unreadCount.value = res.data.unread_count || 0;
    } catch (err) {
        console.error("Lỗi lấy thông báo:", err);
    } finally {
        isLoading.value = false;
    }
};

// Khi ấn vào quả chuông
const toggleNotification = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
    }
};

// Gọi 1 lần lúc vào trang để hiện số đỏ trên quả chuông
onMounted(() => {
    fetchNotifications();
});
</script>

<template>
    <div class="relative">
        <!-- Nút Icon Quả Chuông -->
        <button 
            @click="toggleNotification" 
            class="relative p-2 rounded-full hover:bg-surface-container-high transition-colors focus:outline-none"
        >
            <!-- Icon Chuông -->
            <svg class="w-6 h-6 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <!-- Badge số lượng thông báo (chỉ hiện khi > 0) -->
            <span 
                v-if="unreadCount > 0" 
                class="absolute top-1 right-1 bg-error text-on-error text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Danh Sách Thông Báo -->
        <div 
            v-if="isOpen" 
            class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/30 z-50 overflow-hidden"
        >
            <!-- Header Dropdown -->
            <div class="p-3 px-4 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-low">
                <h4 class="font-bold text-title-small text-on-surface">Thông báo mới</h4>
                <span class="text-body-small text-outline">{{ notifications.length }} tin tức</span>
            </div>

            <!-- Content -->
            <div class="max-h-80 overflow-y-auto divide-y divide-outline-variant/10">
                <!-- Loading State -->
                <div v-if="isLoading" class="p-6 text-center text-outline text-body-small animate-pulse">
                    Đang tải thông báo...
                </div>

                <!-- Empty State -->
                <div v-else-if="notifications.length === 0" class="p-6 text-center text-outline text-body-small">
                    🎉 Không có thông báo nào !
                </div>

                <!-- Items List -->
                <a 
                    v-else 
                    v-for="item in notifications" 
                    :key="item.id"
                    :href="item.link"
                    class="block p-3 hover:bg-surface-container-high transition-colors flex gap-3 items-start"
                >
                    <!-- Chấm màu phân loại thông báo -->
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