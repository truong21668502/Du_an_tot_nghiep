<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { toast } from "vue3-toastify";
import 'vue3-toastify/dist/index.css';

const isMobileMenuOpen = ref(false)
const currentUrl = computed(() => usePage().url)

// navigation links
const navLinks = [
    { label: 'Tổng quan', href: '/nhan-vien/bang-dieu-khien', icon: 'dashboard' },
    { label: 'Đơn hàng', href: '/nhan-vien/don-hang', icon: 'coffee_maker' },
    { label: 'Sơ đồ bàn', href: '/nhan-vien/so-do-ban', icon: 'table_restaurant' },
]

// kiểm tra link active
const isActiveLink = (path) => {
    if (path === '#') return false;
    return currentUrl.value.startsWith(path);
}

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// đóng mobile menu khi chuyển trang
router.on('navigate', () => {
    isMobileMenuOpen.value = false
})

// ngày giờ thực — tách giờ riêng để hiển thị lớn
const currentTime = ref('')
const currentHour = ref('')

let timeInterval = null

const updateTime = () => {
    const now = new Date();
    currentTime.value = new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }).format(now)
    currentHour.value = new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(now)
}

onMounted(() => {
    updateTime()
    timeInterval = setInterval(() => {
        updateTime()
    }, 1000)
})

onUnmounted(() => {
    clearInterval(timeInterval)
})

// ================= logic tạo đơn mới toàn cục (pos) =================
const page = usePage();
const products = computed(() => page.props.globalProducts || []);
const tables = computed(() => page.props.globalTables || []);

const isCreateModalOpen = ref(false);

const newOrderForm = ref({
    order_type: 'TAKE_AWAY',
    table_id: '',
    payment_method: 'CASH',
    payment_status: 'PAID',
    items: []
});

const currentItem = ref({ product: null, variant: null, quantity: 1, note: '' });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

const addItemToOrder = () => {
    if (!currentItem.value.product || !currentItem.value.variant) {
        toast.error("Vui lòng chọn đủ món và kích cỡ (size)!");
        return;
    }
    
    // gộp món nếu trùng hoàn toàn sản phẩm, size và ghi chú
    const existingIndex = newOrderForm.value.items.findIndex(i => 
        i.product_id === currentItem.value.product.id && 
        i.variant_id === currentItem.value.variant.id && 
        i.note === currentItem.value.note
    );

    if (existingIndex !== -1) {
        newOrderForm.value.items[existingIndex].quantity += currentItem.value.quantity;
    } else {
        newOrderForm.value.items.push({
            product_id: currentItem.value.product.id,
            product_name: currentItem.value.product.product_name,
            variant_id: currentItem.value.variant.id,
            size: currentItem.value.variant.size,
            price: currentItem.value.variant.price,
            quantity: currentItem.value.quantity,
            note: currentItem.value.note
        });
    }
    // reset form nhỏ sau khi thêm
    currentItem.value = { product: null, variant: null, quantity: 1, note: '' };
};

const removeItemFromOrder = (index) => {
    newOrderForm.value.items.splice(index, 1);
};

const newOrderTotal = computed(() => {
    return newOrderForm.value.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const submitNewOrder = () => {
    if (newOrderForm.value.items.length === 0) {
        toast.error("Đơn hàng chưa có món nào!"); return;
    }
    if (newOrderForm.value.order_type === 'DINE_IN' && !newOrderForm.value.table_id) {
        toast.error("Vui lòng chọn bàn cho khách!"); return;
    }

    router.post(route('staff.orders.store'), {
        ...newOrderForm.value,
        total_amount: newOrderTotal.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            newOrderForm.value = { order_type: 'TAKE_AWAY', table_id: '', payment_method: 'CASH', payment_status: 'PAID', items: [] };
            toast.success("Tạo đơn thành công!");
        }
    });
};
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-background text-on-background antialiased">

        <aside class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-surface-container-low shadow-soft py-6 z-40 border-r border-outline-variant/30">
            <div class="px-6 mb-6">
                <Link href="/nhan-vien/bang-dieu-khien" class="text-headline-sm text-primary tracking-tight hover:opacity-80 transition-opacity font-serif font-bold">
                    Nắng Coffee
                </Link>
                <p class="text-label-sm text-on-surface-variant/70 mt-1">Staff Panel</p>
            </div>

            <div class="px-4 mb-6">
                <button @click="isCreateModalOpen = true" class="w-full bg-primary text-on-primary py-3.5 rounded-xl font-bold text-label-sm shadow-md hover:bg-primary/90 flex items-center justify-center gap-2 transition-all uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[20px]">add_circle</span>
                    Tạo đơn mới
                </button>
            </div>

            <nav class="flex-1 flex flex-col gap-2 text-label-md px-2 overflow-y-auto hide-scrollbar">
                <Link v-for="link in navLinks" :key="link.label" :href="link.href" :class="[
                    'flex items-center gap-4 rounded-xl px-4 py-3 transition-all duration-200 group',
                    isActiveLink(link.href)
                        ? 'bg-primary-container text-on-primary-container font-bold'
                        : 'text-on-surface-variant hover:bg-surface-container-high hover:text-primary'
                ]">
                    <span :class="['material-symbols-outlined transition-colors duration-300', isActiveLink(link.href) ? 'icon-fill' : 'group-hover:text-primary']">
                        {{ link.icon }}
                    </span>
                    {{ link.label }}
                </Link>
            </nav>

            <div class="mt-auto flex flex-col gap-2 text-label-md px-2 pt-4 border-t border-outline-variant/30">
                <a href="#" class="flex items-center gap-4 text-on-surface-variant hover:bg-surface-container-high rounded-xl px-4 py-3 hover:text-primary transition-colors duration-300 group">
                    <span class="material-symbols-outlined group-hover:text-primary transition-colors duration-300">settings</span>
                    Cài đặt
                </a>
                <Link :href="route('logout')" method="post" as="button" class="flex items-center w-full text-left gap-4 text-on-surface-variant hover:bg-surface-container-high rounded-xl px-4 py-3 hover:text-error transition-colors duration-300 group">
                    <span class="material-symbols-outlined group-hover:text-error transition-colors duration-300">logout</span>
                    Đăng xuất
                </Link>
            </div>
        </aside>

        <div class="flex-1 md:ml-64 flex flex-col h-screen overflow-hidden">

            <!-- ===== TOPBAR ===== -->
            <header class="h-16 bg-surface/90 backdrop-blur-md border-b border-outline-variant/20 flex items-center justify-between px-4 md:px-6 sticky top-0 z-30">

                <!-- Left: Mobile menu + Datetime -->
                <div class="flex items-center gap-4">
                    <button class="md:hidden p-2 text-primary hover:bg-primary-container/20 rounded-xl transition-colors" @click="toggleMobileMenu">
                        <span class="material-symbols-outlined text-[22px]">{{ isMobileMenuOpen ? 'close' : 'menu' }}</span>
                    </button>
                    <div class="hidden md:flex items-center gap-3">
                        <!-- Live clock -->
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-[22px] font-bold text-on-surface tabular-nums tracking-tight leading-none">{{ currentHour }}</span>
                        </div>
                        <div class="w-px h-6 bg-outline-variant/40"></div>
                        <div>
                            <p class="text-[13px] text-on-surface-variant capitalize">{{ currentTime }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions + Avatar -->
                <div class="flex items-center gap-3">
                    <!-- Notification bell -->
                    <button class="relative w-9 h-9 rounded-xl text-on-surface-variant hover:text-primary hover:bg-primary-container/20 flex items-center justify-center transition-all">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full ring-2 ring-surface"></span>
                    </button>

                    <!-- Divider -->
                    <div class="w-px h-6 bg-outline-variant/30"></div>

                    <!-- User -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl overflow-hidden ring-2 ring-primary/20">
                            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1780751292/N%E1%BA%AFng_coffee_tbphoj.jpg" alt="Avatar" class="w-full h-full object-cover">
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-[13px] font-bold text-on-surface leading-none">Staff</p>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">Nhân viên</p>
                        </div>
                    </div>
                </div>
            </header>

            <Transition name="slide-down">
                <div v-if="isMobileMenuOpen" class="md:hidden absolute top-20 left-0 w-full bg-surface border-b border-outline-variant/30 px-margin-mobile py-4 shadow-lg z-20">
                    
                    <div class="mb-4">
                        <button @click="isCreateModalOpen = true; isMobileMenuOpen = false" class="w-full bg-primary text-on-primary py-3.5 rounded-xl font-bold text-label-sm shadow-md flex items-center justify-center gap-2 uppercase tracking-wider">
                            <span class="material-symbols-outlined text-[20px]">add_circle</span>
                            Tạo đơn mới
                        </button>
                    </div>

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
                        <button class="flex items-center gap-4 py-3 px-4 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-all w-full text-left">
                            <span class="material-symbols-outlined">settings</span>
                            <span class="text-body-md">Cài đặt</span>
                        </button>
                        <Link :href="route('logout')" method="post" as="button" class="flex items-center gap-4 py-3 px-4 rounded-lg text-error hover:bg-error-container/50 transition-all w-full text-left">
                            <span class="material-symbols-outlined">logout</span>
                            <span class="text-body-md font-bold">Đăng xuất</span>
                        </Link>
                    </div>
                </div>
            </Transition>

            <main class="flex-1 overflow-y-auto hide-scrollbar" style="background: var(--md-sys-color-background);">
                <div class="p-5 md:p-8 max-w-[1600px] mx-auto">
                    <slot />
                </div>
            </main>

        </div>

        <Transition name="fade">
            <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="isCreateModalOpen = false"></div>
                <Transition name="slide-up">
                    <div v-if="isCreateModalOpen" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
                        
                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-center bg-surface">
                            <h3 class="font-serif text-headline-sm font-bold text-on-background flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[28px]">point_of_sale</span>
                                Tạo đơn hàng mới
                            </h3>
                            <button @click="isCreateModalOpen = false" class="p-2 text-on-surface-variant hover:bg-surface-container hover:text-error rounded-full transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto flex-1 bg-surface-container-lowest hide-scrollbar grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <div class="space-y-5">
                                <div class="bg-surface p-5 rounded-xl border border-outline-variant/30">
                                    <label class="text-label-sm font-bold text-on-surface-variant mb-3 block tracking-widest uppercase">1. Thông tin phục vụ</label>
                                    <div class="flex gap-6 mb-4">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" v-model="newOrderForm.order_type" value="TAKE_AWAY" class="w-4 h-4 text-primary focus:ring-primary border-outline-variant/50">
                                            <span class="font-medium text-body-md">Mang đi</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" v-model="newOrderForm.order_type" value="DINE_IN" class="w-4 h-4 text-primary focus:ring-primary border-outline-variant/50">
                                            <span class="font-medium text-body-md">Tại bàn</span>
                                        </label>
                                    </div>
                                    <div v-if="newOrderForm.order_type === 'DINE_IN'" class="animate-fade-in">
                                        <select v-model="newOrderForm.table_id" class="w-full rounded-lg border-outline-variant/50 bg-surface-container-lowest text-body-md focus:ring-primary focus:border-primary py-2.5">
                                            <option value="" disabled>-- Chọn bàn phục vụ --</option>
                                            <option v-for="table in tables" :key="table.id" :value="table.id">
                                                {{ table.table_name }} ({{ table.area }}) - {{ table.status === 'EMPTY' ? 'Trống' : 'Đang có khách' }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="bg-surface p-5 rounded-xl border border-outline-variant/30">
                                    <label class="text-label-sm font-bold text-on-surface-variant mb-3 block tracking-widest uppercase">2. Chọn món</label>
                                    <div class="space-y-3">
                                        <select v-model="currentItem.product" @change="currentItem.variant = null" class="w-full rounded-lg border-outline-variant/50 bg-surface-container-lowest text-body-md focus:ring-primary focus:border-primary py-2.5">
                                            <option :value="null" disabled>-- Chọn đồ uống/món ăn --</option>
                                            <option v-for="prod in products" :key="prod.id" :value="prod">
                                                {{ prod.product_name }}
                                            </option>
                                        </select>

                                        <div class="flex gap-3">
                                            <select v-model="currentItem.variant" class="flex-1 rounded-lg border-outline-variant/50 bg-surface-container-lowest text-body-md focus:ring-primary focus:border-primary py-2.5" :disabled="!currentItem.product">
                                                <option :value="null" disabled>Chọn size</option>
                                                <option v-for="v in currentItem.product?.variants || []" :key="v.id" :value="v">
                                                    Size {{ v.size }} - {{ formatCurrency(v.price) }}
                                                </option>
                                            </select>
                                            <input type="number" v-model="currentItem.quantity" min="1" title="Số lượng" class="w-20 rounded-lg border-outline-variant/50 bg-surface-container-lowest text-center text-body-md focus:ring-primary focus:border-primary py-2.5">
                                        </div>

                                        <input type="text" v-model="currentItem.note" placeholder="Ghi chú (ví dụ: ít đá, nhiều sữa...)" class="w-full rounded-lg border-outline-variant/50 bg-surface-container-lowest text-body-md focus:ring-primary focus:border-primary py-2.5 placeholder:text-sm">

                                        <button @click="addItemToOrder" class="w-full bg-secondary-container text-on-secondary-container py-3 rounded-lg font-bold hover:bg-secondary-container/80 transition-colors flex items-center justify-center gap-2 mt-2">
                                            <span class="material-symbols-outlined text-[20px]">add_shopping_cart</span>
                                            Thêm món này vào hóa đơn
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col bg-surface p-5 rounded-xl border border-outline-variant/30 h-full">
                                <p class="text-label-sm font-bold text-on-surface-variant tracking-widest uppercase border-b border-outline-variant/30 pb-3 mb-3">3. Chi tiết hóa đơn</p>
                                
                                <div class="flex-1 overflow-y-auto hide-scrollbar space-y-2 min-h-[200px] content-start">
                                    <div v-if="newOrderForm.items.length === 0" class="text-center text-on-surface-variant mt-10 text-sm italic opacity-70 flex flex-col items-center">
                                        <span class="material-symbols-outlined text-[40px] mb-2">receipt_long</span>
                                        Hóa đơn trống
                                    </div>
                                    
                                    <div v-for="(item, index) in newOrderForm.items" :key="index" class="flex justify-between items-center gap-3 bg-surface-container-lowest p-3 rounded-lg border border-outline-variant/20 hover:border-primary/30 transition-colors">
                                        <div class="flex-1">
                                            <p class="font-bold text-on-surface text-[15px]">
                                                {{ item.product_name }} 
                                                <span class="text-primary ml-1 text-sm">x{{ item.quantity }}</span>
                                            </p>
                                            <p class="text-[12px] text-on-surface-variant mt-0.5">Size {{ item.size }} • {{ formatCurrency(item.price) }}</p>
                                            <p v-if="item.note" class="text-[11px] italic text-tertiary mt-1">"{{ item.note }}"</p>
                                        </div>
                                        <button @click="removeItemFromOrder(index)" class="text-error hover:bg-error-container p-2 rounded-full transition-colors flex" title="Xóa món">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t border-outline-variant/30">
                                    <div class="flex justify-between items-center mb-4 bg-primary-container/20 p-3 rounded-lg border border-primary/20">
                                        <span class="font-bold text-on-surface">Tổng thanh toán:</span>
                                        <span class="text-headline-sm font-bold text-primary">{{ formatCurrency(newOrderTotal) }}</span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Hình thức</label>
                                            <select v-model="newOrderForm.payment_method" class="w-full rounded-lg border-outline-variant/50 bg-surface-container-lowest text-sm py-2">
                                                <option value="CASH">Tiền mặt</option>
                                                <option value="BANK_TRANSFER">Chuyển khoản</option> 
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Tình trạng tt</label>
                                            <select v-model="newOrderForm.payment_status" class="w-full rounded-lg border-outline-variant/50 bg-surface-container-lowest text-sm py-2 font-bold" :class="newOrderForm.payment_status === 'PAID' ? 'text-secondary' : 'text-error'">
                                                <option value="PAID">ĐÃ THU TIỀN</option>
                                                <option value="PENDING">CHƯA THU</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-surface border-t border-outline-variant/30 flex gap-4 justify-end">
                            <button @click="isCreateModalOpen = false" class="px-6 py-2.5 rounded-full text-label-md font-bold text-on-surface-variant hover:bg-surface-container transition-colors">
                                Hủy bỏ
                            </button>
                            <button @click="submitNewOrder" class="px-8 py-2.5 rounded-full bg-primary text-on-primary font-bold text-label-md shadow-soft hover:bg-primary/90 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">send</span> Đặt đơn ngay
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
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

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(20px) scale(0.95); }

.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>