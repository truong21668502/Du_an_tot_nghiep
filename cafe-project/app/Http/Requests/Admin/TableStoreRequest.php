<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TableStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_name' => 'required|string|min:2|max:50',
            'area'       => 'nullable|string|min:2|max:50',
            'capacity'   => 'required|integer|min:1',
            'qr_code'    => 'nullable|string|max:255|unique:tables,qr_code',
            'status'     => 'required|in:EMPTY,OCCUPIED',
        ];
    }

    public function messages(): array
    {
        return [
            'table_name.required' => 'Vui lòng nhập tên hoặc số bàn!',
            'capacity.required'   => 'Sức chứa của bàn không được để trống!',
            'capacity.integer'    => 'Sức chứa phải là số nguyên!',
            'capacity.min'        => 'Sức chứa tối thiểu phải từ 1 người trở lên!',
            'qr_code.unique'      => 'Đường dẫn QR Code này đã tồn tại trong hệ thống!',
            'status.required'     => 'Trạng thái bàn không được để trống!',
            'status.in'           => 'Trạng thái bàn không hợp lệ!',
        ];
    }
}