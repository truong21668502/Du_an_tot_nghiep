<?php
// app/Http/Requests/Admin/ReplyReviewRequest.php

namespace App\Http\Requests\Admin;

use App\Models\ProhibitedWord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ReplyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ ADMIN mới được trả lời
    return $this->user() && in_array($this->user()->role, ['ADMIN', 'STAFF', 'BARISTA']);
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Bạn không có quyền thực hiện hành động này.'
        ], 403));
    }

    public function rules(): array
    {
        return [
            'comment' => [
                'required',
                'string',
                'min:3',
                'max:1000',
                function ($attribute, $value, $fail) {
                    if (empty($value)) return;
                    
                    $prohibitedWords = ProhibitedWord::where('is_active', true)
                        ->pluck('word')
                        ->toArray();
                    
                    foreach ($prohibitedWords as $word) {
                        if (stripos($value, $word) !== false) {
                            $fail("Nội dung phản hồi chứa từ ngữ không phù hợp. Vui lòng kiểm tra lại.");
                            return;
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Nội dung phản hồi không được để trống.',
            'comment.string'   => 'Nội dung phản hồi phải là chuỗi ký tự.',
            'comment.min'      => 'Nội dung phản hồi phải có ít nhất :min ký tự.',
            'comment.max'      => 'Nội dung phản hồi không được vượt quá :max ký tự.',
        ];
    }
}