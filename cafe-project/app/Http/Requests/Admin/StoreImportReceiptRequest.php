<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id|distinct',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_name.string' => 'Tên nhà cung cấp không hợp lệ.',
            'supplier_name.max' => 'Tên nhà cung cấp không được vượt quá :max ký tự.',
            'note.string' => 'Ghi chú không hợp lệ.',
            'note.max' => 'Ghi chú không được vượt quá :max ký tự.',
            'items.required' => 'Phiếu nhập phải có ít nhất 1 nguyên liệu.',
            'items.array' => 'Danh sách nguyên liệu không hợp lệ.',
            'items.min' => 'Phiếu nhập phải có ít nhất 1 nguyên liệu.',
            'items.*.material_id.required' => 'Vui lòng chọn nguyên liệu.',
            'items.*.material_id.exists' => 'Nguyên liệu được chọn không tồn tại.',
            'items.*.material_id.distinct' => 'Một nguyên liệu không được nhập trùng nhiều dòng.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.numeric' => 'Số lượng phải là số.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'items.*.unit_price.required' => 'Vui lòng nhập đơn giá.',
            'items.*.unit_price.numeric' => 'Đơn giá phải là số.',
            'items.*.unit_price.min' => 'Đơn giá không được nhỏ hơn 0.',
            'items.*.expiry_date.date' => 'Hạn sử dụng không đúng định dạng ngày.',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_name' => 'tên nhà cung cấp',
            'note' => 'ghi chú',
            'items' => 'danh sách nguyên liệu',
            'items.*.material_id' => 'nguyên liệu',
            'items.*.quantity' => 'số lượng',
            'items.*.unit_price' => 'đơn giá',
            'items.*.expiry_date' => 'hạn sử dụng',
        ];
    }
}