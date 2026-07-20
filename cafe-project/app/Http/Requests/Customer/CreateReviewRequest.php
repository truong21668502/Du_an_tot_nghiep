<?php

namespace App\Http\Requests\Customer;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating'     => ['required', 'integer', 'between:1,5'],
            'comment'    => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            // Tìm đơn COMPLETED có sản phẩm này và chưa được review
            $hasEligible = Order::where('user_id', $this->user()->id)
                ->where('status', 'COMPLETED')
                ->whereHas('details', fn($q) => $q->where('product_id', $this->product_id))
                ->whereDoesntHave('reviews', fn($q) => $q->where('product_id', $this->product_id))
                ->exists();

            if (!$hasEligible) {
                $validator->errors()->add(
                    'product_id',
                    'Bạn chưa mua sản phẩm này hoặc đã đánh giá tất cả đơn hàng rồi.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'rating.between' => 'Điểm đánh giá phải từ 1 đến 5.',
            'comment.max'    => 'Nội dung không vượt quá 1000 ký tự.',
        ];
    }
}