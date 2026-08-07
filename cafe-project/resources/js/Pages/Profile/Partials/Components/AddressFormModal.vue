<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import ShipperLayout from '@/Layouts/ShipperLayout.vue'

const props = defineProps({
    order: Object,
    shopLat: Number,
    shopLng: Number,
})

// Camera/upload
const deliveryPhoto = ref(null)
const photoPreview = ref(null)
const fileInput = ref(null)

const handleFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        deliveryPhoto.value = file
        photoPreview.value = URL.createObjectURL(file)
    }
}

const openCamera = () => {
    fileInput.value.click()
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

// Tính khoảng cách (km) giữa 2 điểm để suy ra zoom
function calcDistanceKm(lat1, lng1, lat2, lng2) {
    const R = 6371
    const dLat = (lat2 - lat1) * Math.PI / 180
    const dLng = (lng2 - lng1) * Math.PI / 180
    const a = Math.sin(dLat / 2) ** 2 +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng / 2) ** 2
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
    return R * c
}

// Tạo iframe Google Maps miễn phí (không API key)
const mapsUrl = computed(() => {
    const destLat = parseFloat(props.order.latitude)
    const destLng = parseFloat(props.order.longitude)
    const shopLat = parseFloat(props.shopLat)
    const shopLng = parseFloat(props.shopLng)

    if (!destLat || !destLng || !shopLat || !shopLng) return ''

    // Tính zoom dựa trên khoảng cách (công thức thực nghiệm)
    const distanceKm = calcDistanceKm(shopLat, shopLng, destLat, destLng)
    // zoom từ 12 (rất xa) đến 18 (gần), tính bằng 16 - log2(km * 1000 / 150)
    let zoom = Math.round(16 - Math.log2(distanceKm * 1000 / 150))
    zoom = Math.min(18, Math.max(12, zoom)) // giới hạn 12-18

    // Timestamp để tránh cache
    const timestamp = Date.now()

    // Tọa độ trung tâm là điểm đến (dest)
    return `https://www.google.com/maps/embed?pb=!1m28!1m12!1m3!1d${zoom}!2d${destLng}!3d${destLat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m13!3e0!4m5!1s${shopLat}%2C${shopLng}!2s${shopLat}%2C${shopLng}!3m2!1d${shopLat}!2d${shopLng}!4m5!1s${destLat}%2C${destLng}!2s${destLat}%2C${destLng}!3m2!1d${destLat}!2d${destLng}!5e0!3m2!1svi!2s!4v${timestamp}`
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
    <ShipperLayout>
        <Head title="Giao hàng" />
        <div class="flex flex-col lg:flex-row h-full">
            <!-- Cột trái: Thông tin đơn -->
            <div class="lg:w-1/2 p-6 bg-white border-r overflow-y-auto">
                <h2 class="text-xl font-bold mb-4">Đơn hàng #{{ order.id }}</h2>
                <div class="space-y-3 text-sm">
                    <p><strong>Khách hàng:</strong> {{ order.user?.full_name || '...' }}</p>
                    <p><strong>SĐT:</strong> {{ order.user?.phone_number || order.receiver_phone }}</p>
                    <p><strong>Địa chỉ:</strong> {{ order.address_detail }}, {{ order.ward }}, {{ order.city }}</p>
                    <p><strong>Tiền ship:</strong> {{ Number(order.shipping_fee).toLocaleString('vi-VN') }}đ</p>
                    <p><strong>Tổng tiền:</strong> {{ Number(order.final_amount).toLocaleString('vi-VN') }}đ</p>
                    <p><strong>Thanh toán:</strong> {{ order.payment?.payment_method === 'CASH' ? 'Tiền mặt' : 'Chuyển khoản' }}</p>
                </div>

                <div class="mt-4">
                    <h4 class="font-semibold mb-2">Sản phẩm:</h4>
                    <ul class="space-y-2">
                        <li v-for="detail in order.details" :key="detail.id" class="flex justify-between text-sm">
                            <span>{{ detail.product?.product_name }} ({{ detail.variant?.size }}) x{{ detail.quantity }}</span>
                            <span>{{ Number(detail.unit_price * detail.quantity).toLocaleString('vi-VN') }}đ</span>
                        </li>
                    </ul>
                    <p v-if="order.note" class="text-xs text-gray-500 mt-2">Ghi chú: {{ order.note }}</p>
                </div>

                <div class="mt-6 border-t pt-4">
                    <h4 class="font-semibold mb-3">Xác nhận giao hàng</h4>
                    <div class="flex flex-col items-center">
                        <input ref="fileInput" type="file" accept="image/*" capture="environment" class="hidden" @change="handleFileChange">
                        <div class="flex gap-4 mb-4">
                            <button type="button" @click="openCamera" class="px-4 py-2 bg-gray-200 rounded-full text-sm flex items-center gap-2">
                                <span class="material-symbols-outlined">camera_alt</span> Chụp ảnh
                            </button>
                            <label class="px-4 py-2 bg-gray-200 rounded-full text-sm flex items-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined">upload</span> Tải lên
                                <input type="file" accept="image/*" class="hidden" @change="handleFileChange">
                            </label>
                        </div>
                        <div v-if="photoPreview" class="mb-4">
                            <img :src="photoPreview" alt="Preview" class="max-w-xs rounded-lg shadow">
                        </div>
                        <button @click="submitComplete" :disabled="form.processing"
                                class="bg-primary text-white px-6 py-2.5 rounded-full shadow hover:bg-primary-dark transition disabled:opacity-50">
                            Hoàn thành giao hàng
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Bản đồ -->
            <div class="lg:w-1/2 h-[70vh] lg:h-auto relative">
                <iframe v-if="mapsUrl" :src="mapsUrl" class="w-full h-full border-0" allowfullscreen loading="lazy"></iframe>
                <div v-else class="flex items-center justify-center h-full text-gray-400">
                    Không có dữ liệu bản đồ.
                </div>
                <button @click="openExternalMap"
                        class="absolute bottom-4 right-4 bg-white shadow-lg px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined">open_in_new</span> Mở Google Maps
                </button>
            </div>
        </div>
    </ShipperLayout>
</template>