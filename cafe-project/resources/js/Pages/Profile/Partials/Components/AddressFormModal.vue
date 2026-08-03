<script setup>
import { reactive, watch, ref, onUnmounted, nextTick } from "vue";
import BaseButton from "@/Components/Base/BaseButton.vue";

const props = defineProps({
    show: Boolean,
    editingAddress: Object,
    loading: Boolean,
    errors: Object,
});

const emit = defineEmits(["close", "submit"]);

// ================== CẤU HÌNH & HẰNG SỐ ==================
const GOONG_API_KEY = import.meta.env.VITE_GOONG_API_KEY;
import { SHOP_POS, MAX_DELIVERY_DISTANCE_METERS, calculateShippingFee } from "@/Composables/shipping";

// ================== STATE ==================
const form = reactive({
    receiver_name: "",
    receiver_phone: "",
    address_detail: "",
    ward: "",
    city: "Thành phố Đà Nẵng",
    latitude: null,
    longitude: null,
    goong_place_id: null,
    is_default: false,
});

const searchInput = ref("");
const suggestions = ref([]);
const showSuggestions = ref(false);
const isOutRange = ref(false);
const isManualInputError = ref(false); // Lỗi khi người dùng tự gõ tay không qua gợi ý
const distanceText = ref("");
const durationText = ref("");
const shippingFee = ref(0);
const mapIframeSrc = ref("about:blank");

let debounceTimer = null;

// ================== HELPER FUNCTIONS ==================
function formatCurrency(value) {
    return value.toLocaleString("vi-VN") + "đ";
}

function updateMapIframe(destLat, destLng) {
    if (!destLat || !destLng) {
        mapIframeSrc.value = "about:blank";
        return;
    }
    mapIframeSrc.value = `https://www.google.com/maps/embed?pb=!1m28!1m12!1m3!1d${Math.floor(
        Math.random() * 100000
    )}!2d${destLng}!3d${destLat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m13!3e0!4m5!1s${
        SHOP_POS.lat
    }%2C${SHOP_POS.lng}!2s${SHOP_POS.lat}%2C${SHOP_POS.lng}!3m2!1d${
        SHOP_POS.lat
    }!2d${SHOP_POS.lng}!4m5!1s${destLat}%2C${destLng}!2s${destLat}%2C${destLng}!3m2!1d${destLat}!2d${destLng}!5e0!3m2!1svi!2s!4v${Date.now()}`;
}

// ================== GOONG MAP SERVICES ==================
const handleInput = () => {
    clearTimeout(debounceTimer);
    const query = searchInput.value.trim();

    // BẮT BỘC: Ngay khi người dùng sửa chữ tay, hủy ngay lat/lng cũ
    form.latitude = null;
    form.longitude = null;
    form.goong_place_id = null;
    isManualInputError.value = false;

    if (query.length < 3) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    debounceTimer = setTimeout(async () => {
        try {
            const url = `https://rsapi.goong.io/v2/place/autocomplete?api_key=${GOONG_API_KEY}&input=${encodeURIComponent(
                query
            )}&location=${SHOP_POS.lat},${
                SHOP_POS.lng
            }&radius=5&limit=5&origin=${SHOP_POS.lat},${
                SHOP_POS.lng
            }&more_compound=true`;

            const res = await fetch(url);
            const data = await res.json();

            if (data.predictions && data.predictions.length) {
                suggestions.value = data.predictions;
                showSuggestions.value = true;
            } else {
                suggestions.value = [];
                showSuggestions.value = false;
            }
        } catch (err) {
            console.error("Autocomplete error:", err);
            suggestions.value = [];
            showSuggestions.value = false;
        }
    }, 500);
};

const selectPrediction = async (prediction) => {
    searchInput.value = prediction.description;
    showSuggestions.value = false;
    isManualInputError.value = false;
    await getPlaceDetail(prediction.place_id);
};

const getPlaceDetail = async (placeId) => {
    try {
        const url = `https://rsapi.goong.io/Place/Detail?place_id=${placeId}&api_key=${GOONG_API_KEY}`;
        const res = await fetch(url);
        const data = await res.json();

        if (!data.result || !data.result.geometry) return;

        const { lat, lng } = data.result.geometry.location;
        const compound = data.result.compound || {};

        form.goong_place_id = placeId;
        form.latitude = parseFloat(lat);
        form.longitude = parseFloat(lng);
        form.address_detail = data.result.formatted_address || searchInput.value;
        form.ward = compound.ward || compound.district || "Chưa xác định";
        form.city = compound.province || "Thành phố Đà Nẵng";

        await calculateDirection(lat, lng);
    } catch (err) {
        console.error("Place Detail error:", err);
    }
};

const calculateDirection = async (destLat, destLng) => {
    try {
        const url = `https://rsapi.goong.io/Direction?api_key=${GOONG_API_KEY}&origin=${SHOP_POS.lat},${SHOP_POS.lng}&destination=${destLat},${destLng}&vehicle=car`;
        const res = await fetch(url);
        const data = await res.json();

        if (!data.routes || data.routes.length === 0) {
            isOutRange.value = true;
            mapIframeSrc.value = "about:blank";
            return;
        }

        const leg = data.routes[0].legs[0];
        const distanceMeters = leg.distance.value;

        distanceText.value = leg.distance.text;
        durationText.value = leg.duration.text;

        const fee = calculateShippingFee(distanceMeters);

        if (distanceMeters > MAX_DELIVERY_DISTANCE_METERS || fee === null) {
            isOutRange.value = true;
            mapIframeSrc.value = "about:blank";
        } else {
            isOutRange.value = false;
            shippingFee.value = fee;
            updateMapIframe(destLat, destLng);
        }
    } catch (err) {
        console.error("Direction error:", err);
    }
};

// ================== WATCHERS ==================
watch(
    () => [props.show, props.editingAddress],
    async ([newShow, newEditing]) => {
        if (newShow) {
            // Reset state
            isOutRange.value = false;
            isManualInputError.value = false;
            distanceText.value = "";
            durationText.value = "";
            shippingFee.value = 0;
            showSuggestions.value = false;

            if (newEditing && Object.keys(newEditing).length > 0) {
                const lat = newEditing.latitude ? parseFloat(newEditing.latitude) : null;
                const lng = newEditing.longitude ? parseFloat(newEditing.longitude) : null;

                Object.assign(form, {
                    receiver_name: newEditing.receiver_name || "",
                    receiver_phone: newEditing.receiver_phone || "",
                    address_detail: newEditing.address_detail || "",
                    ward: newEditing.ward || "",
                    city: newEditing.city || "Thành phố Đà Nẵng",
                    latitude: lat,
                    longitude: lng,
                    goong_place_id: newEditing.goong_place_id || null,
                    is_default: !!newEditing.is_default,
                });

                const fullText = [
                    newEditing.address_detail,
                    newEditing.ward,
                    newEditing.city
                ].filter(Boolean).join(", ");

                searchInput.value = fullText || newEditing.address_detail || "";

                await nextTick();

                if (lat && lng) {
                    await calculateDirection(lat, lng);
                } else {
                    updateMapIframe(SHOP_POS.lat, SHOP_POS.lng);
                }
            } else {
                Object.assign(form, {
                    receiver_name: "",
                    receiver_phone: "",
                    address_detail: "",
                    ward: "",
                    city: "Thành phố Đà Nẵng",
                    latitude: null,
                    longitude: null,
                    goong_place_id: null,
                    is_default: false,
                });
                searchInput.value = "";
                updateMapIframe(SHOP_POS.lat, SHOP_POS.lng);
            }
        }
    },
    { immediate: true, deep: true }
);

const handleClickOutside = (e) => {
    if (!e.target.closest(".autocomplete-container")) {
        showSuggestions.value = false;
    }
};

if (typeof window !== "undefined") {
    document.addEventListener("click", handleClickOutside);
}

onUnmounted(() => {
    if (typeof window !== "undefined") {
        document.removeEventListener("click", handleClickOutside);
    }
});

// ================== SUBMIT ==================
const handleSubmit = () => {
    // 1. Kiểm tra BẮT BỘC phải chọn từ gợi ý để có lat/lng
    if (!form.latitude || !form.longitude) {
        isManualInputError.value = true;
        return;
    }

    // 2. Kiểm tra bán kính 5km
    if (isOutRange.value) return;

    form.address_detail = searchInput.value || form.address_detail;

    emit("submit", {
        ...form,
        latitude: parseFloat(form.latitude),
        longitude: parseFloat(form.longitude),
    });
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 md:p-6 backdrop-blur-sm"
        >
            <div
                class="bg-surface w-full h-full md:h-auto md:max-h-[92vh] md:max-w-2xl rounded-none md:rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-5 overflow-y-auto flex flex-col shadow-2xl"
            >
                <!-- Header -->
                <div class="flex justify-between items-center border-b border-outline-variant/20 pb-4">
                    <h3 class="font-serif text-headline-sm text-primary font-semibold">
                        {{ editingAddress ? "Sửa địa chỉ nhận hàng" : "Thêm địa chỉ mới" }}
                    </h3>
                    <button
                        @click="emit('close')"
                        class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-low rounded-full transition-colors"
                    >
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <!-- Form Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tên người nhận -->
                    <div class="space-y-1">
                        <input
                            v-model="form.receiver_name"
                            placeholder="Tên người nhận"
                            class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                            :class="errors?.receiver_name ? 'border-red-500' : 'border-outline-variant/30'"
                        />
                        <p v-if="errors?.receiver_name" class="text-red-500 text-xs font-sans pl-1">
                            {{ Array.isArray(errors.receiver_name) ? errors.receiver_name[0] : errors.receiver_name }}
                        </p>
                    </div>

                    <!-- Số điện thoại -->
                    <div class="space-y-1">
                        <input
                            v-model="form.receiver_phone"
                            placeholder="Số điện thoại"
                            class="w-full px-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                            :class="errors?.receiver_phone ? 'border-red-500' : 'border-outline-variant/30'"
                        />
                        <p v-if="errors?.receiver_phone" class="text-red-500 text-xs font-sans pl-1">
                            {{ Array.isArray(errors.receiver_phone) ? errors.receiver_phone[0] : errors.receiver_phone }}
                        </p>
                    </div>

                    <!-- Ô tìm kiếm địa chỉ tự động (Goong Autocomplete) -->
                    <div class="md:col-span-2 space-y-1 relative autocomplete-container">
                        <div class="relative">
                            <input
                                v-model="searchInput"
                                @input="handleInput"
                                placeholder="Nhập địa chỉ nhận hàng (Ví dụ: 123 Nguyễn Văn Linh...)"
                                class="w-full pl-10 pr-4 py-2.5 bg-surface border rounded-xl font-sans text-body-md focus:outline-none focus:border-secondary"
                                :class="errors?.address_detail || isOutRange || isManualInputError ? 'border-red-500' : 'border-outline-variant/30'"
                            />
                            <span class="material-symbols-outlined absolute left-3 top-3 text-gray-400 text-lg">location_on</span>
                        </div>

                        <!-- Dropdown gợi ý địa chỉ -->
                        <div
                            v-if="showSuggestions && suggestions.length"
                            class="absolute z-50 left-0 right-0 mt-1 bg-surface border border-outline-variant/30 rounded-xl shadow-xl max-h-60 overflow-y-auto"
                        >
                            <div
                                v-for="p in suggestions"
                                :key="p.place_id"
                                @click="selectPrediction(p)"
                                class="p-3 hover:bg-surface-container-low cursor-pointer border-b border-outline-variant/10 text-sm flex items-center gap-2 text-on-surface"
                            >
                                <span class="text-gray-400">📍</span>
                                <span>{{ p.description }}</span>
                            </div>
                        </div>

                        <!-- Cảnh báo nếu tự gõ tay không chọn từ danh sách -->
                        <p v-if="isManualInputError" class="text-red-500 text-xs font-sans pl-1">
                            Vui lòng chọn một địa chỉ cụ thể từ danh sách gợi ý bên dưới để định vị bản đồ.
                        </p>
                        <p v-else-if="errors?.address_detail" class="text-red-500 text-xs font-sans pl-1">
                            {{ Array.isArray(errors.address_detail) ? errors.address_detail[0] : errors.address_detail }}
                        </p>
                    </div>

                    <!-- Cảnh báo nếu nằm ngoài bán kính 5km -->
                    <div v-if="isOutRange" class="md:col-span-2 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2 text-red-700 text-sm">
                        <span class="material-symbols-outlined text-lg">warning</span>
                        <span>Địa chỉ này vượt quá bán kính giao hàng (tối đa 5km từ cửa hàng). Vui lòng chọn địa điểm khác!</span>
                    </div>

                    <!-- Panel hiển thị khoảng cách và phí ship -->
                    <div v-if="distanceText && !isOutRange" class="md:col-span-2 p-3 bg-surface-container-low border border-outline-variant/20 rounded-xl grid grid-cols-3 gap-2 text-center text-xs md:text-sm">
                        <div>
                            <span class="text-on-surface-variant block">Khoảng cách</span>
                            <strong class="text-primary font-semibold">{{ distanceText }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant block">Thời gian giao</span>
                            <strong class="text-primary font-semibold">{{ durationText }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant block">Phí vận chuyển</span>
                            <strong class="text-secondary font-bold">{{ formatCurrency(shippingFee) }}</strong>
                        </div>
                    </div>

                    <!-- Iframe Google Map dẫn đường -->
                    <div class="md:col-span-2 w-full h-48 md:h-56 rounded-xl overflow-hidden border border-outline-variant/30 relative">
                        <iframe
                            :src="mapIframeSrc"
                            class="w-full h-full border-0"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                    </div>

                    <!-- Checkbox địa chỉ mặc định -->
                    <label class="md:col-span-2 flex items-center gap-2 cursor-pointer pt-1">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            class="w-4 h-4 rounded border-outline-variant/50 text-primary focus:ring-secondary/30"
                        />
                        <span class="font-sans text-label-sm text-on-surface-variant">Đặt làm địa chỉ mặc định</span>
                    </label>

                    <!-- GHI CHÚ BÁN KÍNH GIAO HÀNG (NOTE DƯỚI CÙNG) -->
                    <div class="md:col-span-2 pt-1">
                        <p class="font-sans text-xs italic text-amber-600 font-medium flex items-center gap-1">
                            <span>*</span> Chỉ hỗ trợ giao hàng trong bán kính 5km trở xuống.
                        </p>
                    </div>
                </div>

                <!-- Footer nút bấm -->
                <div class="flex gap-3 justify-end pt-4 border-t border-outline-variant/20">
                    <button
                        @click="emit('close')"
                        type="button"
                        class="px-5 py-2.5 border border-outline-variant/30 rounded-full font-sans text-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors"
                    >
                        Hủy
                    </button>
                    <BaseButton
                        @click="handleSubmit"
                        variant="primary"
                        :disabled="loading || isOutRange"
                        class="px-6 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ loading ? "Đang lưu..." : editingAddress ? "Cập nhật" : "Lưu địa chỉ" }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>