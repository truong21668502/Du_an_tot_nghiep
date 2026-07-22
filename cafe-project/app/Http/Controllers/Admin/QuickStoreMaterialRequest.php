<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuickStoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_name' => 'required|string|max:255|unique:materials,material_name',
            'base_unit' => 'required|string|max:50',
            'input_unit' => 'required|string|max:50',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'quantity_in_stock' => 'nullable|numeric|min:0',
        ];
    }
}