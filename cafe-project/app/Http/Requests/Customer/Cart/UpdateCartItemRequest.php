<?php

namespace App\Http\Requests\Customer\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1|max:10',
            'note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.integer' => 'Số lượng phải là số nguyên',
            'quantity.min' => 'Số lượng tối thiểu là 1',
            'quantity.max' => 'Số lượng mỗi sản phẩm tối đa là 10',
            'note.string' => 'Ghi chú không hợp lệ',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $cart = $this->getCart();
            $cartItem = $this->route('cartItem');
            
            if (!$cart || !$cartItem) return;

            $currentTotalQuantity = $cart->items()->sum('quantity');
            $currentItemQuantity = $cartItem->quantity;
            $newQuantity = (int) $this->quantity;

            // Tổng mới sau khi cập nhật
            $totalAfterUpdate = $currentTotalQuantity - $currentItemQuantity + $newQuantity;

            if ($totalAfterUpdate > 40) {
                $maxCanAdd = 40 - ($currentTotalQuantity - $currentItemQuantity);
                $validator->errors()->add(
                    'quantity',
                    'Tổng số sản phẩm trong giỏ hàng không được vượt quá 40. Bạn có thể cập nhật tối đa ' . $maxCanAdd . ' sản phẩm cho mặt hàng này.'
                );
            }
        });
    }

    private function getCart()
    {
        if (Auth::check()) {
            return \App\Models\Cart::where('user_id', Auth::id())->first();
        }

        $token = request()->cookie('cart_token');
        if ($token) {
            return \App\Models\Cart::whereNull('user_id')->where('token', $token)->first();
        }

        return null;
    }
}