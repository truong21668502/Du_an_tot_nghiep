<?php

namespace App\Http\Requests\Customer;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProhibitedWord;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating'     => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:500',
                    function ($attribute, $value, $fail) {
                    if (empty($value)) return;
                    
                    $prohibitedWords = ProhibitedWord::where('is_active', true)
                        ->pluck('word')
                        ->toArray();
                    
                    foreach ($prohibitedWords as $word) {
                        $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
                        
                        if (preg_match($pattern, $value)) {
                            $fail("Bình luận chứa từ ngữ không phù hợp. Vui lòng kiểm tra lại.");
                            return;
                        }
                    }
                },
        ],
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
            'comment.max'    => 'Nội dung không vượt quá 500 ký tự.',
            'comment.min'    => 'Nội dung phải có ít nhất 5 ký tự.',
            'comment.required' => 'Nội dung đánh giá là bắt buộc.',
        ];
    }
}