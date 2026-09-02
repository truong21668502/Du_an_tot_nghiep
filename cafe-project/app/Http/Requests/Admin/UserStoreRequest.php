<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'    => 'required|string|max:100',
            // Đồng bộ max:15 với migration và kiểm tra unique
            'phone_number' => 'nullable|string|max:15|unique:users,phone_number',
            // Email có thể bắt buộc với nhân sự, kiểm tra tối đa 100 ký tự theo Migration
            'email'        => 'required|email|max:100|unique:users,email',
            'password'     => 'required|string|min:6|max:255',
            'role'         => 'required|in:ADMIN,STAFF,BARISTA,SHIPPER', 
            'gender'       => 'required|in:Nam,Nữ,Khác',
            'date_of_birth'=> 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'  => 'Vui lòng nhập họ tên nhân viên!',
            'email.required'      => 'Vui lòng nhập địa chỉ Email làm việc!',
            'email.unique'        => 'Email này đã được sử dụng trên hệ thống!',
            'password.required'   => 'Vui lòng nhập mật khẩu khởi tạo!',
            'password.min'        => 'Mật khẩu phải từ 6 ký tự trở lên!',
            'phone_number.unique' => 'Số điện thoại này đã tồn tại!',
        ];
    }
}