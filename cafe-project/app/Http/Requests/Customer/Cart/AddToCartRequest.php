<?php

namespace App\Http\Requests\Customer\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'integer|min:1|max:10',
            'note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            'variant_id.required' => 'Vui lòng chọn phiên bản sản phẩm',
            'variant_id.exists' => 'Phiên bản sản phẩm không tồn tại',
            'quantity.integer' => 'Số lượng phải là số nguyên',
            'quantity.min' => 'Số lượng tối thiểu là 1',
            'quantity.max' => 'Số lượng mỗi lần thêm tối đa là 10',
            'note.string' => 'Ghi chú không hợp lệ',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $cart = $this->getCart();
            if (!$cart) return;

            $newQuantity = (int) ($this->quantity ?? 1);

            // Kiểm tra nếu sản phẩm đã có trong giỏ, số lượng sau khi cộng dồn không vượt quá 10
            $existingItem = $cart->items()
                ->where('product_id', $this->product_id)
                ->where('variant_id', $this->variant_id)
                ->first();

            if ($existingItem) {
                $totalItemQuantity = $existingItem->quantity + $newQuantity;
                
                if ($totalItemQuantity > 10) {
                    $validator->errors()->add(
                        'quantity',
                        'Số lượng sản phẩm này trong giỏ hàng không được vượt quá 10.'
                    );
                }
            } else {
                $totalItemQuantity = $newQuantity;
            }

            // Kiểm tra tổng số sản phẩm trong giỏ không vượt quá 20
            $currentTotalQuantity = $cart->items()->sum('quantity');

            if ($existingItem) {
                $totalAfterAdd = $currentTotalQuantity + $newQuantity;
            } else {
                $totalAfterAdd = $currentTotalQuantity + $newQuantity;
            }

            if ($totalAfterAdd > 20) {
                $validator->errors()->add(
                    'quantity',
                    'Tổng số sản phẩm trong giỏ hàng không được vượt quá 20'
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