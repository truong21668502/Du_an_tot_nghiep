<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_id' => 'required|exists:materials,id',
            'actual_quantity' => 'required|numeric|min:0',
            'reason' => 'required|in:kiem_ke,that_thoat,het_han,hu_hong,khac',
            'note' => 'nullable|string|max:500',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'import_receipt_detail_id' => 'nullable|integer|exists:import_receipt_details,id',

        ];
    }

    public function messages(): array
    {
        return [
            'material_id.required' => 'Vui lòng chọn nguyên liệu.',
            'material_id.exists' => 'Nguyên liệu được chọn không tồn tại.',
            'actual_quantity.required' => 'Vui lòng nhập số lượng thực tế.',
            'actual_quantity.numeric' => 'Số lượng thực tế phải là số.',
            'actual_quantity.min' => 'Số lượng thực tế không được nhỏ hơn 0.',
            'reason.required' => 'Vui lòng chọn lý do điều chỉnh.',
            'reason.in' => 'Lý do điều chỉnh không hợp lệ.',
            'note.string' => 'Ghi chú không hợp lệ.',
            'note.max' => 'Ghi chú không được vượt quá :max ký tự.',
            'min_stock.numeric' => 'Tồn kho tối thiểu phải là số.',
            'min_stock.min' => 'Tồn kho tối thiểu không được nhỏ hơn 0.',
            'max_stock.numeric' => 'Tồn kho tối đa phải là số.',
            'max_stock.min' => 'Tồn kho tối đa không được nhỏ hơn 0.',
            'expiry_date.date' => 'Ngày hết hạn không hợp lệ.',
            'import_receipt_detail_id.exists' => 'Chi tiết phiếu nhập được chọn không tồn tại.',
        ];
    }

    public function attributes(): array
    {
        return [
            'material_id' => 'nguyên liệu',
            'actual_quantity' => 'số lượng thực tế',
            'reason' => 'lý do điều chỉnh',
            'note' => 'ghi chú',
            'min_stock' => 'tồn kho tối thiểu',
            'max_stock' => 'tồn kho tối đa',
            'expiry_date' => 'ngày hết hạn',
            'import_receipt_detail_id' => 'chi tiết phiếu nhập',
        ];
    }
}