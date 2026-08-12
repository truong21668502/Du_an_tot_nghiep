<?php
// app/Http/Requests/Admin/ReplyReviewRequest.php

namespace App\Http\Requests\Admin;

use App\Models\ProhibitedWord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Services\ProfanityFilterService;

class ReplyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ ADMIN mới được trả lời
    return $this->user() && in_array($this->user()->role, ['ADMIN', 'STAFF', 'BARISTA']);
    }
        protected function prepareForValidation(): void
    {
        $this->merge([
            'comment' => app(ProfanityFilterService::class)
                ->filter($this->input('comment')),
        ]);
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
                'max:1000'],
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