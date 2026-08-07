<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        // Lấy settings theo từng nhóm
        $shopInfo = Setting::getGroup('shop_info');
        $delivery = Setting::getGroup('delivery');
        $website  = Setting::getGroup('website');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'shop_info' => $shopInfo,
                'delivery'  => $delivery,
                'website'   => $website,
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $data = $request->validated();

        // Mapping key -> group
        $mapping = [
            'shop_email'   => 'shop_info',
            'shop_hotline' => 'shop_info',
            'shop_address' => 'shop_info',
            'shop_lat'     => 'shop_info',
            'shop_lng'     => 'shop_info',
            'goong_place_id'      => 'delivery',
            'delivery_radius'     => 'delivery',
            'cash_payment_limit'  => 'delivery',
            'shipping_fees'       => 'delivery',
            'google_maps_api_key' => 'delivery',
            'social_facebook' => 'website',
            'social_zalo'     => 'website',
            'copyright'       => 'website',
        ];

        $bulk = [];
        foreach ($data as $key => $value) {
            if (isset($mapping[$key])) {
                $bulk[] = [
                    'key'   => $key,
                    'group' => $mapping[$key],
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type'  => is_array($value) ? 'json' : 'text',
                ];
            }
        }

        Setting::upsert($bulk, ['key'], ['value', 'group', 'type']);

        return back()->with('success', 'Cập nhật cài đặt thành công!');
    }
}