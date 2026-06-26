<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : $user;

        return [
            'full_name'     => 'required|string|max:100',
            'phone_number'  => 'nullable|string|max:15|unique:users,phone_number,' . $userId,
            'email'         => 'required|email|max:100|unique:users,email,' . $userId,
            'role'          => 'required|in:ADMIN,STAFF,BARISTA,CUSTOMER',
            'status'        => 'required|in:active,inactive,banned',
            'status_note'   => 'nullable|string|max:255',
            'gender'        => 'required|in:Nam,Nữ,Khác',
            'date_of_birth' => 'nullable|date',
            'reward_points' => 'required|integer|min:0',
            'password'      => 'nullable|string|min:6|max:255', // Cho phép cập nhật mật khẩu nếu điền
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'  => 'Họ và tên không được để trống!',
            'email.required'      => 'Email không được để trống!',
            'email.unique'        => 'Địa chỉ email này đã tồn tại!',
            'phone_number.unique' => 'Số điện thoại này đã được sử dụng!',
            'password.min'        => 'Mật khẩu mới phải từ 6 ký tự trở lên!',
        ];
    }
}