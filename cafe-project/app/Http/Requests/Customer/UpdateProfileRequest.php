<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:15', 'unique:users,phone_number,' . auth()->id()],
            'gender' => ['nullable', 'in:Nam,Nữ,Khác'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên',
            'full_name.max' => 'Họ tên không được vượt quá 100 ký tự',
            'phone_number.unique' => 'Số điện thoại đã được sử dụng',
            'gender.in' => 'Giới tính không hợp lệ',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại',
        ];
    }
}