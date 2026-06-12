<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreAddressRequest;
use App\Http\Requests\Customer\UpdateAddressRequest;
use App\Http\Requests\Customer\UpdateAvatarRequest;
use App\Http\Requests\Customer\UpdatePasswordRequest;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function info()
    {
        return inertia('Profile/Partials/Info', [
            'user' => $this->getUserData(),
        ]);
    }

    public function password()
    {
        return inertia('Profile/Partials/Password', [
            'user' => $this->getUserData(),
        ]);
    }

    public function orders()
    {
        $orders = Order::with(['orderDetails.product', 'orderDetails.variant', 'payment'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(fn($order) => $this->formatOrder($order));

        return inertia('Profile/Partials/Orders', [
            'user' => $this->getUserData(),
            'orders' => $orders,
        ]);
    }

    public function orderDetail(Order $order)
    {
        if ($order->user_id !== Auth::id()) abort(403);

        $order->load(['orderDetails.product', 'orderDetails.variant', 'payment', 'coupon']);

        return inertia('Profile/Partials/OrderDetail', [
            'user' => $this->getUserData(),
            'order' => $this->formatOrderDetail($order),
        ]);
    }

    public function addresses()
    {
        $addresses = UserAddress::where('user_id', Auth::id())->get();

        return inertia('Profile/Partials/Addresses', [
            'user' => $this->getUserData(),
            'addresses' => $this->formatAddresses($addresses),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        Auth::user()->update($request->validated());
        return redirect()->route('profile.info')->with('toast-success', 'Cập nhật thông tin thành công');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác']);
        }

        $user->update(['password' => $request->password]);
        return redirect()->route('profile.password')->with('toast-success', 'Đổi mật khẩu thành công');
    }

    public function updateAvatar(UpdateAvatarRequest $request)
    {
        $user = Auth::user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $path]);
        }

        return back()->with('toast-success', 'Cập nhật ảnh đại diện thành công');
    }

    public function cancelOrder(Order $order)
    {
        if ($order->user_id !== Auth::id()) abort(403);

        if (in_array($order->status, ['PROCESSING', 'COMPLETED', 'CANCELLED'])) {
            return back()->with('toast-error', 'Không thể hủy đơn hàng ở trạng thái này');
        }

        $order->update(['status' => 'CANCELLED']);
        return redirect()->route('profile.orders')->with('toast-success', 'Hủy đơn hàng thành công');
    }

    public function storeAddress(StoreAddressRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['user_id'] = $user->id;

        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        UserAddress::create($data);
        return redirect()->route('profile.addresses')->with('toast-success', 'Thêm địa chỉ thành công');
    }

    public function updateAddress(UpdateAddressRequest $request, UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) abort(403);

        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $address->update($request->validated());
        return redirect()->route('profile.addresses')->with('toast-success', 'Cập nhật địa chỉ thành công');
    }

    public function deleteAddress(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) abort(403);

        $address->delete();
        return redirect()->route('profile.addresses')->with('toast-success', 'Xóa địa chỉ thành công');
    }

    public function setDefaultAddress(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) abort(403);

        UserAddress::where('user_id', Auth::id())->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return redirect()->route('profile.addresses')->with('toast-success', 'Đã đặt làm địa chỉ mặc định');
    }

    private function getUserData(): array
    {
        $user = Auth::user();
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'gender' => $user->gender,
            'date_of_birth' => $user->date_of_birth,
            'avatar' => $user->avatar ?? null,
        ];
    }

    private function formatAddresses($addresses): array
    {
        return $addresses->map(fn($a) => [
            'id' => $a->id,
            'receiver_name' => $a->receiver_name,
            'receiver_phone' => $a->receiver_phone,
            'address_detail' => $a->address_detail,
            'ward' => $a->ward,
            'city' => $a->city,
            'is_default' => $a->is_default,
        ])->values()->toArray();
    }

    private function formatOrder($order): array
    {
        return [
            'id' => $order->id,
            'total_amount' => $order->total_amount,
            'discount_amount' => $order->discount_amount,
            'final_amount' => $order->final_amount,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'order_type' => $order->order_type,
            'created_at' => $order->created_at,
            'items' => $order->orderDetails->map(fn($d) => [
                'id' => $d->id,
                'product_name' => $d->product->product_name ?? 'Sản phẩm',
                'product_image' => $d->product->image_url ?? null,
                'size' => $d->variant->size ?? null,
                'quantity' => $d->quantity,
                'unit_price' => $d->unit_price,
                'subtotal' => $d->unit_price * $d->quantity,
                'note' => $d->note,
            ])->toArray(),
            'payment' => $order->payment ? [
                'method' => $order->payment->payment_method,
                'status' => $order->payment->payment_status,
                'transaction_id' => $order->payment->transaction_id,
            ] : null,
        ];
    }

    private function formatOrderDetail($order): array
    {
        return $this->formatOrder($order) + [
            'delivery_address' => $order->delivery_address,
            'coupon' => $order->coupon ? [
                'code' => $order->coupon->code,
                'discount_type' => $order->coupon->discount_type,
                'discount_value' => $order->coupon->discount_value,
            ] : null,
        ];
    }
}