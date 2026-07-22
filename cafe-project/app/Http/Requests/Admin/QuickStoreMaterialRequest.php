<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuickStoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_name' => 'required|string|max:255|unique:materials,material_name',
            'base_unit' => 'required|string|max:50',
            'input_unit' => 'required|string|max:50',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'quantity_in_stock' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'material_name.required' => 'Vui lòng nhập tên nguyên liệu.',
            'material_name.string' => 'Tên nguyên liệu không hợp lệ.',
            'material_name.max' => 'Tên nguyên liệu không được vượt quá :max ký tự.',
            'material_name.unique' => 'Tên nguyên liệu này đã tồn tại.',
            'base_unit.required' => 'Vui lòng nhập đơn vị tính gốc.',
            'base_unit.string' => 'Đơn vị tính gốc không hợp lệ.',
            'base_unit.max' => 'Đơn vị tính gốc không được vượt quá :max ký tự.',
            'input_unit.required' => 'Vui lòng nhập đơn vị nhập kho.',
            'input_unit.string' => 'Đơn vị nhập kho không hợp lệ.',
            'input_unit.max' => 'Đơn vị nhập kho không được vượt quá :max ký tự.',
            'exchange_rate.required' => 'Vui lòng nhập tỉ lệ quy đổi.',
            'exchange_rate.numeric' => 'Tỉ lệ quy đổi phải là số.',
            'exchange_rate.min' => 'Tỉ lệ quy đổi phải lớn hơn 0.',
            'quantity_in_stock.numeric' => 'Số lượng tồn kho phải là số.',
            'quantity_in_stock.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'material_name' => 'tên nguyên liệu',
            'base_unit' => 'đơn vị tính gốc',
            'input_unit' => 'đơn vị nhập kho',
            'exchange_rate' => 'tỉ lệ quy đổi',
            'quantity_in_stock' => 'số lượng tồn kho',
        ];
    }
}