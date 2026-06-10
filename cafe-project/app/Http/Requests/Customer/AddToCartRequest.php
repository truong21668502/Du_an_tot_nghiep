<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            'variant_id.exists' => 'Biến thể sản phẩm không tồn tại',
            'quantity.min' => 'Số lượng tối thiểu là 1',
            'quantity.max' => 'Số lượng tối đa là 99',
        ];
    }
}