<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import BaristaLayout from '../../Layouts/BaristaLayout.vue';
import { toast } from "vue3-toastify";
import 'vue3-toastify/dist/index.css';

// Dữ liệu mẫu (Thay bằng props từ Controller truyền sang sau này)
const orders = ref([
    {
        id: 1042,
        product_name: 'Cà Phê Sữa Đá Nắng',
        table: 'Bàn 04',
        time_ago: '5 phút trước',
        note: 'Ít đá, Thêm shot Espresso',
        status: 'urgent',
        recipe: {
            ingredients: [
                { name: 'Espresso (Double)', amount: '60ml' },
                { name: 'Sữa đặc', amount: '25ml' },
                { name: 'Sữa tươi', amount: '40ml' },
                { name: 'Đá', amount: 'Ít (50g)', is_alert: true }
            ],
            presentation: {
                glass: 'Ly thủy tinh cao 350ml',
                style: 'Tầng sữa dưới, cà phê trên'
            }
        }
    },
    {
        id: 1043,
        product_name: 'Cold Brew Cam Vàng',
        table: 'Bàn 07',
        time_ago: '2 phút trước',
        note: 'Bình thường',
        status: 'normal',
        recipe: null
    },
    {
        id: 1044,
        product_name: 'Matcha Latte',
        table: 'Mang đi',
        time_ago: 'Vừa xong',
        note: 'Sữa hạt yến mạch',
        status: 'normal',
        recipe: null
    }
]);

// Quản lý Modal Công thức
const selectedOrder = ref(null);
const isRecipeModalOpen = ref(false);

const openRecipeModal = (order) => {
    selectedOrder.value = order;
    isRecipeModalOpen.value = true;
};

const closeRecipeModal = () => {
    isRecipeModalOpen.value = false;
    setTimeout(() => selectedOrder.value = null, 300);
};

// Xử lý hoàn thành món
const completeOrder = (orderId) => {
    // Hiện thông báo Toast
    toast.success(`Đã pha chế xong đơn #${orderId}!`, {
        position: "bottom-right",
        autoClose: 3000,
    });

    // Xóa đơn khỏi danh sách hiển thị (Sau này dùng API thì gọi router.patch)
    orders.value = orders.value.filter(o => o.id !== orderId);

    // Đóng Modal
    closeRecipeModal();
};
</script>

<template>

    <Head title="Đơn chờ pha chế - Barista" />

    <BaristaLayout>
        <header class="mb-10">
            <h2 class="text-headline-xl font-bold text-primary mb-2">Danh sách chờ pha chế</h2>
            <p class="text-body-lg text-on-surface-variant">Bạn có <strong class="text-primary">{{ orders.length
                    }}</strong> đơn hàng cần xử lý.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <div v-for="order in orders" :key="order.id"
                class="relative bg-surface-container-lowest rounded-2xl p-6 shadow-soft border-2 hover:shadow-md transition-all group flex flex-col justify-between h-full"
                :class="order.status === 'urgent' ? 'border-primary' : 'border-outline-variant/30'">

                <div v-if="order.status === 'urgent'"
                    class="absolute top-0 left-0 w-1.5 h-full bg-primary rounded-l-2xl"></div>

                <div>
                    <div class="flex justify-between items-start mb-4">
                        <span class="font-bold text-label-md px-4 py-1.5 rounded-full"
                            :class="order.status === 'urgent' ? 'bg-primary-container/30 text-primary' : 'bg-surface-variant text-on-surface-variant'">
                            #{{ order.id }} • {{ order.table }}
                        </span>
                        <span class="text-label-sm font-medium flex items-center gap-1"
                            :class="order.status === 'urgent' ? 'text-error' : 'text-on-surface-variant'">
                            <span class="material-symbols-outlined text-[16px]">schedule</span>
                            {{ order.time_ago }}
                        </span>
                    </div>

                    <h3 class="text-headline-md font-bold text-on-surface mb-2">{{ order.product_name }}</h3>

                    <div v-if="order.note"
                        class="inline-flex items-start gap-1 bg-surface-container text-tertiary px-3 py-2 rounded-lg border border-outline-variant/20 mb-4">
                        <span class="material-symbols-outlined text-[16px] mt-0.5">edit_note</span>
                        <span class="text-body-md italic">{{ order.note }}</span>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button @click="completeOrder(order.id)"
                        class="flex-1 bg-primary text-on-primary py-3 rounded-xl font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined text-[20px]">check_circle</span> Xong
                    </button>
                    <button @click="openRecipeModal(order)"
                        class="p-3 bg-secondary-container text-on-secondary-container rounded-xl hover:opacity-90 transition-opacity"
                        title="Xem công thức">
                        <span class="material-symbols-outlined text-[20px]">menu_book</span>
                    </button>
                </div>
            </div>

            <div v-if="orders.length === 0"
                class="col-span-full py-20 flex flex-col items-center justify-center bg-surface-container-lowest rounded-2xl border border-dashed border-outline-variant/50">
                <span class="material-symbols-outlined text-[64px] text-outline mb-4">local_cafe</span>
                <p class="text-headline-md text-on-surface-variant">Tuyệt vời! Không còn đơn nào chờ.</p>
            </div>

        </div>

        <Transition name="fade">
            <div v-if="isRecipeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeRecipeModal"></div>

                <Transition name="slide-up">
                    <div v-if="isRecipeModalOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">

                        <div
                            class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-start bg-surface">
                            <div>
                                <h2 class="text-headline-lg font-bold text-primary">{{ selectedOrder?.product_name }}
                                </h2>
                                <p class="text-body-md text-on-surface-variant mt-1">Đơn #{{ selectedOrder?.id }} • {{
                                    selectedOrder?.table }}</p>
                            </div>
                            <button @click="closeRecipeModal"
                                class="p-2 text-on-surface-variant hover:bg-surface-container hover:text-error rounded-full transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto flex-1 bg-surface-container-lowest hide-scrollbar">

                            <div v-if="selectedOrder?.note"
                                class="mb-6 bg-error-container/20 border border-error/20 p-4 rounded-xl flex items-start gap-3">
                                <span class="material-symbols-outlined text-error">campaign</span>
                                <div>
                                    <span
                                        class="block text-label-sm font-bold text-error uppercase tracking-wider mb-1">Lưu
                                        ý từ khách</span>
                                    <span class="text-body-md text-on-surface font-medium">{{ selectedOrder.note
                                        }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" v-if="selectedOrder?.recipe">
                                <div class="bg-surface-container p-5 rounded-2xl">
                                    <h4
                                        class="text-label-md text-on-surface-variant mb-4 uppercase tracking-wider font-bold flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">scale</span> Định lượng
                                    </h4>
                                    <ul class="space-y-3 text-body-md text-on-surface">
                                        <li v-for="(item, index) in selectedOrder.recipe.ingredients" :key="index"
                                            class="flex justify-between border-b border-surface-variant/80 pb-2 last:border-0"
                                            :class="item.is_alert ? 'text-error font-bold' : ''">
                                            <span>{{ item.name }}</span>
                                            <span class="font-medium">{{ item.amount }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    class="bg-surface-container p-5 rounded-2xl flex flex-col justify-center items-center text-center">
                                    <h4
                                        class="text-label-md text-on-surface-variant mb-4 uppercase tracking-wider font-bold w-full text-left flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">local_cafe</span> Cốc &
                                        Trình bày
                                    </h4>
                                    <span
                                        class="material-symbols-outlined text-[64px] text-tertiary font-light mb-3">local_cafe</span>
                                    <p class="text-body-lg font-bold text-on-surface">{{
                                        selectedOrder.recipe.presentation.glass }}</p>
                                    <p class="text-body-md text-on-surface-variant mt-2">{{
                                        selectedOrder.recipe.presentation.style }}</p>
                                </div>
                            </div>

                            <div v-else
                                class="py-10 text-center text-on-surface-variant italic border-2 border-dashed border-outline-variant/50 rounded-2xl">
                                Đang cập nhật công thức cho món này...
                            </div>

                        </div>

                        <div class="px-6 py-5 bg-surface border-t border-outline-variant/30">
                            <button @click="completeOrder(selectedOrder.id)"
                                class="w-full bg-gradient-to-r from-primary to-secondary text-on-primary font-bold text-label-md py-4 rounded-xl shadow-lg hover:shadow-xl hover:opacity-95 transition-all flex justify-center items-center gap-2 uppercase tracking-wider">
                                <span class="material-symbols-outlined">check_circle</span>
                                Đã pha chế xong món này
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

    </BaristaLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>