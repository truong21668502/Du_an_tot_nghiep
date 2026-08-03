<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'min:2', 'max:100'],
            // Áp dụng regex mới: Bắt buộc bắt đầu bằng số 0, gồm 10 chữ số chuẩn các nhà mạng VN
            'receiver_phone' => ['required', 'string', 'regex:/^0[3|5|7|8|9][0-9]{8}$/'],
            'address_detail' => ['required', 'string', 'min:5', 'max:255'],
            'ward' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'latitude'       => ['nullable', 'numeric'],
            'longitude'      => ['nullable', 'numeric'],
            'goong_place_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_name.required' => 'Vui lòng nhập tên người nhận',
            'receiver_name.min' => 'Tên người nhận phải có ít nhất 2 ký tự',
            'receiver_name.max' => 'Tên người nhận không được vượt quá 100 ký tự',
            'receiver_phone.required' => 'Vui lòng nhập số điện thoại',
            // Thông báo lỗi chuẩn 10 số, không nhận +84 và đầu 01
            'receiver_phone.regex' => 'Số điện thoại phải bắt đầu bằng số 0, gồm 10 chữ số và đúng định dạng Việt Nam',
            'address_detail.required' => 'Vui lòng nhập địa chỉ chi tiết',
            'address_detail.min' => 'Địa chỉ chi tiết phải có ít nhất 5 ký tự',
            'address_detail.max' => 'Địa chỉ không được vượt quá 255 ký tự',
            'ward.max' => 'Phường/Xã không được vượt quá 100 ký tự',
            'city.max' => 'Tỉnh/Thành phố không được vượt quá 100 ký tự',
            'city.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'ward.required' => 'Vui lòng chọn Phường/Xã/Thị trấn.',
            'latitude.numeric' => 'Vĩ độ phải là định dạng số',
            'longitude.numeric' => 'Kinh độ phải là định dạng số',
        ];
    }
}