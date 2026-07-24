<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProhibitedWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $wordId = $this->route('prohibited_word') ? $this->route('prohibited_word')->id : null;

        return [
            'word' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9áàảãạăắằẳẵặâấầẩẫậéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵđĐ\s]+$/u',
                Rule::unique('prohibited_words', 'word')->ignore($wordId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'word.required' => 'Vui lòng nhập từ khóa cần cấm.',
            'word.max' => 'Từ khóa không được vượt quá 100 ký tự.',
            'word.unique' => 'Từ khóa này đã tồn tại trong danh sách cấm.',
            'word.regex' => 'Từ khóa chỉ được chứa chữ cái, số và khoảng trắng.',
        ];
    }
}