<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'max:15'],
            'address_detail' => ['required', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_name.required' => 'Vui lòng nhập tên người nhận',
            'receiver_phone.required' => 'Vui lòng nhập số điện thoại',
            'address_detail.required' => 'Vui lòng nhập địa chỉ chi tiết',
        ];
    }
}