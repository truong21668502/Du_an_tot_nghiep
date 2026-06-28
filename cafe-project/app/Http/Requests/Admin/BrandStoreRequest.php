<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Brand;

class BrandStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name'  => [
                'required',
                'string',
                'max:150',
                function ($attribute, $value, $fail) {
                    // Nếu đang trong chế độ THÊM MỚI và đã có ít nhất 1 thương hiệu trong DB
                    if (Brand::exists()) {
                        $fail('Hệ thống đã cấu hình thương hiệu độc quyền, không thể thêm thương hiệu thứ hai!');
                    }
                },
            ],
            'logo_url'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'brand_name.required' => 'Vui lòng nhập tên thương hiệu sản phẩm!',
            'brand_name.max'      => 'Tên thương hiệu không được vượt quá 150 ký tự!',
            'logo_url.max'        => 'Đường dẫn logo quá dài (Tối đa 255 ký tự)!',
        ];
    }
}