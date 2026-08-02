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
            'shelf_life_after_opening_days' => 'nullable|integer|min:1|max:365',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0|gte:min_stock',
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
            'shelf_life_after_opening_days.integer' => 'Hạn dùng sau khi mở phải là số nguyên.',
            'shelf_life_after_opening_days.min' => 'Hạn dùng sau khi mở phải lớn hơn hoặc bằng 1.',
            'shelf_life_after_opening_days.max' => 'Hạn dùng sau khi mở không được vượt quá 365 ngày.',
            'min_stock.numeric' => 'Số lượng tồn kho tối thiểu phải là số.',
            'min_stock.min' => 'Số lượng tồn kho tối thiểu không được nhỏ hơn 0.',
            'max_stock.numeric' => 'Số lượng tồn kho tối đa phải là số.',
            'max_stock.min' => 'Số lượng tồn kho tối đa không được nhỏ hơn 0.',
            'max_stock.gte' => 'Số lượng tồn kho tối đa phải lớn hơn
    hoặc bằng số lượng tồn kho tối thiểu.',
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
            'shelf_life_after_opening_days' => 'hạn dùng sau khi mở',
            'min_stock' => 'số lượng tồn kho tối thiểu',
            'max_stock' => 'số lượng tồn kho tối đa',
        ];
    }
}