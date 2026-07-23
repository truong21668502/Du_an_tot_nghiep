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
            'comment' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.between' => 'Điểm đánh giá phải từ 1 đến 5.',
            'comment.max'    => 'Nội dung không vượt quá 500 ký tự.',
            'comment.min'    => 'Nội dung phải có ít nhất 10 ký tự.',
            'comment.required' => 'Nội dung đánh giá là bắt buộc.',
        ];
    }
}