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
use Illuminate\Support\Facades\Http;

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
        $orders = Order::with(['details.product', 'details.variant', 'payment', 'coupon'])
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

        $order->load(['details.product', 'details.variant', 'payment', 'coupon', 'table']);

        // Trả về JSON cho API call
        return response()->json([
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

        // 1. Kiểm tra mật khẩu cũ có đúng hay không
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác']);
        }

        // 2. Kiểm tra mật khẩu mới có TRÙNG với mật khẩu cũ hay không
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại']);
        }

        // 3. Mã hóa mật khẩu mới trước khi lưu
        $user->update([
            'password' => Hash::make($request->password)
        ]);

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

        if (in_array($order->status, ['PROCESSING', 'READY', 'DELIVERING', 'COMPLETED', 'CANCELLED'])) {
            return back()->with('toast-error', 'Không thể hủy đơn hàng ở trạng thái này');
        }

        // Lấy thông tin thanh toán (Yêu cầu Model Order có relationship: public function payment())
        $payment = $order->payment;

        // Kiểm tra xem đơn hàng đã được thanh toán thành công chưa
        if ($payment && $payment->payment_status === 'PAID') {
            
            // Nếu phương thức là BANK_TRANSFER (VNPAY), tiến hành hoàn tiền qua API
            if ($payment->payment_method === 'BANK_TRANSFER') {
                $refundResult = $this->processVnPayRefund($payment, $order);

                // Nếu hoàn tiền VNPAY thất bại, dừng việc hủy đơn và báo lỗi
                if (!$refundResult['success']) {
                    return back()->with('toast-error', 'Hoàn tiền thất bại: ' . $refundResult['message']);
                }
            }

            // Cập nhật trạng thái thanh toán thành Đã hoàn tiền
            $payment->update(['payment_status' => 'REFUNDED']);
        }

        // Cập nhật trạng thái đơn hàng
        $order->update(['status' => 'CANCELLED', 'cancel_reason' => 'Khách hàng hủy đơn']);
        
        return redirect()->route('profile.orders')->with('toast-success', 'Hủy đơn hàng và hoàn tiền thành công');
    }

    /**
     * Xử lý gọi API Hoàn tiền của VNPAY Sandbox
     */
    private function processVnPayRefund($payment, $order)
    {
        $vnp_TmnCode = env('VNP_TMN_CODE');
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_Url = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction"; // Endpoint Refund của Sandbox

        // Các tham số bắt buộc theo tài liệu VNPAY
        $vnp_RequestId = uniqid(); // Mã request id duy nhất cho mỗi lần gọi API
        $vnp_Command = "refund";
        $vnp_TransactionType = "02"; // 02: Hoàn tiền toàn phần
        $vnp_TxnRef = $payment->vnp_txn_ref;
        $vnp_Amount = $payment->amount * 100; // VNPAY yêu cầu nhân 100
        $vnp_TransactionNo = $payment->transaction_id; // Mã giao dịch do VNPAY sinh ra lúc thanh toán
        
        // Thời gian tạo giao dịch gốc (VNPAY yêu cầu định dạng yyyyMMddHHmmss)
        $vnp_TransactionDate = date('YmdHis', strtotime($payment->payment_time)); 
        
        $vnp_CreateBy = Auth::user()->name ?? 'System'; // Người thực hiện hoàn tiền
        $vnp_CreateDate = date('YmdHis');
        $vnp_IpAddr = request()->ip();
        $vnp_OrderInfo = "Hoan tien don hang " . $order->id;

        $data = [
            "vnp_RequestId" => $vnp_RequestId,
            "vnp_Version" => "2.1.0",
            "vnp_Command" => $vnp_Command,
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_TransactionType" => $vnp_TransactionType,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_Amount" => $vnp_Amount,
            "vnp_TransactionNo" => $vnp_TransactionNo,
            "vnp_TransactionDate" => $vnp_TransactionDate,
            "vnp_CreateBy" => $vnp_CreateBy,
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_OrderInfo" => $vnp_OrderInfo
        ];

        // Tạo chuỗi Hash Checksum (Phải đúng thứ tự theo tài liệu VNPAY)
        $format = '%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s';
        $dataHash = sprintf(
            $format,
            $data['vnp_RequestId'],
            $data['vnp_Version'],
            $data['vnp_Command'],
            $data['vnp_TmnCode'],
            $data['vnp_TransactionType'],
            $data['vnp_TxnRef'],
            $data['vnp_Amount'],
            $data['vnp_TransactionNo'],
            $data['vnp_TransactionDate'],
            $data['vnp_CreateBy'],
            $data['vnp_CreateDate'],
            $data['vnp_IpAddr'],
            $data['vnp_OrderInfo']
        );

        // Tạo mã bảo mật
        $vnp_SecureHash = hash_hmac('sha512', $dataHash, $vnp_HashSecret);
        $data['vnp_SecureHash'] = $vnp_SecureHash;

        // Gọi API bằng Laravel Http Client
        $response = Http::withoutVerifying()->post($vnp_Url, $data);

        if ($response->successful()) {
            $responseData = $response->json();
            
            // Mã '00' biểu thị VNPAY đã chấp nhận yêu cầu hoàn tiền thành công
            if (isset($responseData['vnp_ResponseCode']) && $responseData['vnp_ResponseCode'] == '00') {
                return ['success' => true];
            }
            return ['success' => false, 'message' => $responseData['vnp_Message'] ?? 'Từ chối từ VNPAY'];
        }

        return ['success' => false, 'message' => 'Lỗi kết nối tới hệ thống VNPAY'];
    }

public function storeAddress(StoreAddressRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['user_id'] = $user->id;

        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = UserAddress::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm địa chỉ thành công',
            'data' => $address
        ]);
    }

    public function updateAddress(UpdateAddressRequest $request, UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công',
            'data' => $address
        ]);
    }

public function deleteAddress(UserAddress $address)
{
    if ($address->user_id !== Auth::id()) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $userId = Auth::id();
    $wasDefault = $address->is_default;

    // Xóa địa chỉ
    $address->delete();

    if ($wasDefault) {
        $remainingAddress = UserAddress::where('user_id', $userId)->latest()->first();
        if ($remainingAddress) {
            $remainingAddress->update(['is_default' => true]);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Xóa địa chỉ thành công'
    ]);
}

    public function setDefaultAddress(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        UserAddress::where('user_id', Auth::id())->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt làm địa chỉ mặc định'
        ]);
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
            'id'             => $a->id,
            'receiver_name'  => $a->receiver_name,
            'receiver_phone' => $a->receiver_phone,
            'address_detail' => $a->address_detail,
            'ward'           => $a->ward,
            'city'           => $a->city,
            'latitude'       => $a->latitude ? (float) $a->latitude : null,
            'longitude'      => $a->longitude ? (float) $a->longitude : null,
            'goong_place_id' => $a->goong_place_id,
            'is_default'     => (bool) $a->is_default,
        ])->values()->toArray();
    }

    private function formatOrder($order): array
    {
        return [
            'id' => $order->id,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_fee' => (float) $order->shipping_fee,
            'final_amount' => (float) $order->final_amount,
            'note' => $order->note,
            'cancel_reason' => $order->cancel_reason,
            'table' => $order->table ? [
                'id' => $order->table->id,
                'table_name' => $order->table->table_name,
                'area' => $order->table->area,
            ] : null,
            'receiver_name' => $order->receiver_name,
            'receiver_phone' => $order->receiver_phone,
            'address_detail' => $order->address_detail,
            'ward' => $order->ward,
            'city' => $order->city,
            'created_at' => $order->created_at,
            'items' => $order->details->map(fn($d) => [
                'id' => $d->id,
                'product_name' => $d->product->product_name ?? 'Sản phẩm',
                'product_image' => $d->product->image_url ?? null,
                'size' => $d->variant->size ?? null,
                'quantity' => $d->quantity,
                'unit_price' => (float) $d->unit_price,
                'subtotal' => (float) ($d->unit_price * $d->quantity),
                'note' => $d->note,
            ])->toArray(),
            'payment' => $order->payment ? [
                'method' => $order->payment->payment_method,
                'status' => $order->payment->payment_status,
                'transaction_id' => $order->payment->transaction_id,
            ] : null,
            'coupon' => $order->coupon ? [
                'code' => $order->coupon->code,
                'discount_type' => $order->coupon->discount_type,
                'discount_value' => (float) $order->coupon->discount_value,
            ] : null,
        ];
    }

    private function formatOrderDetail($order): array
    {
        return $this->formatOrder($order);
    }

    
}