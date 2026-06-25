<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:15'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên',
            'name.min' => 'Họ tên phải có ít nhất 2 ký tự',
            'name.max' => 'Họ tên không được vượt quá 100 ký tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'email.max' => 'Email không được vượt quá 100 ký tự',
            'phone.max' => 'Số điện thoại không được vượt quá 15 ký tự',
            'subject.required' => 'Vui lòng chọn chủ đề',
            'subject.max' => 'Chủ đề không được vượt quá 200 ký tự',
            'message.required' => 'Vui lòng nhập nội dung tin nhắn',
            'message.min' => 'Tin nhắn phải có ít nhất 10 ký tự',
            'message.max' => 'Tin nhắn không được vượt quá 2000 ký tự',
        ];
    }
}