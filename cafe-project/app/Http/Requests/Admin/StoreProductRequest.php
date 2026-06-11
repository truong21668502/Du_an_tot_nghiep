<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'       => 'required|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'product_name'      => 'required|string|max:150',
            'slug'              => 'nullable|string|max:150|unique:products,slug',
            'short_description' => 'nullable|string',
            'description'       => 'nullable|string',
            'image_url'         => 'nullable|string|max:255',
            'is_active'         => 'required|in:Đang bán,Ngừng kinh doanh',
            
            // Validate mảng biến thể đi kèm
            'variants'                  => 'required|array|min:1',
            'variants.*.size'           => 'required|string|max:50',
            'variants.*.price'          => 'required|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0|lt:variants.*.price',
            'variants.*.stock_quantity' => 'required|integer|min:0',
            'variants.*.status'         => 'required|in:AVAILABLE,OUT_OF_STOCK',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'     => 'Vui lòng chọn danh mục sản phẩm.',
            'category_id.exists'       => 'Danh mục không hợp lệ.',
            'product_name.required'    => 'Tên sản phẩm không được để trống.',
            'product_name.max'         => 'Tên sản phẩm tối đa 150 ký tự.',
            'slug.unique'              => 'Đường dẫn (Slug) này đã tồn tại.',
            'variants.required'        => 'Sản phẩm phải có ít nhất một biến thể (Size/Giá).',
            'variants.*.size.required' => 'Kích cỡ không được để trống.',
            'variants.*.price.required'=> 'Giá bán không được để trống.',
            'variants.*.discount_price.lt' => 'Giá giảm phải nhỏ hơn giá gốc.',
        ];
    }
}