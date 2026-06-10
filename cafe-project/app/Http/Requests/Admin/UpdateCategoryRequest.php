<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Lấy ra id của category hiện tại đang được chỉnh sửa từ URL route
        $categoryId = $this->route('category')->id;

        return [
            'category_name' => 'required|string|max:100',
            'description'   => 'nullable|string',
            'slug'          => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Tên danh mục không được để trống.',
            'category_name.max'      => 'Tên danh mục không được vượt quá 100 ký tự.',
            'slug.unique'            => 'Đường dẫn (Slug) này đã tồn tại.',
        ];
    }
}