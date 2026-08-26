<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['ADMIN']);
    }

    public function rules(): array
    {
        return [
            // Voucher
            'voucher_gift' => 'nullable|string|max:50',

            // Shop info
            'shop_email'   => 'required|email|max:255',
            'shop_hotline' => 'required|string|max:20',
            'shop_address' => 'required|string|max:255',
            'shop_lat'     => 'nullable|numeric',
            'shop_lng'     => 'nullable|numeric',

            // Delivery & Maps
            'goong_place_id'      => 'nullable|string|max:255',
            'delivery_radius'     => 'required|numeric|min:0',
            'shipping_fees'       => 'required|json', // JSON chứa mảng phí ship theo khoảng cách
            'google_maps_api_key' => 'nullable|string|max:255',

            // Website
            'social_facebook'       => 'nullable|url|max:255',
            'social_zalo'           => 'nullable|string|max:255', // Có thể là SĐT hoặc URL
            'google_maps_embed_url' => 'nullable|string', // Không nên giới hạn max:255 vì URL nhúng map thường rất dài
            
            // 'copyright' => 'required|string|max:255', // Bỏ comment nếu bạn vẫn muốn dùng dù không có trong seeder
        ];
    }

    public function messages(): array
    {
        return [
            'shop_email.required'         => 'Email cửa hàng là bắt buộc.',
            'shop_email.email'            => 'Email cửa hàng không đúng định dạng.',
            'shop_hotline.required'       => 'Hotline là bắt buộc.',
            'shop_address.required'       => 'Địa chỉ cửa hàng là bắt buộc.',
            
            'delivery_radius.required'    => 'Bán kính giao hàng là bắt buộc.',
            'delivery_radius.numeric'     => 'Bán kính giao hàng phải là số.',
            'delivery_radius.min'         => 'Bán kính giao hàng không được nhỏ hơn 0.',
            
            
            'shipping_fees.required'      => 'Phí ship theo khoảng cách là bắt buộc.',
            'shipping_fees.json'          => 'Phí ship phải là định dạng JSON hợp lệ.',
            
            'social_facebook.url'         => 'Link Facebook phải là một URL hợp lệ.',
        ];
    }
}