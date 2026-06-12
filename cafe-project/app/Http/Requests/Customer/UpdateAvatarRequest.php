<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Vui lòng chọn ảnh đại diện',
            'avatar.image' => 'File phải là hình ảnh',
            'avatar.mimes' => 'Ảnh phải có định dạng jpeg, png, jpg hoặc webp',
            'avatar.max' => 'Kích thước ảnh tối đa 2MB',
        ];
    }
}