<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CopyRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // hệ số nhân, ví dụ 1.2 cho size L
            'scale' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'scale.numeric' => 'Hệ số nhân phải là số.',
            'scale.min' => 'Hệ số nhân phải từ :min trở lên.',
            'scale.max' => 'Hệ số nhân không được vượt quá :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            'scale' => 'hệ số nhân',
        ];
    }
}