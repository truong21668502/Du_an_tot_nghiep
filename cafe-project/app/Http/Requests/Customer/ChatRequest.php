<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'  => ['required', 'string', 'min:1', 'max:500'],
            'context'  => ['sometimes', 'nullable', 'array'],
            'context.*' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Tin nhắn không được để trống.',
            'message.max'      => 'Tin nhắn không vượt quá 500 ký tự.',
        ];
    }
}