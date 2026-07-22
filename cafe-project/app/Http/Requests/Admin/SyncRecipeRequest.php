<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required', 'integer', 'exists:materials,id'],
            'items.*.quantity_needed' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Công thức phải có ít nhất 1 nguyên liệu.',
            'items.array' => 'Danh sách nguyên liệu không hợp lệ.',
            'items.min' => 'Công thức phải có ít nhất 1 nguyên liệu.',
            'items.*.material_id.required' => 'Vui lòng chọn nguyên liệu.',
            'items.*.material_id.integer' => 'Nguyên liệu không hợp lệ.',
            'items.*.material_id.exists' => 'Nguyên liệu được chọn không tồn tại.',
            'items.*.quantity_needed.required' => 'Vui lòng nhập định lượng.',
            'items.*.quantity_needed.numeric' => 'Định lượng phải là số.',
            'items.*.quantity_needed.min' => 'Định lượng phải lớn hơn 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'danh sách nguyên liệu',
            'items.*.material_id' => 'nguyên liệu',
            'items.*.quantity_needed' => 'định lượng',
        ];
    }
}