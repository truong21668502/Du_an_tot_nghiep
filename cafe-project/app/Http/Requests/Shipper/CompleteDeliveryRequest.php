<?php

namespace App\Http\Requests\Shipper;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDeliveryRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     // Shipper phải là người đang nhận đơn này
    //     return $this->user()->role === 'SHIPPER' &&
    //         $this->route('order')->shipper_id === $this->user()->id;
    // }

    public function rules(): array
    {
        return [
            'delivery_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // tối đa 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_photo.required' => 'Vui lòng cung cấp ảnh xác nhận giao hàng.',
            'delivery_photo.image'    => 'File tải lên phải là ảnh.',
            'delivery_photo.mimes'    => 'Ảnh phải có định dạng jpg, jpeg, png, gif.',
        ];
    }
}