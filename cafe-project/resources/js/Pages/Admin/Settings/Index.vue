<script setup>
import { ref, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import AdminLayout from '../Layout/AdminLayout.vue'
import axios from "axios";

const props = defineProps({
    settings: Object
})

const activeTab = ref('shop_info') // Tab mặc định

const tabs = [
    { id: 'shop_info', name: 'Thông tin cửa hàng', icon: 'store' },
    { id: 'delivery', name: 'Giao hàng & Phí ship', icon: 'local_shipping' },
    { id: 'website', name: 'Website & Mạng xã hội', icon: 'language' },
    { id: 'voucher', name: 'Khuyến mãi & Quà tặng', icon: 'redeem' },
]

const form = useForm({
    // Shop info
    shop_email: props.settings.shop_info?.shop_email || '',
    shop_hotline: props.settings.shop_info?.shop_hotline || '',
    shop_address: props.settings.shop_info?.shop_address || '',
    shop_lat: props.settings.shop_info?.shop_lat || '',
    shop_lng: props.settings.shop_info?.shop_lng || '',
    goong_place_id: props.settings.shop_info?.goong_place_id || '',

    // Delivery
    delivery_radius: props.settings.delivery?.delivery_radius || '',
    shipping_fees: props.settings.delivery?.shipping_fees || '[]',

    // Website
    social_facebook: props.settings.website?.social_facebook || '',
    social_zalo: props.settings.website?.social_zalo || '',
    google_maps_embed_url: props.settings.website?.google_maps_embed_url || '',

    // Voucher
    voucher_gift: props.settings.voucher_gift?.voucher_gift || '',
})

// Goong Autocomplete
const addressInput = ref(null)

const goongApiKey = import.meta.env.VITE_GOONG_API_KEY || ''

const suggestions = ref([])
const showSuggestions = ref(false)

const couponSuggestions = ref([])
const showCouponSuggestions = ref(false)
let couponDebounceTimer = null
let debounceTimer = null

function handleAddressInput() {
    const query = form.shop_address?.trim()

    clearTimeout(debounceTimer)

    if (!query || query.length < 2) {
        suggestions.value = []
        showSuggestions.value = false
        return
    }

    debounceTimer = setTimeout(async () => {
        try {
            const url = `https://rsapi.goong.io/v2/place/autocomplete?api_key=${goongApiKey}&input=${encodeURIComponent(query)}&more_compound=true`

            const res = await fetch(url)
            const data = await res.json()

            if (data.predictions?.length) {
                suggestions.value = data.predictions
                showSuggestions.value = true
            } else {
                suggestions.value = []
                showSuggestions.value = false
            }
        } catch (err) {
            console.error('Autocomplete error:', err)
            suggestions.value = []
            showSuggestions.value = false
        }
    }, 500)
}

async function selectAddress(suggestion) {
    // Cập nhật địa chỉ hiển thị từ gợi ý
    const mainText = suggestion.structured_formatting?.main_text || ''
    const secondaryText = suggestion.structured_formatting?.secondary_text || ''
    form.shop_address = mainText
        ? (secondaryText ? `${mainText}, ${secondaryText}` : mainText)
        : suggestion.description

    // Ẩn gợi ý
    showSuggestions.value = false
    suggestions.value = []

    // Gọi Goong Place Detail để lấy tọa độ
    try {
        const url = `https://rsapi.goong.io/v2/place/detail?place_id=${suggestion.place_id}&api_key=${goongApiKey}`
        const res = await fetch(url)
        const data = await res.json()

        if (data.result && data.result.geometry?.location) {
            const location = data.result.geometry.location
            form.shop_lat = location.lat
            form.shop_lng = location.lng
            form.goong_place_id = suggestion.place_id
        } else {
            toast.error('Không thể lấy tọa độ từ địa chỉ này')
        }
    } catch (err) {
        console.error('Place detail error:', err)
        toast.error('Đã xảy ra lỗi khi lấy tọa độ')
    }
}

// Shipping fees management
const shippingFees = ref(JSON.parse(form.shipping_fees || '[]'))

function addFeeRange() {
    shippingFees.value.push({ from_km: '', to_km: '', fee: '' })
}

function removeFeeRange(index) {
    shippingFees.value.splice(index, 1)
}

const submit = () => {
    // --- BẮT ĐẦU VALIDATE PHÍ SHIP TOÀN DIỆN ---
    if (shippingFees.value.length > 0) {
        // 1. Chuyển đổi dữ liệu sang dạng Number
        const parsedFees = shippingFees.value.map((fee, index) => ({
            originalIndex: index + 1,
            from_km: parseFloat(fee.from_km),
            to_km: parseFloat(fee.to_km),
            fee: parseFloat(fee.fee)
        }));

        // 2. Kiểm tra dữ liệu rỗng và logic cơ bản từng dòng
        for (const item of parsedFees) {
            if (isNaN(item.from_km) || isNaN(item.to_km) || isNaN(item.fee)) {
                activeTab.value = 'delivery';
                toast.error(`Vui lòng nhập đầy đủ số liệu ở khoảng cách thứ ${item.originalIndex}`);
                return;
            }
            if (item.from_km < 0 || item.to_km < 0 || item.fee < 0) {
                activeTab.value = 'delivery';
                toast.error(`Số liệu ở khoảng cách thứ ${item.originalIndex} không được là số âm.`);
                return;
            }
            if (item.from_km >= item.to_km) {
                activeTab.value = 'delivery';
                toast.error(`Lỗi ở dòng ${item.originalIndex}: Khoảng cách "Từ" (${item.from_km}km) phải nhỏ hơn "Đến" (${item.to_km}km).`);
                return;
            }
        }

        // 3. Sắp xếp lại theo "Từ" tăng dần để kiểm tra chuỗi liên tục
        parsedFees.sort((a, b) => a.from_km - b.from_km);

        // 4. Bắt buộc khoảng đầu tiên phải bắt đầu từ 0km
        if (parsedFees[0].from_km !== 0) {
            activeTab.value = 'delivery';
            toast.error(`Khoảng cách đầu tiên phải bắt đầu từ 0 km (hiện tại bắt đầu từ ${parsedFees[0].from_km} km).`);
            return;
        }

        // 5. Kiểm tra trùng lặp (Overlap) VÀ khoảng trống (Gap)
        for (let i = 1; i < parsedFees.length; i++) {
            const prev = parsedFees[i - 1];
            const current = parsedFees[i];

            // Bị trùng lấn: ví dụ 0-3km và 2-5km
            if (current.from_km < prev.to_km) {
                activeTab.value = 'delivery';
                toast.error(`Bị trùng lặp: Khoảng (${current.from_km} - ${current.to_km}km) đè lên khoảng (${prev.from_km} - ${prev.to_km}km).`);
                return;
            }

            // Bị hở/trống: ví dụ 0-2km và 3-5km (khoảng 2 -> 3km không có giá)
            if (current.from_km > prev.to_km) {
                activeTab.value = 'delivery';
                toast.error(`Bị gián đoạn: Khoảng cách từ ${prev.to_km}km đến ${current.from_km}km đang bị thiếu phí ship!`);
                return;
            }
        }

        // 6. (Tùy chọn) Kiểm tra xem khoảng cuối cùng có khớp với bán kính tối đa không
        const maxRadius = parseFloat(form.delivery_radius);
        if (!isNaN(maxRadius) && maxRadius > 0) {
            const lastToKm = parsedFees[parsedFees.length - 1].to_km;
            if (lastToKm < maxRadius) {
                activeTab.value = 'delivery';
                toast.error(`Bán kính giao hàng là ${maxRadius}km nhưng cấu hình phí ship mới dừng ở ${lastToKm}km.`);
                return;
            }
        }
    }
    // --- KẾT THÚC VALIDATE ---

    // Chuyển mảng object thành chuỗi JSON trước khi submit
    form.shipping_fees = JSON.stringify(shippingFees.value)

    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => toast.success('Cập nhật cài đặt thành công!'),
        onError: (errors) => {
            const errorKeys = Object.keys(errors)
            if (errorKeys.some(key => key.includes('shop_'))) activeTab.value = 'shop_info'
            else if (errorKeys.some(key => ['delivery_radius', 'shipping_fees'].includes(key))) activeTab.value = 'delivery'

            Object.values(errors).forEach(err => toast.error(err))
        }
    })
}
const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

function handleCouponInput() {
    const query = form.voucher_gift?.trim() || ''

    clearTimeout(couponDebounceTimer)

    couponDebounceTimer = setTimeout(async () => {

        try {
            const url = `/quan-tri/api/coupons/search/${encodeURIComponent(query)}`

            // Sử dụng axios.get thay cho fetch
            const res = await axios.get(url)

            // Axios tự động parse JSON và lưu dữ liệu trong thuộc tính .data
            const data = res.data

            if (data && data.length > 0) {
                couponSuggestions.value = data
                showCouponSuggestions.value = true
            } else {
                couponSuggestions.value = []
                showCouponSuggestions.value = false
            }
        } catch (err) {
            console.error('Lỗi tìm kiếm coupon:', err)
            couponSuggestions.value = []
            showCouponSuggestions.value = false
        }
    }, 300)
}


function selectCoupon(coupon) {
    form.voucher_gift = coupon.code
    showCouponSuggestions.value = false
    couponSuggestions.value = []
}
</script>

<template>
    <AdminLayout>
        <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Cấu hình hệ thống</h2>
                    <p class="mt-1 text-sm text-gray-500">Quản lý các thông số hoạt động của cửa hàng và website.</p>
                </div>

                <!-- Sticky Submit Button cho Desktop -->
                <button @click="submit" :disabled="form.processing"
                    class="hidden md:flex items-center justify-center gap-2 bg-primary text-white px-6 py-2.5 rounded-lg shadow-sm hover:bg-primary/90 focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all disabled:opacity-70 disabled:cursor-not-allowed font-medium">
                    <span v-if="form.processing"
                        class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                    <span v-else class="material-symbols-outlined text-sm">save</span>
                    Lưu thay đổi
                </button>
            </div>

            <div class="flex flex-col md:flex-row gap-8">
                <!-- Sidebar Tabs -->
                <div class="w-full md:w-1/4 shrink-0">
                    <nav class="flex flex-col space-y-1 bg-white p-2 rounded-xl shadow-sm border border-gray-100">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-colors text-left"
                            :class="activeTab === tab.id ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                            <span class="material-symbols-outlined text-xl"
                                :class="activeTab === tab.id ? 'text-primary' : 'text-gray-400'">
                                {{ tab.icon }}
                            </span>
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>

                <!-- Form Content -->
                <div class="w-full md:w-3/4">
                    <form @submit.prevent="submit"
                        class="bg-white rounded-xl shadow-sm border border-gray-100 ">

                        <!-- 1. Shop Info -->
                        <!-- Dùng v-show để đảm bảo DOM của form input luôn tồn tại cho API Map -->
                        <div v-show="activeTab === 'shop_info'" class="p-6 md:p-8 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-4">Thông tin liên hệ & Địa chỉ
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email cửa hàng <span
                                            class="text-red-500">*</span></label>
                                    <input v-model="form.shop_email" type="email"
                                        class="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2"
                                        placeholder="contact@cuahang.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hotline <span
                                            class="text-red-500">*</span></label>
                                    <input v-model="form.shop_hotline" type="text"
                                        class="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2"
                                        placeholder="0909 123 456">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ cửa hàng <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span
                                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">location_on</span>
                                        <input v-model="form.shop_address" @input="handleAddressInput" type="text"
                                            class="w-full pl-10 rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2"
                                            placeholder="Nhập địa chỉ cửa hàng...">
                                        <div v-if="showSuggestions && suggestions.length"
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                            <button v-for="suggestion in suggestions" :key="suggestion.place_id"
                                                type="button" @click="selectAddress(suggestion)"
                                                class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b last:border-b-0">
                                                <div class="font-medium text-gray-800">
                                                    {{ suggestion.structured_formatting?.main_text ||
                                                    suggestion.description }}
                                                </div>

                                                <div v-if="suggestion.structured_formatting?.secondary_text"
                                                    class="text-sm text-gray-500 mt-1">
                                                    {{ suggestion.structured_formatting.secondary_text }}
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">Hệ thống sử dụng Goong Maps để tự động lấy tọa
                                        độ hỗ trợ tính phí ship.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vĩ độ (Latitude)</label>
                                    <input v-model="form.shop_lat" type="text"
                                        class="w-full rounded-lg border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed shadow-sm p-2"
                                        readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kinh độ
                                        (Longitude)</label>
                                    <input v-model="form.shop_lng" type="text"
                                        class="w-full rounded-lg border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed shadow-sm p-2"
                                        readonly>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Goong Place ID</label>
                                    <input v-model="form.goong_place_id" type="text"
                                        class="w-full rounded-lg border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed shadow-sm p-2"
                                        readonly placeholder="Tự động sinh khi chọn địa chỉ">
                                </div>
                            </div>
                        </div>

                        <!-- 2. Delivery -->
                        <div v-show="activeTab === 'delivery'" class="p-6 md:p-8 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-4">Cấu hình Giao hàng
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bán kính giao hàng tối
                                        đa (km) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input v-model="form.delivery_radius" type="number" step="0.1" min="0"
                                            class="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2 pr-12">
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">km</span>
                                    </div>
                                </div>


                                <div class="md:col-span-2 mt-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <label class="block text-sm font-medium text-gray-700">Bảng giá phí vận
                                            chuyển</label>
                                        <button type="button" @click="addFeeRange"
                                            class="flex items-center gap-1 text-sm font-medium text-primary hover:text-primary/80 bg-primary/10 px-3 py-1.5 rounded-md transition-colors">
                                            <span class="material-symbols-outlined text-sm">add</span> Thêm khoảng cách
                                        </button>
                                    </div>

                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                        <div v-if="shippingFees.length === 0"
                                            class="text-center py-4 text-sm text-gray-500">
                                            Chưa có thiết lập phí ship nào. Vui lòng thêm mức phí mới.
                                        </div>

                                        <div class="space-y-3">
                                            <div v-for="(fee, idx) in shippingFees" :key="idx"
                                                class="flex flex-wrap sm:flex-nowrap items-center gap-3 bg-white p-3 rounded-lg shadow-sm border border-gray-100">
                                                <div class="flex items-center gap-2 flex-1">
                                                    <span class="text-sm text-gray-500 font-medium w-8">Từ</span>
                                                    <input v-model="fee.from_km" type="number" step="0.1"
                                                        class="w-full rounded-md border-gray-300 focus:ring-primary focus:border-primary text-sm shadow-sm"
                                                        placeholder="0">
                                                    <span class="text-sm text-gray-500 font-medium">km</span>
                                                </div>

                                                <div class="flex items-center gap-2 flex-1">
                                                    <span
                                                        class="text-sm text-gray-500 font-medium w-8 text-center">Đến</span>
                                                    <input v-model="fee.to_km" type="number" step="0.1"
                                                        class="w-full rounded-md border-gray-300 focus:ring-primary focus:border-primary text-sm shadow-sm"
                                                        placeholder="5">
                                                    <span class="text-sm text-gray-500 font-medium">km</span>
                                                </div>

                                                <div class="flex items-center gap-2 flex-1">
                                                    <span class="text-sm text-gray-500 font-medium w-8">Phí</span>
                                                    <input v-model="fee.fee" type="number"
                                                        class="w-full rounded-md border-gray-300 focus:ring-primary focus:border-primary text-sm shadow-sm"
                                                        placeholder="15000">
                                                    <span class="text-sm text-gray-500 font-medium">VNĐ</span>
                                                </div>

                                                <button type="button" @click="removeFeeRange(idx)"
                                                    class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                                    title="Xóa mức phí này">
                                                    <span class="material-symbols-outlined text-lg">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <!-- 3. Website -->
                        <div v-show="activeTab === 'website'" class="p-6 md:p-8 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-4">Thông tin Mạng xã hội & Khác
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                        </svg>
                                        Facebook Fanpage URL
                                    </label>
                                    <input v-model="form.social_facebook" type="url"
                                        class="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2"
                                        placeholder="https://facebook.com/cuahang">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                                        <span class="text-blue-500 font-bold text-xs tracking-wider">ZALO</span>
                                        Link Zalo / SĐT Zalo
                                    </label>
                                    <input v-model="form.social_zalo" type="text"
                                        class="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2"
                                        placeholder="https://zalo.me/...">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã nhúng Bản đồ Google
                                        (Iframe URL)</label>
                                    <textarea v-model="form.google_maps_embed_url" rows="3"
                                        class="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2 font-mono text-sm"
                                        placeholder="https://www.google.com/maps/embed?..."></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Chỉ copy phần link src="..." trong mã nhúng
                                        của Google Maps.</p>
                                </div>
                            </div>
                        </div>
                        <!-- 4. Voucher -->
                        <div v-show="activeTab === 'voucher'" class="p-6 md:p-8 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-4">Khuyến mãi & Quà tặng</h3>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mã Voucher cho thành viên
                                    mới đăng ký</label>

                                <div class="relative w-full md:w-1/2">
                                    <!-- Thêm @input và @focus để bắt sự kiện lấy danh sách -->
                                    <input v-model="form.voucher_gift" @input="handleCouponInput"
                                        @focus="handleCouponInput" type="text"
                                        class="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary shadow-sm p-2 uppercase font-bold text-primary"
                                        placeholder="WELCOME10">

                                    <!-- Box hiển thị danh sách gợi ý -->
                                    <div v-if="showCouponSuggestions && couponSuggestions.length"
                                        class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                        <button v-for="coupon in couponSuggestions" :key="coupon.id" type="button"
                                            @click="selectCoupon(coupon)"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b last:border-b-0">
                                            <div class="font-medium text-gray-800 flex justify-between items-center">
                                                <span>{{ coupon.code }}</span>
                                                <!-- Hiển thị giá trị giảm % hoặc VNĐ -->
                                                <span
                                                    class="text-primary text-sm font-bold bg-primary/10 px-2 py-0.5 rounded">
                                                    {{ coupon.discount_type === 'PERCENTAGE' ? coupon.discount_value + '%'
                                                    : formatCurrency(coupon.discount_value) }}
                                                </span>
                                            </div>

                                            <div v-if="coupon.expiration_date"
                                                class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                                HSD: {{ new Date(coupon.expiration_date).toLocaleDateString('vi-VN') }}
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <p class="mt-2 text-xs text-gray-500">Voucher này sẽ được tự động áp dụng hoặc gửi cho
                                    khách hàng sau khi tạo tài khoản thành công.</p>
                            </div>
                        </div>

                        <!-- Vùng footer mờ để submit trên Mobile -->
                        <div class="md:hidden bg-gray-50 p-4 border-t border-gray-200">
                            <button type="submit" :disabled="form.processing"
                                class="w-full flex items-center justify-center gap-2 bg-primary text-white px-6 py-3 rounded-lg shadow font-medium disabled:opacity-70">
                                <span v-if="form.processing"
                                    class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                                Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped></style>