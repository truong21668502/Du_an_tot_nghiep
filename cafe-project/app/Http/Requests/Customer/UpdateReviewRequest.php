<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Kiểm tra ownership ngay tại Request, không cần Policy
        return $this->route('review')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'rating'  => ['sometimes', 'required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.between' => 'Điểm đánh giá phải từ 1 đến 5.',
            'comment.max'    => 'Nội dung không vượt quá 1000 ký tự.',
        ];
    }
}