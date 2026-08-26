import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useShipping() {
    const settings = computed(() => usePage().props.settings || {})

    // Vị trí cửa hàng (lat/lng) lấy từ DB
    const shopPos = computed(() => ({
        lat: parseFloat(settings.value.shop_info?.shop_lat || '0'),
        lng: parseFloat(settings.value.shop_info?.shop_lng || '0'),
    }))

    // Bán kính giao hàng tối đa (mét) - chuyển từ km sang mét
    const maxDeliveryDistanceMeters = computed(() => {
        const radiusKm = parseFloat(settings.value.delivery?.delivery_radius || '5')
        return radiusKm * 1000
    })

    // Hàm tính phí ship từ bảng giá trong DB
    function calculateShippingFee(distanceMeters) {
        const fees = JSON.parse(settings.value.delivery?.shipping_fees || '[]')
        const km = distanceMeters / 1000

        // Sắp xếp tăng dần theo from_km để duyệt đúng
        const sortedFees = [...fees].sort((a, b) => a.from_km - b.from_km)

        for (const fee of sortedFees) {
            if (km >= fee.from_km && km <= fee.to_km) {
                return fee.fee
            }
        }

        // Không có khoảng phù hợp hoặc vượt bán kính
        return null
    }

    return {
        shopPos,
        maxDeliveryDistanceMeters,
        calculateShippingFee,
    }
}