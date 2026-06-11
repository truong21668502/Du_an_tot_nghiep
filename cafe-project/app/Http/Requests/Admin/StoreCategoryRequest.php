<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Xác định xem người dùng có quyền thực hiện hành động này không.
     * Đổi thành `true` nếu bạn không dùng phân quyền ở đây.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Quy định các luật validate (Validation Rules)
     */
    public function rules(): array
    {
        return [
            'category_name' => 'required|string|max:100',
            'description'   => 'nullable|string',
            'slug'          => 'nullable|string|max:150|unique:categories,slug',
        ];
    }

    /**
     * Tùy chỉnh thông báo lỗi bằng tiếng Việt (Messages)
     */
    public function messages(): array
    {
        return [
            'category_name.required' => 'Tên danh mục không được để trống.',
            'category_name.max'      => 'Tên danh mục không được vượt quá 100 ký tự.',
            'slug.unique'            => 'Đường dẫn (Slug) này đã tồn tại, vui lòng chọn tên khác.',
            'slug.max'               => 'Đường dẫn (Slug) không được vượt quá 150 ký tự.',
        ];
    }
}