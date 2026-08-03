<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ShippingService
{
    public const SHOP_LAT = 16.0757601;
    public const SHOP_LNG = 108.1699827;
    public const MAX_DISTANCE_METERS = 5000;

    /**
     * Tính phí ship theo bậc thang dựa trên khoảng cách (mét)
     * 0-2km: 10.000đ | >2-3km: 15.000đ | >3-4km: 20.000đ | >4-5km: 25.000đ
     */
    public function calculateFee(float $distanceMeters): ?float
    {
        $km = $distanceMeters / 1000;

        return match (true) {
            $km <= 2 => 10000.0,
            $km <= 3 => 15000.0,
            $km <= 4 => 20000.0,
            $km <= 5 => 25000.0,
            default => null, // Vượt quá bán kính giao hàng
        };
    }

    /**
     * Gọi Goong Direction API để lấy khoảng cách (mét) từ shop đến điểm giao hàng
     */
    public function getDistanceMeters(float $destLat, float $destLng): ?float
    {
        $response = Http::get('https://rsapi.goong.io/Direction', [
            'api_key' => config('services.goong.api_key'),
            'origin' => self::SHOP_LAT . ',' . self::SHOP_LNG,
            'destination' => $destLat . ',' . $destLng,
            'vehicle' => 'car',
        ]);

        $data = $response->json();

        if (empty($data['routes'][0]['legs'][0]['distance']['value'])) {
            return null;
        }

        return (float) $data['routes'][0]['legs'][0]['distance']['value'];
    }
}