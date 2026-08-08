<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import ShipperLayout from '@/Layouts/ShipperLayout.vue'

const props = defineProps({
    order: Object,
    shopLat: Number,
    shopLng: Number,
})

// Camera/upload logic
const deliveryPhoto = ref(null)
const photoPreview = ref(null)
const fileInput = ref(null)
const isDragging = ref(false)

const handleFileChange = (e) => {
    const file = e.target.files[0]
    processFile(file)
}

const handleDrop = (e) => {
    isDragging.value = false
    const file = e.dataTransfer.files[0]
    processFile(file)
}

const processFile = (file) => {
    if (file && file.type.startsWith('image/')) {
        deliveryPhoto.value = file
        photoPreview.value = URL.createObjectURL(file)
    } else {
        toast.error('Vui lòng chọn định dạng hình ảnh!')
    }
}

const openCamera = () => {
    fileInput.value.click()
}

const removePhoto = () => {
    deliveryPhoto.value = null
    photoPreview.value = null
    fileInput.value.value = ''
}

const form = useForm({
    delivery_photo: null,
})

const submitComplete = () => {
    if (!deliveryPhoto.value) {
        toast.error('Vui lòng chụp ảnh xác nhận giao hàng!')
        return
    }
    form.delivery_photo = deliveryPhoto.value
    form.post(route('shipper.orders.complete', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Giao hàng thành công!')
        },
        onError: (errors) => {
            Object.values(errors).forEach(err => toast.error(err))
        }
    })
}

// FORMATTERS
const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0)
}
const amountToCollect = computed(() => {
    const paymentMethod =
        props.order.payment?.payment_method ||
        props.order.payment_method

    return paymentMethod === 'CASH'
        ? props.order.final_amount
        : 0
})

// BẢN ĐỒ
const mapsUrl = computed(() => {
    const destLat = props.order.latitude
    const destLng = props.order.longitude
    const shopLat = props.shopLat
    const shopLng = props.shopLng

    console.log('Maps URL:', { destLat, destLng, shopLat, shopLng });

    if (!destLat || !destLng || !shopLat || !shopLng) return ''

    return `https://www.google.com/maps/embed?pb=!1m28!1m12!1m3!1d${Math.floor(Math.random() * 100000)}!2d${destLng}!3d${destLat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m13!3e0!4m5!1s${shopLat}%2C${shopLng}!2s${shopLat}%2C${shopLng}!3m2!1d${shopLat}!2d${shopLng}!4m5!1s${destLat}%2C${destLng}!2s${destLat}%2C${destLng}!3m2!1d${destLat}!2d${destLng}!5e0!3m2!1svi!2s!4v${Date.now()}`
})

const openExternalMap = () => {
    const destLat = props.order.latitude
    const destLng = props.order.longitude
    if (destLat && destLng) {
        window.open(`https://www.google.com/maps/dir/?api=1&destination=${destLat},${destLng}`, '_blank')
    }
}
</script>

<template>
    <Head title="Giao hàng" />
    
    <ShipperLayout>
        <!-- Bố cục cột: Mobile sẽ hiển thị Bản đồ trên cùng rồi đến thông tin (flex-col-reverse), PC chia 2 cột -->
        <div class="flex flex-col-reverse lg:flex-row min-h-screen bg-background">
            
            <!-- CỘT TRÁI: THÔNG TIN ĐƠN HÀNG VÀ ACTION -->
            <div class="w-full lg:w-1/2 lg:border-r border-surface-container-high overflow-y-auto pb-24 lg:pb-10">
                
                <!-- Header / Nút Quay Lại -->
                <div class="bg-surface sticky top-0 z-10 px-4 py-4 shadow-sm flex items-center gap-3">
                    <button 
                        @click="router.get(route('shipper.orders.index'))" 
                        class="p-2 rounded-full bg-surface-container hover:bg-surface-container-high transition-colors"
                    >
                        <span class="material-symbols-outlined text-on-surface">arrow_back</span>
                    </button>
                    <div>
                        <h1 class="text-headline-sm text-on-surface leading-tight">Đơn #{{ order.code || order.id }}</h1>
                        <p class="text-label-sm text-primary tracking-wide">ĐANG GIAO HÀNG</p>
                    </div>
                </div>

                <div class="p-4 lg:p-6 space-y-6">
                    
                    <div 
                        class="p-5 rounded-2xl flex items-center justify-between shadow-soft border"
                        :class="amountToCollect > 0 ? 'bg-primary-container border-primary/20 text-on-primary-container' : 'bg-surface-container border-surface-container-highest text-on-surface'"
                    >
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[28px]">payments</span>
                            <span class="text-label-md font-bold uppercase tracking-wider">Cần thu (Tiền mặt)</span>
                        </div>
                        <span class="text-headline-md font-bold">{{ formatCurrency(amountToCollect) }}</span>
                    </div>

                    <!-- THÔNG TIN KHÁCH HÀNG & ĐỊA CHỈ -->
                    <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm border border-surface-container-high">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex gap-3 items-center">
                                <div class="w-12 h-12 bg-secondary-container text-on-secondary-container rounded-full flex items-center justify-center shrink-0 shadow-sm">
                                    <span class="material-symbols-outlined text-[24px]">person</span>
                                </div>
                                <div>
                                    <h3 class="text-body-lg font-bold text-on-surface">{{ order.receiver_name || 'Khách lẻ' }}</h3>
                                    <p class="text-body-md text-on-surface-variant">{{ order.receiver_phone }}</p>
                                </div>
                            </div>
                            <!-- Nút Gọi Trực Tiếp -->
                            <a :href="`tel:${order.receiver_phone}`" class="bg-secondary text-on-secondary p-3 rounded-xl shadow-md hover:bg-tertiary transition flex items-center justify-center">
                                <span class="material-symbols-outlined">call</span>
                            </a>
                        </div>

                        <hr class="border-surface-container my-4">

                        <div class="flex gap-3 items-start">
                            <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
                            <div>
                                <p class="text-label-sm text-outline mb-1 uppercase tracking-wider">Địa chỉ giao hàng</p>
                                <p class="text-body-md text-on-surface font-medium leading-relaxed">
                                    {{ order.address_detail }}, {{ order.ward }}, {{ order.city }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- CHI TIẾT SẢN PHẨM -->
                    <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm border border-surface-container-high">
                        <h4 class="text-label-md text-on-surface font-bold uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                            Chi tiết món
                        </h4>
                        
                        <ul class="space-y-3 mb-4">
                            <li v-for="detail in order.details" :key="detail.id" class="flex justify-between items-start text-body-md">
                                <div class="flex gap-2">
                                    <span class="font-bold text-primary">{{ detail.quantity }}x</span>
                                    <span class="text-on-surface">
                                        {{ detail.product?.product_name }} 
                                        <span class="text-outline text-sm block" v-if="detail.variant?.size">Size: {{ detail.variant.size }}</span>
                                    </span>
                                </div>
                                <span class="text-on-surface font-medium">{{ formatCurrency(detail.unit_price * detail.quantity) }}</span>
                            </li>
                        </ul>
                        
                        <div v-if="order.note" class="bg-surface-container-low p-3 rounded-lg flex gap-2 items-start mt-2">
                            <span class="material-symbols-outlined text-outline text-[18px]">edit_note</span>
                            <p class="text-sm text-on-surface-variant italic">{{ order.note }}</p>
                        </div>

                        <hr class="border-surface-container my-4">
                        
                        <div class="space-y-2 text-body-md">
                            <div class="flex justify-between text-on-surface-variant">
                                <span>Tiền món:</span>
                                <span>{{ formatCurrency(order.final_amount - order.shipping_fee) }}</span>
                            </div>
                            <div class="flex justify-between text-on-surface-variant">
                                <span>Phí ship:</span>
                                <span>{{ formatCurrency(order.shipping_fee) }}</span>
                            </div>
                            <div class="flex justify-between text-on-surface font-bold text-lg pt-2 border-t border-surface-container-highest">
                                <span>Tổng cộng:</span>
                                <span>{{ formatCurrency(order.final_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- XÁC NHẬN GIAO HÀNG (CAMERA) -->
                    <div>
                        <h4 class="text-label-md text-on-surface font-bold uppercase tracking-wider mb-3">Xác nhận hình ảnh</h4>
                        
                        <!-- Input ẩn -->
                        <input ref="fileInput" type="file" accept="image/*" capture="environment" class="hidden" @change="handleFileChange">
                        
                        <!-- Khu vực Upload/Preview -->
                        <div 
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            class="relative border-2 border-dashed rounded-2xl p-6 transition-all duration-200 text-center"
                            :class="[
                                isDragging ? 'border-primary bg-primary-container/20' : 'border-outline-variant bg-surface-container-lowest',
                                photoPreview ? 'border-none p-0' : 'hover:bg-surface-container-low'
                            ]"
                        >
                            <!-- Trạng thái CHƯA CÓ ẢNH -->
                            <div v-if="!photoPreview" class="flex flex-col items-center justify-center py-6">
                                <div class="w-16 h-16 bg-surface-container rounded-full flex items-center justify-center mb-4">
                                    <span class="material-symbols-outlined text-[32px] text-primary">add_a_photo</span>
                                </div>
                                <p class="text-body-md text-on-surface font-medium mb-1">Chụp ảnh xác nhận giao hàng</p>
                                <p class="text-label-sm text-outline mb-4">Hoặc tải lên từ thư viện ảnh</p>
                                
                                <div class="flex gap-3">
                                    <button @click="openCamera" class="px-5 py-2.5 bg-primary text-on-primary rounded-xl text-label-md flex items-center gap-2 hover:bg-inverse-surface transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-[20px]">photo_camera</span> Chụp ảnh
                                    </button>
                                </div>
                            </div>

                            <!-- Trạng thái ĐÃ CÓ ẢNH (Preview) -->
                            <div v-else class="relative rounded-2xl overflow-hidden shadow-soft group">
                                <img :src="photoPreview" alt="Preview" class="w-full h-48 lg:h-64 object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                                    <button @click="openCamera" class="p-3 bg-white text-on-surface rounded-full shadow-lg hover:bg-gray-100" title="Chụp lại">
                                        <span class="material-symbols-outlined">refresh</span>
                                    </button>
                                    <button @click="removePhoto" class="p-3 bg-error text-on-error rounded-full shadow-lg hover:bg-error/90" title="Xóa ảnh">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                                <!-- Nút xoá nổi cho mobile (không cần hover) -->
                                <button @click="removePhoto" class="lg:hidden absolute top-3 right-3 p-2 bg-error text-on-error rounded-full shadow-md">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- NÚT HOÀN THÀNH GIAO HÀNG (Dính đáy trên Mobile) -->
                    <div class="fixed bottom-0 left-0 right-0 lg:static p-4 lg:p-0 bg-surface lg:bg-transparent border-t border-surface-container lg:border-none shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] lg:shadow-none z-20 mt-8">
                        <button 
                            @click="submitComplete" 
                            :disabled="form.processing || !deliveryPhoto"
                            class="w-full bg-primary text-on-primary px-6 py-4 rounded-xl shadow-md text-label-md transition-all flex items-center justify-center gap-2"
                            :class="(form.processing || !deliveryPhoto) ? 'opacity-50 cursor-not-allowed bg-outline' : 'hover:bg-inverse-surface'"
                        >
                            <span v-if="form.processing" class="material-symbols-outlined animate-spin">autorenew</span>
                            <span v-else class="material-symbols-outlined">check_circle</span>
                            HOÀN THÀNH GIAO HÀNG
                        </button>
                    </div>

                </div>
            </div>

            <!-- CỘT PHẢI: BẢN ĐỒ -->
            <!-- Chú ý: Trên mobile nó sẽ đẩy lên trên cùng nhờ flex-col-reverse -->
            <div class="w-full lg:w-1/2 h-[40vh] lg:h-screen relative lg:sticky lg:top-0 bg-surface-container-low border-b lg:border-b-0 border-surface-container-high">
                <iframe v-if="mapsUrl" :src="mapsUrl" class="w-full h-full border-0" allowfullscreen loading="lazy"></iframe>
                <div v-else class="flex flex-col items-center justify-center h-full text-outline gap-3">
                    <span class="material-symbols-outlined text-[48px]">map</span>
                    <p>Không có dữ liệu vị trí bản đồ.</p>
                </div>
                
                <!-- Nút mở App Google Maps -->
                <button 
                    @click="openExternalMap"
                    class="fixed bottom-4 right-8 bg-surface text-on-surface shadow-soft px-5 py-3 rounded-xl text-label-md flex items-center gap-2 hover:bg-surface-container-high transition border border-surface-container"
                >
                    <span class="material-symbols-outlined text-[20px] text-secondary">explore</span> 
                    Chỉ đường Maps
                </button>
            </div>

        </div>
    </ShipperLayout>
</template>