<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_type' => ['required', Rule::in(['DINE_IN', 'TAKE_AWAY'])],
            'table_id' => [
                Rule::requiredIf(fn () => $this->input('order_type') === 'DINE_IN'),
                'nullable',
                'exists:tables,id',
            ],
            'payment_method' => ['required', Rule::in(['CASH', 'BANK_TRANSFER'])],
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.required' => 'Vui lòng chọn bàn cho đơn tại chỗ',
        ];
    }
}