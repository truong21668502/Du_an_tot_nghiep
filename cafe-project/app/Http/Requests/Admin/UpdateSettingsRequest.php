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
            // Shop info
            'shop_email'   => 'required|email',
            'shop_hotline' => 'required|string|max:20',
            'shop_address' => 'required|string|max:255',
            'shop_lat'     => 'nullable|numeric',
            'shop_lng'     => 'nullable|numeric',

            // Delivery
            'goong_place_id'      => 'nullable|string',
            'delivery_radius'     => 'required|numeric|min:0',
            'cash_payment_limit'  => 'required|numeric|min:0',
            'shipping_fees'       => 'required|json', // JSON chứa mảng phí ship theo khoảng cách
            'google_maps_api_key' => 'nullable|string',

            // Website
            'social_facebook' => 'nullable|url',
            'social_zalo'     => 'nullable|string',
            'copyright'       => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'shop_email.required'    => 'Email cửa hàng là bắt buộc.',
            'shop_hotline.required'  => 'Hotline là bắt buộc.',
            'shop_address.required'  => 'Địa chỉ cửa hàng là bắt buộc.',
            'delivery_radius.required' => 'Bán kính giao hàng là bắt buộc.',
            'shipping_fees.required' => 'Phí ship theo khoảng cách là bắt buộc.',
            'shipping_fees.json'     => 'Phí ship phải là định dạng JSON hợp lệ.',
        ];
    }
}