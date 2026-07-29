<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $materialId = $this->route('material')->id;

        return [
            'material_name' => ['required', 'string', 'max:255', Rule::unique('materials', 'material_name')->ignore($materialId)],
            'input_unit' => ['required', 'string', 'max:50'],
            'base_unit' => ['required', 'string', 'max:50'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'shelf_life_after_opening_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }


}