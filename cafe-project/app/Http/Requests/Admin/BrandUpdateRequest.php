<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BrandUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name'  => 'required|string|max:150',
            'logo_url'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'brand_name.required' => 'Vui lòng không để trống tên thương hiệu!',
            'brand_name.max'      => 'Tên thương hiệu không được vượt quá 150 ký tự!',
        ];
    }
}