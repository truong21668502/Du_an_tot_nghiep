<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image'       => ['required', 'image', 'max:5120'],

            'button_text' => ['nullable', 'string', 'max:100', 'required_with:button_url'],
            'button_url'  => ['nullable', 'url', 'max:255', 'required_with:button_text'],

            'theme'       => ['required', Rule::in(Banner::THEMES)],
            'text_align'  => ['required', Rule::in(Banner::TEXT_ALIGNS)],
            'position'    => ['required', Rule::in(Banner::POSITIONS)],

            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required'             => 'Vui lòng chọn ảnh banner.',
            'button_text.required_with'  => 'Vui lòng nhập text nút khi đã nhập link.',
            'button_url.required_with'   => 'Vui lòng nhập link khi đã nhập text nút.',
            'button_url.url'             => 'Link nút bấm không đúng định dạng URL.',
        ];
    }
}