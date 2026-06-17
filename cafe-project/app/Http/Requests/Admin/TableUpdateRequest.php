<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TableUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Lấy ID của bàn hiện tại từ URL route
        $tableId = $this->route('table');

        return [
            'table_name' => 'required|string|max:50',
            'area'       => 'nullable|string|max:50',
            'capacity'   => 'required|integer|min:1',
            'qr_code'    => 'nullable|string|max:255|unique:tables,qr_code,' . $tableId,
            'status'     => 'required|in:EMPTY,OCCUPIED,RESERVED',
        ];
    }

    public function messages(): array
    {
        return [
            'table_name.required' => 'Vui lòng nhập tên hoặc số bàn!',
            'capacity.required'   => 'Sức chứa của bàn không được để trống!',
            'capacity.min'        => 'Sức chứa tối thiểu phải từ 1 người trở lên!',
            'qr_code.unique'      => 'Đường dẫn QR Code này đã tồn tại!',
            'status.required'     => 'Trạng thái bàn không được để trống!',
        ];
    }
}