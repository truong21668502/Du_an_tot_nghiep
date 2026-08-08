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
            'order_type' => ['required', Rule::in(['DINE_IN', 'TAKE_AWAY', 'DELIVERY'])],
            'table_id' => [
                Rule::requiredIf(fn () => $this->input('order_type') === 'DINE_IN' && !session('table_id')),
                'nullable',
                'exists:tables,id',
            ],
            'address_id' => [
                Rule::requiredIf(fn () => $this->input('order_type') === 'DELIVERY'),
                'nullable',
                'exists:user_addresses,id',
            ],
            'payment_method' => ['required', Rule::in(['CASH', 'BANK_TRANSFER'])],
            'note' => ['nullable', 'string', 'max:255'],
            'distance' => ['nullable', 'numeric', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0']
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.required' => 'Vui lòng chọn bàn cho đơn tại chỗ',
        ];
    }
}