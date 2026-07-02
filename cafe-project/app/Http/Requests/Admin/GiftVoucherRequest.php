<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GiftVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_id' => 'required|exists:coupons,id',
            'user_id'   => 'nullable|required_without:send_to_all|exists:users,id',
            'send_to_all' => 'nullable|boolean', // Đánh dấu nếu gửi hàng loạt
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_id.required' => 'Vui lòng chọn loại mã ưu đãi muốn tặng!',
            'coupon_id.exists'   => 'Mã ưu đãi không tồn tại trên hệ thống!',
            'user_id.required_without' => 'Vui lòng chọn khách hàng nhận mã nếu không gửi hàng loạt!',
            'user_id.exists'     => 'Khách hàng không tồn tại!',
        ];
    }
}