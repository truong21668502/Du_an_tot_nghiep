<script setup>
import { ref, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import AdminLayout from '@/Layouts/AdminLayout.vue' // giả sử có layout admin

const props = defineProps({
    settings: Object
})

const form = useForm({
    shop_email: props.settings.shop_info?.shop_email || '',
    shop_hotline: props.settings.shop_info?.shop_hotline || '',
    shop_address: props.settings.shop_info?.shop_address || '',
    shop_lat: props.settings.shop_info?.shop_lat || '',
    shop_lng: props.settings.shop_info?.shop_lng || '',
    goong_place_id: props.settings.delivery?.goong_place_id || '',
    delivery_radius: props.settings.delivery?.delivery_radius || '',
    cash_payment_limit: props.settings.delivery?.cash_payment_limit || '',
    shipping_fees: props.settings.delivery?.shipping_fees || '[]',
    google_maps_api_key: props.settings.delivery?.google_maps_api_key || '',
    social_facebook: props.settings.website?.social_facebook || '',
    social_zalo: props.settings.website?.social_zalo || '',
    copyright: props.settings.website?.copyright || '',
})

// Goong Autocomplete
const goongApiKey = import.meta.env.VITE_GOONG_API_KEY || ''
const addressInput = ref(null)

onMounted(() => {
    if (goongApiKey && addressInput.value) {
        const script = document.createElement('script')
        script.src = `https://api.goong.io/place/autocomplete/js?key=${goongApiKey}&libraries=places`
        script.onload = initAutocomplete
        document.head.appendChild(script)
    }
})

function initAutocomplete() {
    const autocomplete = new goong.sdk.maps.places.Autocomplete(addressInput.value, {
        componentRestrictions: { country: 'vn' }
    })
    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (place.geometry) {
            form.shop_lat = place.geometry.location.lat()
            form.shop_lng = place.geometry.location.lng()
            form.shop_address = place.formatted_address || place.name
            form.goong_place_id = place.place_id
        }
    })
}

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => toast.success('Đã lưu cài đặt!'),
        onError: (errors) => {
            Object.values(errors).forEach(err => toast.error(err))
        }
    })
}

// Shipping fees management
const shippingFees = ref(JSON.parse(form.shipping_fees || '[]'))

function addFeeRange() {
    shippingFees.value.push({ from_km: '', to_km: '', fee: '' })
}
function removeFeeRange(index) {
    shippingFees.value.splice(index, 1)
}
function updateShippingFees() {
    form.shipping_fees = JSON.stringify(shippingFees.value)
}
</script>

<template>
    <AdminLayout title="Cài đặt hệ thống">
        <div class="max-w-4xl mx-auto py-6 px-4">
            <h2 class="text-2xl font-bold mb-6">Cài đặt hệ thống</h2>
            <form @submit.prevent="submit" class="space-y-8">
                <!-- 1. Shop Info -->
                <section class="bg-white rounded-xl p-6 shadow">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">store</span> Thông tin cửa hàng
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input v-model="form.shop_email" type="email" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hotline</label>
                            <input v-model="form.shop_hotline" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Địa chỉ (Goong Autocomplete)</label>
                            <input ref="addressInput" v-model="form.shop_address" type="text" placeholder="Nhập địa chỉ..." class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input v-model="form.shop_lat" type="text" class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input v-model="form.shop_lng" type="text" class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50" readonly>
                        </div>
                    </div>
                </section>

                <!-- 2. Delivery -->
                <section class="bg-white rounded-xl p-6 shadow">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">local_shipping</span> Giao hàng
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Goong Place ID</label>
                            <input v-model="form.goong_place_id" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bán kính giao hàng (km)</label>
                                <input v-model="form.delivery_radius" type="number" step="0.1" min="0" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Giới hạn thanh toán tiền mặt (VNĐ)</label>
                                <input v-model="form.cash_payment_limit" type="number" min="0" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary" required>
                            </div>
                        </div>

                        <!-- Shipping fees ranges -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phí ship theo khoảng cách</label>
                            <div v-for="(fee, idx) in shippingFees" :key="idx" class="flex items-center gap-3 mb-2">
                                <input v-model="fee.from_km" type="number" step="0.1" placeholder="Từ (km)" class="w-24 rounded-lg border-gray-300" @input="updateShippingFees">
                                <span>-</span>
                                <input v-model="fee.to_km" type="number" step="0.1" placeholder="Đến (km)" class="w-24 rounded-lg border-gray-300" @input="updateShippingFees">
                                <input v-model="fee.fee" type="number" placeholder="Phí (VNĐ)" class="w-32 rounded-lg border-gray-300" @input="updateShippingFees">
                                <button type="button" @click="removeFeeRange(idx)" class="text-red-500 hover:text-red-700">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                            <button type="button" @click="addFeeRange" class="text-sm text-primary hover:underline mt-2">
                                + Thêm khoảng cách
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Google Maps API Key (cho iframe)</label>
                            <input v-model="form.google_maps_api_key" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                </section>

                <!-- 3. Website -->
                <section class="bg-white rounded-xl p-6 shadow">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">language</span> Website
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Facebook URL</label>
                            <input v-model="form.social_facebook" type="url" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Zalo</label>
                            <input v-model="form.social_zalo" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Copyright</label>
                            <input v-model="form.copyright" type="text" class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary" required>
                        </div>
                    </div>
                </section>

                <div class="text-right">
                    <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-full shadow hover:bg-primary-dark transition">
                        Lưu cài đặt
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>