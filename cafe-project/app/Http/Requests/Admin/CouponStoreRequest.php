<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CouponStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'                => 'required|string|max:50|unique:coupons,code',
            'discount_type'       => 'required|in:FIXED,PERCENTAGE',
            'discount_value'      => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->discount_type === 'PERCENTAGE' && $value > 50) {
                        $fail('Giá trị giảm theo phần trăm không được vượt quá 50%.');
                    }
                },
            ],
        
            'max_discount_amount' => [
                'nullable',
                'required_if:discount_type,PERCENTAGE',
                'numeric',
                'min:0',
                'max:50000', // Giới hạn tối đa 50,000
            ],

            'min_order_value' => [
                'required',
                'numeric',
                'min:50000', // Giới hạn sàn là 50.000đ như bạn muốn
                function ($attribute, $value, $fail) {
                    // Lấy giá trị giảm thực tế dựa vào loại giảm giá
                    $discountAmount = $this->discount_value;

                    // Nếu là % thì lấy số tiền giảm tối đa (max_discount_amount) để so sánh
                    if ($this->discount_type === 'PERCENTAGE') {
                        $discountAmount = $this->max_discount_amount;
                    }

                    // Kiểm tra logic: Đơn tối thiểu không được nhỏ hơn số tiền khách sẽ được giảm
                    if ($value <= $discountAmount) {
                        $fail('Đơn hàng tối thiểu (' . number_format($value) . 'đ) phải lớn hơn giá trị giảm (<= 50%) và giá trị giảm tối đa (<=' . number_format($discountAmount) . 'đ).');
                    }
                }
            ],

            'usage_limit'         => 'nullable|integer|min:1|max:1000',
            'expiration_date'     => 'required|date|after:now',
            'status'              => 'required|in:ACTIVE,INACTIVE,EXPIRED',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'                 => 'Vui lòng nhập mã giảm giá!',
            'code.unique'                   => 'Mã giảm giá này đã tồn tại trên hệ thống!',
            'discount_value.required'       => 'Vui lòng điền giá trị giảm giá!',
            'max_discount_amount.required_if' => 'Giảm theo % bắt buộc phải cấu hình số tiền giảm tối đa!',
            'max_discount_amount.max'       => 'Số tiền giảm tối đa không được vượt quá 50.000 VNĐ.',
            'expiration_date.required'      => 'Vui lòng chọn ngày hết hạn mã!',
            'expiration_date.after'         => 'Ngày hết hạn phải nằm trong tương lai!',
            'min_order_value.min'               => 'Đơn hàng tối thiểu phải nhỏ hơn hoặc bằng 50.000đ',
            'usage_limit.min'               => 'Số lượt sử dụng mã không được nhỏ hơn 1',
            'usage_limit.max'               => 'Số lượt sử dụng mã không được lớn hơn 1000, bạn có thể bỏ trống ô này nếu muốn dùng vô hạn !',
        ];
    }
}