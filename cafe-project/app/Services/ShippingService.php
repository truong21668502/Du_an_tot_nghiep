<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ShippingService
{
    // Giá trị mặc định fallback khi chưa có cấu hình trong DB
    public const DEFAULT_SHOP_LAT = 16.0757601;
    public const DEFAULT_SHOP_LNG = 108.1699827;
    public const DEFAULT_MAX_DISTANCE_METERS = 5000;

    protected $shopLat;
    protected $shopLng;
    protected $maxDistanceMeters;
    protected $shippingFees;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Tải cấu hình từ cache hoặc database.
     * Khi admin cập nhật settings, cache sẽ bị xóa (đã cấu hình trong model Setting).
     */
    protected function loadConfig()
    {
        $settings = Cache::remember('shop_settings_grouped', now()->addDays(7), function () {
            return Setting::all()
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key'))
                ->toArray();
        });

        $shopInfo = $settings['shop_info'] ?? [];
        $delivery = $settings['delivery'] ?? [];

        $this->shopLat = (float) ($shopInfo['shop_lat'] ?? self::DEFAULT_SHOP_LAT);
        $this->shopLng = (float) ($shopInfo['shop_lng'] ?? self::DEFAULT_SHOP_LNG);
        // delivery_radius lưu theo km, đổi sang mét
        $this->maxDistanceMeters = (float) ($delivery['delivery_radius'] ?? '5') * 1000;
        $this->shippingFees = json_decode($delivery['shipping_fees'] ?? '[]', true);

        // Nếu chưa có bảng phí, dùng bảng mặc định (để tránh lỗi)
        if (empty($this->shippingFees)) {
            $this->shippingFees = [
                ['from_km' => 0, 'to_km' => 2, 'fee' => 10000],
                ['from_km' => 2, 'to_km' => 3, 'fee' => 15000],
                ['from_km' => 3, 'to_km' => 4, 'fee' => 20000],
                ['from_km' => 4, 'to_km' => 5, 'fee' => 25000],
            ];
        }
    }

    /**
     * Tính phí ship dựa trên khoảng cách (mét) và bảng giá từ DB.
     */
    public function calculateFee(float $distanceMeters): ?float
    {
        $km = $distanceMeters / 1000;

        // Sắp xếp tăng dần theo from_km
        $fees = $this->shippingFees;
        usort($fees, fn ($a, $b) => $a['from_km'] <=> $b['from_km']);

        foreach ($fees as $range) {
            if ($km >= $range['from_km'] && $km <= $range['to_km']) {
                return (float) $range['fee'];
            }
        }

        return null; // Vượt quá bán kính giao hàng hoặc không có khoảng phù hợp
    }

    /**
     * Gọi Goong Direction API để lấy khoảng cách (mét) từ shop đến điểm giao hàng.
     */
    public function getDistanceMeters(float $destLat, float $destLng): ?float
    {
        $response = Http::get('https://rsapi.goong.io/Direction', [
            'api_key' => config('services.goong.api_key'),
            'origin' => $this->shopLat . ',' . $this->shopLng,
            'destination' => $destLat . ',' . $destLng,
            'vehicle' => 'car',
        ]);

        $data = $response->json();

        if (empty($data['routes'][0]['legs'][0]['distance']['value'])) {
            return null;
        }

        return (float) $data['routes'][0]['legs'][0]['distance']['value'];
    }

    // Getter tiện ích nếu cần dùng ở controller hoặc nơi khác
    public function getShopLat(): float
    {
        return $this->shopLat;
    }

    public function getShopLng(): float
    {
        return $this->shopLng;
    }

    public function getMaxDistanceMeters(): float
    {
        return $this->maxDistanceMeters;
    }
}