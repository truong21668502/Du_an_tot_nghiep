<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelImportReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.string' => 'Lý do huỷ không hợp lệ.',
            'cancel_reason.max' => 'Lý do huỷ không được vượt quá :max ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cancel_reason' => 'lý do huỷ',
        ];
    }
}