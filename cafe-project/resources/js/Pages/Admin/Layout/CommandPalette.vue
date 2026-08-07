<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const isOpen = ref(false);
const searchQuery = ref('');
const selectedIndex = ref(0);
const searchInput = ref(null);

// Danh sách tất cả các chức năng điều hướng dựa theo routes.php của bạn
const commands = [
    { title: 'Trang chủ / Dashboard', icon: 'dashboard', route: '/quan-tri/trang-chu', category: 'Tổng quan' },
    
    // Quản lý bán hàng & Cửa hàng
    { title: 'Quản lý Đơn hàng', icon: 'shopping_cart', route: '/quan-tri/don-hang', category: 'Bán hàng' },
    { title: 'Quản lý Bàn ăn / Cà phê', icon: 'table_restaurant', route: '/quan-tri/ban', category: 'Cửa hàng' },
    { title: 'Quản lý Sản phẩm', icon: 'inventory_2', route: '/quan-tri/san-pham', category: 'Sản phẩm' },
    { title: 'Quản lý Danh mục sản phẩm', icon: 'category', route: '/quan-tri/danh-muc', category: 'Sản phẩm' },
    { title: 'Quản lý Thương hiệu', icon: 'branding_watermark', route: '/quan-tri/thuong-hieu', category: 'Sản phẩm' },
    { title: 'Công thức pha chế (Recipe)', icon: 'menu_book', route: '/quan-tri/cong-thuc', category: 'Sản phẩm' },

    // Quản lý Kho & Nguyên liệu
    { title: 'Tổng quan Kho nguyên liệu', icon: 'warehouse', route: '/quan-tri/kho', category: 'Quản lý kho' },
    { title: 'Nhập kho nguyên liệu', icon: 'add_shopping_cart', route: '/quan-tri/kho/nhap', category: 'Quản lý kho' },
    { title: 'Lịch sử nhập kho', icon: 'history', route: '/quan-tri/kho/nhap/lich-su', category: 'Quản lý kho' },
    { title: 'Điều chỉnh tồn kho', icon: 'tune', route: '/quan-tri/kho/dieu-chinh-ton', category: 'Quản lý kho' },
    { title: 'Biến động / Biến đổi kho', icon: 'swap_horiz', route: '/quan-tri/kho/chuyen-dong', category: 'Quản lý kho' },

    // Khách hàng & Marketing
    { title: 'Quản lý Người dùng / Tài khoản', icon: 'group', route: '/quan-tri/nguoi-dung', category: 'Tài khoản' },
    { title: 'Quản lý Mã giảm giá (Coupon)', icon: 'confirmation_number', route: '/quan-tri/ma-giam-gia', category: 'Marketing' },
    { title: 'Ví Voucher người dùng', icon: 'card_membership', route: '/quan-tri/vi-voucher', category: 'Marketing' },
    { title: 'Quản lý Banner quảng cáo', icon: 'view_carousel', route: '/quan-tri/banners', category: 'Marketing' },

    // Nội dung & Đánh giá
    { title: 'Quản lý Bài viết', icon: 'article', route: '/quan-tri/bai-viet', category: 'Nội dung' },
    { title: 'Tạo bài viết mới', icon: 'post_add', route: '/quan-tri/bai-viet/tao-moi', category: 'Nội dung' },
    { title: 'Danh mục bài viết', icon: 'folder', route: '/quan-tri/danh-muc-bai-viet', category: 'Nội dung' },
    { title: 'Quản lý Bình luận bài viết', icon: 'comment', route: '/quan-tri/binh-luan', category: 'Nội dung' },
    { title: 'Đánh giá sản phẩm & Phản hồi', icon: 'star', route: '/quan-tri/danh-gia-san-pham', category: 'Đánh giá' },
    { title: 'Từ khóa vi phạm / Cấm', icon: 'block', route: '/quan-tri/tu-khoa-vi-pham', category: 'Cấu hình' },

    
];

// Lọc danh sách theo từ khóa tìm kiếm
const filteredCommands = computed(() => {
    if (!searchQuery.value.trim()) return commands;
    const query = searchQuery.value.toLowerCase();
    return commands.filter(item => 
        item.title.toLowerCase().includes(query) || 
        item.category.toLowerCase().includes(query)
    );
});

// Mở Palette và focus vào ô input
const openPalette = () => {
    isOpen.value = true;
    searchQuery.value = '';
    selectedIndex.value = 0;
    nextTick(() => {
        searchInput.value?.focus();
    });
};

// Đóng Palette
const closePalette = () => {
    isOpen.value = false;
};

// Điều hướng đến trang được chọn
const navigateTo = (url) => {
    closePalette();
    router.visit(url);
};

// Xử lý phím mũi tên lên/xuống và Enter
const handleKeyDown = (e) => {
    if (!isOpen.value) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % filteredCommands.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value - 1 + filteredCommands.value.length) % filteredCommands.value.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (filteredCommands.value[selectedIndex.value]) {
            navigateTo(filteredCommands.value[selectedIndex.value].route);
        }
    } else if (e.key === 'Escape') {
        closePalette();
    }
};

// Lắng nghe phím tắt Ctrl + K (hoặc Cmd + K trên Mac)
const handleGlobalKeyDown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        if (isOpen.value) {
            closePalette();
        } else {
            openPalette();
        }
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeyDown);
});
</script>

<template>
    <div>
        <!-- Ô Input / Nút trigger ở Header Admin (Bấm vào hoặc dùng Ctrl+K) -->
        <button 
            @click="openPalette"
            class="group relative flex items-center justify-between w-80 sm:w-80 px-3 py-2 text-[12px] text-on-surface-variant hover:text-primary bg-surface-container-high/80 hover:bg-surface-container-lowest rounded-full border border-primary/30 hover:border-primary shadow-xs hover:shadow-md transition-all duration-300 cursor-pointer"
        >
            <!-- Hiệu ứng viền phát sáng nhấp nháy nhẹ (Pulse Glow) thu hút ánh nhìn -->
            <span class="absolute -inset-0.5 rounded-full bg-primary/20 animate-pulse group-hover:hidden"></span>

            <div class="relative flex items-center gap-1.5 min-w-0 z-10">
                <!-- Icon Kính Lúp nhún nhẹ và đổi màu khi hover -->
                <span class="material-symbols-outlined text-[16px] text-primary group-hover:scale-110 transition-transform duration-200 shrink-0">
                    search
                </span>
                <span class="truncate font-sans text-[16px]">Tìm nhanh chức năng...</span>
            </div>
        
            <!-- Phím Ctrl K nổi bật nhẹ -->
            <kbd class="relative z-10 hidden sm:inline-block text-[12px] font-mono font-bold text-primary bg-primary-container/40 group-hover:bg-primary group-hover:text-on-primary px-1.5 py-0.5 rounded border border-primary/20 transition-colors shrink-0">
                Ctrl K
            </kbd>
        </button>

        <!-- Modal Popup Tìm Kiếm -->
        <div 
            v-if="isOpen" 
            class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 bg-black/40 backdrop-blur-sm px-4"
            @click.self="closePalette"
        >
            <div 
                class="w-full max-w-xl bg-surface-container-lowest rounded-2xl shadow-2xl border border-outline-variant/30 overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                @keydown="handleKeyDown"
            >
                <!-- Input Tìm Kiếm -->
                <div class="p-4 border-b border-outline-variant/20 flex items-center gap-3 bg-surface-container-low">
                    <span class="material-symbols-outlined text-primary">search</span>
                    <input 
                        ref="searchInput"
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Gõ tên chức năng (ví dụ: kho, đơn hàng, bài viết)..."
                        class="w-full bg-transparent border-none text-body-medium text-on-surface focus:outline-none focus:ring-0 placeholder:text-on-surface-variant/50"
                    />
                    <kbd @click="closePalette" class="cursor-pointer text-[11px] font-mono bg-surface-container-high text-on-surface-variant px-2 py-0.5 rounded border border-outline-variant/30">
                        ESC
                    </kbd>
                </div>

                <!-- Danh Sách Kết Quả -->
                <div class="max-h-80 overflow-y-auto p-2">
                    <div v-if="filteredCommands.length === 0" class="p-8 text-center text-on-surface-variant/60 text-body-medium">
                        Không tìm thấy chức năng nào phù hợp!
                    </div>

                    <div 
                        v-else 
                        v-for="(item, index) in filteredCommands" 
                        :key="item.route"
                        @click="navigateTo(item.route)"
                        @mouseenter="selectedIndex = index"
                        class="flex items-center justify-between p-3 rounded-xl cursor-pointer transition-colors"
                        :class="{
                            'bg-primary-container/40 text-primary font-medium': selectedIndex === index,
                            'hover:bg-surface-container-high/50 text-on-surface': selectedIndex !== index
                        }"
                    >
                        <div class="flex items-center gap-3">
                            <div 
                                class="w-8 h-8 rounded-lg flex items-center justify-center"
                                :class="selectedIndex === index ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ item.icon }}</span>
                            </div>
                            <div>
                                <p class="text-label-large">{{ item.title }}</p>
                                <p class="text-[11px] text-on-surface-variant/60">{{ item.route }}</p>
                            </div>
                        </div>

                        <span class="text-[11px] px-2 py-0.5 rounded-md bg-surface-container-high/80 text-on-surface-variant">
                            {{ item.category }}
                        </span>
                    </div>
                </div>

                <!-- Footer hướng dẫn bấm phím -->
                <div class="p-3 bg-surface-container-low border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant/60">
                    <div class="flex items-center gap-3">
                        <span><kbd class="font-mono bg-surface-container px-1 rounded">↑</kbd> <kbd class="font-mono bg-surface-container px-1 rounded">↓</kbd> Di chuyển</span>
                        <span><kbd class="font-mono bg-surface-container px-1 rounded">↵</kbd> Chọn</span>
                    </div>
                    <span>{{ filteredCommands.length }} chức năng</span>
                </div>
            </div>
        </div>
    </div>
</template>