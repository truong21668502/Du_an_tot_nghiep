<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VnpayController extends Controller
{
    public function return(Request $request)
    {
        $inputData = $request->query();

        if (!$this->isValidSignature($inputData)) {
            return inertia('Payment/Result', [
                'status' => 'error',
                'message' => 'Chữ ký không hợp lệ',
                'order' => null,
            ]);
        }

        $orderId = strtok($inputData['vnp_TxnRef'] ?? '', '_');
        $order = is_numeric($orderId) ? Order::with('payment', 'cart')->find((int) $orderId) : null;

        if (!$order) {
            return inertia('Payment/Result', [
                'status' => 'error',
                'message' => 'Không tìm thấy đơn hàng tương ứng',
                'order' => null,
            ]);
        }

        $expected = (int) round($order->final_amount * 100);
        $actual = (int) ($inputData['vnp_Amount'] ?? 0);

        if ($expected !== $actual) {
            Log::warning('VNPay amount mismatch', ['order_id' => $order->id]);
            return inertia('Payment/Result', [
                'status' => 'error',
                'message' => 'Số tiền thanh toán không khớp với đơn hàng',
                'order' => $order->only('id', 'final_amount'),
            ]);
        }

        $isSuccess = ($inputData['vnp_ResponseCode'] ?? null) === '00';

        if ($isSuccess) {
            return $this->handlePaymentSuccess($order, $inputData);
        }

        return $this->handlePaymentFailed($order);
    }

    private function handlePaymentSuccess(Order $order, array $inputData)
    {
        if ($order->payment->payment_status !== 'PAID') {
            DB::transaction(function () use ($order, $inputData) {
                $order->payment->update([
                    'payment_status' => 'PAID',
                    'transaction_id' => $inputData['vnp_TransactionNo'] ?? null,
                    'payment_time' => now(),
                ]);

                $order->update(['status' => 'PROCESSING']);

                if ($order->cart) {
                    $order->cart->items()->delete();
                    session()->forget('cart_voucher');
                }
            });
        }

        return inertia('Payment/Result', [
            'status' => 'success',
            'message' => 'Thanh toán thành công',
            'order' => $order->only('id', 'final_amount'),
        ]);
    }

    private function handlePaymentFailed(Order $order)
    {
        $order->payment->update(['payment_status' => 'FAILED']);

        return inertia('Payment/Result', [
            'status' => 'error',
            'message' => 'Thanh toán thất bại hoặc đã bị hủy',
            'order' => $order->only('id', 'final_amount'),
        ]);
    }

private function isValidSignature(array $inputData): bool
{
    $hashSecret = config('services.vnpay.hash_secret');
    $secureHash = $inputData['vnp_SecureHash'] ?? null;
    
    //  Loại bỏ các tham số không tham gia tạo chữ ký
    unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

    //  Sắp xếp mảng theo alphabet của key
    ksort($inputData);
    
    //  Tự xây dựng chuỗi hash data theo chuẩn VNPay
    $hashData = "";
    $i = 0;
    foreach ($inputData as $key => $value) {
        // Chỉ lấy các tham số có giá trị thực (không null và không rỗng)
        if ($value !== null && strlen($value) > 0) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
    }

    // Tạo chữ ký mong đợi
    $expectedHash = hash_hmac('sha512', $hashData, $hashSecret);

    // So sánh an toàn chuỗi
    return $secureHash && hash_equals($expectedHash, $secureHash);
}
}