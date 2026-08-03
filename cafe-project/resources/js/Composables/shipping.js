export const SHOP_POS = { lat: 16.0757601, lng: 108.1699827 };
export const MAX_DELIVERY_DISTANCE_METERS = 5000; // 5km

/**
 * Tính phí ship theo bậc thang dựa trên khoảng cách (mét)
 * 0-2km: 10.000đ | >2-3km: 15.000đ | >3-4km: 20.000đ | >4-5km: 25.000đ
 * Trả về null nếu vượt quá bán kính giao hàng
 */
export function calculateShippingFee(distanceMeters) {
    const km = distanceMeters / 1000;

    if (km <= 2) return 10000;
    if (km <= 3) return 15000;
    if (km <= 4) return 20000;
    if (km <= 5) return 25000;
    return null;
}