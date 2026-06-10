<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class MenuFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort_by' => ['nullable', 'string', 'in:newest,oldest,price_asc,price_desc,name_asc,name_desc,rating'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_price.gte' => 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.',
            'rating.min' => 'Đánh giá tối thiểu là 0 sao.',
            'rating.max' => 'Đánh giá tối đa là 5 sao.',
        ];
    }
}