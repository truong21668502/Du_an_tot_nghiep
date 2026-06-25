<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CouponUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon');

        return [
            'code'                => 'required|string|max:50|unique:coupons,code,' . $couponId,
            'discount_type'       => 'required|in:FIXED,PERCENTAGE',
            'discount_value'      => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|required_if:discount_type,PERCENTAGE|numeric|min:0',
            'min_order_value'     => 'required|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:1',
            'expiration_date'     => 'required|date',
            'status'              => 'required|in:ACTIVE,INACTIVE,EXPIRED',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'           => 'Vui lòng nhập mã giảm giá!',
            'code.unique'             => 'Mã giảm giá này đã trùng với một mã khác!',
            'discount_value.required' => 'Vui lòng điền giá trị giảm giá!',
        ];
    }
}