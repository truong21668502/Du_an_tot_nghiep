<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use App\models\Coupon;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        // Lấy settings theo từng nhóm
        $voucherGift = Setting::getGroup('voucher_gift');
        $shopInfo    = Setting::getGroup('shop_info');
        $delivery    = Setting::getGroup('delivery');
        $website     = Setting::getGroup('website');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'voucher_gift' => $voucherGift,
                'shop_info'    => $shopInfo,
                'delivery'     => $delivery,
                'website'      => $website,
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $data = $request->validated();

        // Mapping key -> group
        $mapping = [
            // Voucher
            'voucher_gift'          => 'voucher_gift',

            // Shop info
            'shop_email'            => 'shop_info',
            'shop_hotline'          => 'shop_info',
            'shop_address'          => 'shop_info',
            'shop_lat'              => 'shop_info',
            'shop_lng'              => 'shop_info',
            'goong_place_id'        => 'shop_info', 

            // Delivery
            'delivery_radius'       => 'delivery',

            'shipping_fees'         => 'delivery',


            // Website
            'social_facebook'       => 'website',
            'social_zalo'           => 'website',
            'google_maps_embed_url' => 'website',
        ];

        $bulk = [];
        foreach ($data as $key => $value) {
            if (isset($mapping[$key])) {
                // Xác định type của setting để đồng bộ với Seeder
                $type = 'text';
                if (is_array($value) || $key === 'shipping_fees') {
                    $type = 'json';
                } elseif (in_array($key, ['delivery_radius'])) {
                    $type = 'number';
                }

                $bulk[] = [
                    'key'   => $key,
                    'group' => $mapping[$key],
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type'  => $type,
                ];
            }
        }

        Setting::upsert($bulk, ['key'], ['value', 'group', 'type']);
        Cache::forget('shop_settings_grouped');


        return back()->with('success', 'Cập nhật cài đặt thành công!');
    }

        public function searchCoupons($q = null)
        {
            $coupons = Coupon::query()
                ->when($q, function ($queryBuilder) use ($q) {
                    $queryBuilder->where('code', 'like', "%{$q}%");
                })
                ->where(function ($queryBuilder) {
                    // Chỉ lấy mã chưa hết hạn
                    $queryBuilder->whereNull('expiration_date')
                                ->orWhere('expiration_date', '>=', now());
                })
                ->where('status', 'active') 
                ->select('id', 'code', 'discount_type', 'discount_value', 'expiration_date')
                ->when($q, function ($queryBuilder) use ($q) {
                    // ƯU TIÊN: Trùng khớp chính xác lên đầu, khớp từ chữ cái đầu lên thứ hai
                    $queryBuilder->orderByRaw("
                        CASE 
                            WHEN code = ? THEN 1 
                            WHEN code LIKE ? THEN 2 
                            ELSE 3 
                        END ASC
                    ", [$q, "{$q}%"]);
                })
                // Nếu không nhập gì (hoặc các mã có cùng mức độ ưu tiên), sắp xếp theo ngày tạo mới nhất
                ->orderBy('created_at', 'desc') 
                ->limit(4)
                ->get();

            return response()->json($coupons);
        }

}