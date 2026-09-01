<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import ShipperLayout from '@/Layouts/ShipperLayout.vue'

const props = defineProps({
    orders: Array,
    shopLat: Number,
    shopLng: Number,
})

const form = useForm({})
const ordersList = ref(props.orders)
const confirmAccept = ref(null)

// --- FORMATTERS ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

const formatDistance = (meters) => {
    if (!meters) return 'Chưa rõ khoảng cách'
    return  `${meters} km`
}

const formatTime = (isoString) => {
    const date = new Date(isoString)
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

// --- COMPUTED ---
// Đơn mới, chưa có ai nhận
const availableOrders = computed(() => {
    return ordersList.value.filter(o => o.status === 'READY' && !o.is_my_order)
})

// Đơn mình đang giao
const myOrders = computed(() => {
    return ordersList.value.filter(o => o.is_my_order)
})

// --- WEBSOCKET ---
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('shipper-orders')
            .listen('.order.ready', (e) => {
                const newOrder = e.order
                
                const existsIndex = ordersList.value.findIndex(o => o.id === newOrder.id)
                
                if (existsIndex === -1) {
                    newOrder.is_my_order = false 
                    ordersList.value.unshift(newOrder)
                    toast.info(`Đơn mới #${newOrder.code || newOrder.id} sẵn sàng giao!`)
                    new Audio('https://res.cloudinary.com/dltgjdf9t/video/upload/v1785330018/Chu%C3%B4ng_nh%E1%BA%AFc_nh%E1%BB%9F_nh%C3%A2n_vi%C3%AAn_lp3cpm.mp3').play().catch(err => console.error('Error playing sound:', err));
                }
            })
            .listen('.order.accepted', (e) => {
                if(e.order.shipper_id !== usePage().props.auth.user.id) {
                    ordersList.value = ordersList.value.filter(o => o.id !== e.order.id)
                }
            })
            .listen('.order.status-updated', (e) => {
                const idx = ordersList.value.findIndex(o => o.id === e.order.id)
                if (idx !== -1) {
                    if (e.order.payment_status) {
                        ordersList.value[idx].payment_status = e.order.payment_status
                    }
                }
            })
            .listen('.order.payment-confirmed', (e) => {
                const idx = ordersList.value.findIndex(o => o.id === e.id)
                if (idx !== -1) {
                    ordersList.value[idx].payment_status = e.payment_status
                }
            })
    }
})

onUnmounted(() => {
    if (window.Echo) {      
        window.Echo.leaveChannel('shipper-orders')
    }
})

// --- ACTIONS ---
const openConfirm = (order) => {
    confirmAccept.value = order
}

const closeConfirm = () => {
    confirmAccept.value = null
}

const acceptOrder = () => {
    if (!confirmAccept.value) return

    form.patch(route('shipper.orders.accept', confirmAccept.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            const idx = ordersList.value.findIndex(o => o.id === confirmAccept.value.id)
            if (idx !== -1) {
                ordersList.value[idx].status = 'DELIVERING'
                ordersList.value[idx].is_my_order = true
            }
            toast.success('Nhận đơn thành công! Vui lòng tiến hành giao hàng.')
            closeConfirm()
        },
        onError: (errors) => {
            Object.values(errors).forEach(err => toast.error(err))
            router.reload({ only: ['orders'] })
            closeConfirm()
        },
    })
}

const goToDelivery = (orderId) => {
    router.get(route('shipper.delivery', orderId))
}
</script>

<template>
    <Head title="Danh sách đơn hàng" />

    <ShipperLayout>
        <div class="min-h-screen bg-background pb-20">
            <!-- Header Tổng -->
            <div class="bg-surface sticky top-0 z-10 px-6 py-4 shadow-sm mb-4">
                <h1 class="text-headline-md text-on-surface">Quản Lý Giao Hàng</h1>
            </div>

            <!-- Bố cục Stack (Xếp dọc) thay vì Grid -->
            <div class="px-4 lg:px-6 flex flex-col gap-6 items-stretch">
                
                <!-- ================= ĐƠN ĐANG GIAO ================= -->
                <div class="bg-surface-container-low rounded-2xl p-4 shadow-sm border border-surface-container">
                    <div class="flex items-center justify-between mb-4 border-b border-surface-container-highest pb-3">
                        <h2 class="text-headline-sm text-secondary flex items-center">
                            <span class="material-symbols-outlined mr-2">speed</span>
                            Đơn Đang Giao
                        </h2>
                        <span v-if="myOrders.length" class="bg-secondary text-on-secondary px-3 py-1 rounded-full text-label-md shadow-sm">
                            {{ myOrders.length }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        <TransitionGroup name="list" tag="div" class="space-y-4">
                            <div 
                                v-for="order in myOrders" 
                                :key="order.id"
                                class="bg-surface-container-lowest rounded-xl p-4 shadow-soft border border-surface-container-high relative overflow-hidden"
                            >
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-secondary"></div>

                                <!-- Card Header -->
                                <div class="flex justify-between items-start mb-3 pl-2">
                                    <div>
                                        <span class="text-label-md text-primary font-bold">{{ order.code }}</span>
                                        <div class="flex items-center text-label-sm text-outline mt-1">
                                            <span class="material-symbols-outlined text-[14px] mr-1">schedule</span>
                                            {{ formatTime(order.created_at) }}
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-sm text-[11px] font-bold tracking-wider bg-secondary-container text-on-secondary-container">
                                        ĐANG GIAO
                                    </span>
                                </div>

                                <hr class="border-surface-container-high mb-3">

                                <!-- Card Body -->
                                <div class="space-y-2.5 pl-2 mb-4">
                                    <div class="flex items-start">
                                        <div class="bg-surface-container p-1.5 rounded-full mr-3 shrink-0 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">location_on</span>
                                        </div>
                                        <div>
                                            <p class="text-body-md text-on-surface font-medium line-clamp-2 leading-tight">{{ order.address }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="bg-surface-container p-1.5 rounded-full mr-3 shrink-0 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">phone</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-body-md text-on-surface">{{ order.customer }}</span>
                                            <a :href="`tel:${order.phone}`" class="text-label-sm text-secondary font-medium">{{ order.phone }}</a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Action -->
                                <div class="pl-2 pt-2">
                                    <button 
                                        @click="goToDelivery(order.id)"
                                        class="w-full bg-secondary hover:bg-tertiary text-on-secondary py-3 rounded-lg text-label-md transition-colors flex items-center justify-center shadow-md"
                                    >
                                        <span class="material-symbols-outlined text-[20px] mr-2">check_circle</span>
                                        TIẾP TỤC GIAO HÀNG
                                    </button>
                                </div>
                            </div>
                        </TransitionGroup>

                        <!-- Empty State Đang Giao -->
                        <div v-if="myOrders.length === 0" class="flex flex-col items-center justify-center py-12 text-center opacity-60">
                            <span class="material-symbols-outlined text-[48px] text-outline mb-3">check_box_outline_blank</span>
                            <p class="text-body-md text-on-surface-variant">Bạn chưa nhận đơn nào.</p>
                        </div>
                    </div>
                </div>

                <!-- ================= ĐƠN MỚI (CHỜ NHẬN) ================= -->
                <div class="bg-surface-container-low rounded-2xl p-4 shadow-sm border border-surface-container">
                    <div class="flex items-center justify-between mb-4 border-b border-surface-container-highest pb-3">
                        <h2 class="text-headline-sm text-primary flex items-center">
                            <span class="material-symbols-outlined mr-2">new_releases</span>
                            Đơn Mới Chờ Nhận
                        </h2>
                        <span v-if="availableOrders.length" class="bg-error text-on-error px-3 py-1 rounded-full text-label-md shadow-sm">
                            {{ availableOrders.length }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        <TransitionGroup name="list" tag="div" class="space-y-4">
                            <div 
                                v-for="order in availableOrders" 
                                :key="order.id"
                                class="bg-surface-container-lowest rounded-xl p-4 shadow-soft border border-surface-container-high relative overflow-hidden"
                            >
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary"></div>

                                <!-- Card Header -->
                                <div class="flex justify-between items-start mb-3 pl-2">
                                    <div>
                                        <span class="text-label-md text-primary font-bold">{{ order.code }}</span>
                                        <div class="flex items-center text-label-sm text-outline mt-1">
                                            <span class="material-symbols-outlined text-[14px] mr-1">schedule</span>
                                            {{ formatTime(order.created_at) }}
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-sm text-[11px] font-bold tracking-wider bg-primary-container text-on-primary-container">
                                        CHỜ NHẬN
                                    </span>
                                </div>

                                <hr class="border-surface-container-high mb-3">

                                <!-- Card Body -->
                                <div class="space-y-2.5 pl-2 mb-4">
                                    <div class="flex items-start">
                                        <div class="bg-surface-container p-1.5 rounded-full mr-3 shrink-0 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">location_on</span>
                                        </div>
                                        <div>
                                            <p class="text-body-md text-on-surface font-medium line-clamp-2 leading-tight">{{ order.address }}</p>
                                            <p class="text-label-sm text-primary mt-0.5 font-semibold">Cách đây: {{ formatDistance(order.distance) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="bg-surface-container p-1.5 rounded-full mr-3 shrink-0 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">payments</span>
                                        </div>
                                        <p class="text-body-lg text-on-surface font-bold">
                                            {{ formatCurrency(order.total) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="pl-2 pt-2">
                                    <button 
                                        @click="order.payment_status === 'PAID' ? openConfirm(order) : null"
                                        :class="order.payment_status === 'PAID' 
                                            ? 'cursor-pointer bg-primary hover:bg-inverse-surface text-on-primary' 
                                            : 'cursor-not-allowed bg-surface-container-highest text-on-surface-variant opacity-60'"
                                        class="w-full py-3 rounded-lg text-label-md transition-colors flex items-center justify-center"
                                    >
                                        <span v-if="order.payment_status === 'PAID'" class="material-symbols-outlined text-[20px] mr-2">local_shipping</span>
                                        <span v-else class="material-symbols-outlined text-[20px] mr-2">payments</span>
                                        {{ order.payment_status === 'PAID' ? 'NHẬN ĐƠN NÀY' : 'CHỜ QUẦY THU TIỀN' }}
                                    </button>
                                </div>
                            </div>
                        </TransitionGroup>

                        <!-- Empty State Chờ Nhận -->
                        <div v-if="availableOrders.length === 0" class="flex flex-col items-center justify-center py-12 text-center opacity-60">
                            <span class="material-symbols-outlined text-[48px] text-outline mb-3">inbox</span>
                            <p class="text-body-md text-on-surface-variant">Không có đơn hàng mới nào.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Confirm Accept -->
        <Transition name="fade">
            <div v-if="confirmAccept" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-inverse-surface/40 backdrop-blur-sm" @click="closeConfirm"></div>
                
                <div class="glass-panel w-full max-w-sm rounded-xl p-6 relative z-10 shadow-2xl transform transition-all scale-100 bg-surface">
                    <button @click="closeConfirm" class="absolute top-4 right-4 p-1 rounded-full hover:bg-surface-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-[24px] text-outline">close</span>
                    </button>

                    <h3 class="text-headline-sm text-on-surface mb-2">Xác nhận nhận đơn</h3>
                    <p class="text-body-md text-on-surface-variant mb-6">
                        Bạn có chắc chắn muốn nhận giao đơn hàng <span class="font-bold text-primary">{{ confirmAccept.code }}</span> không?
                    </p>

                    <div class="flex gap-3">
                        <button 
                            @click="closeConfirm"
                            class="flex-1 py-3 rounded-lg text-label-md text-on-surface bg-surface-container hover:bg-surface-container-high transition-colors"
                            :disabled="form.processing"
                        >
                            HỦY
                        </button>
                        <button 
                            @click="acceptOrder"
                            class="flex-1 py-3 cursor-pointer rounded-lg text-label-md text-on-primary bg-primary hover:bg-inverse-surface transition-colors relative flex items-center justify-center"
                            :disabled="form.processing"
                        >
                            <span :class="{'opacity-0': form.processing}">ĐỒNG Ý</span>
                            <svg v-if="form.processing" class="animate-spin h-5 w-5 absolute text-on-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </ShipperLayout>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.5s ease;
}
.list-enter-from {
    opacity: 0;
    transform: translateY(30px);
}
.list-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>