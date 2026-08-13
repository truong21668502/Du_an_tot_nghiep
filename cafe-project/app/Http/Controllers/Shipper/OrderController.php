<?php

namespace App\Http\Controllers\Shipper;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shipper\CompleteDeliveryRequest;
use App\Models\Order;
use App\Models\Setting;
use App\Events\OrderStatusUpdated;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Danh sách đơn hàng READY (chưa có shipper nhận).
     */
public function index()
    {
        $shipperId = Auth::id(); 

        $orders = Order::with('payment')
            ->where('order_type', 'DELIVERY')
            ->where(function ($query) use ($shipperId) {
                // Điều kiện A: Đơn mới chờ nhận (READY & chưa có shipper)
                $query->where(function ($q) {
                    $q->where('status', 'READY')
                    ->whereNull('shipper_id');
                })
                // Điều kiện B: Đơn của CHÍNH shipper này đang xử lý/giao
                ->orWhere(function ($q) use ($shipperId) {
                    $q->where('status', 'DELIVERING')
                    ->where('shipper_id', $shipperId);
                });
            })
            ->latest()
            ->get();

        $shopLat = Setting::getValue('shop_lat');
        $shopLng = Setting::getValue('shop_lng');

        // 2. Map dữ liệu
        $mappedOrders = $orders->map(function ($order) use ($shopLat, $shopLng, $shipperId) {
            $address = implode(', ', array_filter([
                $order->address_detail,
                $order->ward,
                $order->city,
            ]));

            return [
                'id'             => $order->id,
                'code'           => $order->code ?? 'ĐH'.$order->id,
                'status'         => $order->status,
                'customer'       => $order->receiver_name ?? 'Khách lẻ',
                'phone'          => $order->receiver_phone,
                'address'        => $address,
                'distance'       => $order->distance, 
                'shipping_fee'   => $order->shipping_fee,
                'total'          => $order->final_amount,
                'created_at'     => $order->created_at->toIso8601String(),
                'latitude'       => $order->latitude,
                'longitude'      => $order->longitude,
                'payment_status' => $order->payment ? $order->payment->payment_status : 'PENDING',
                'is_my_order'    => $order->shipper_id === $shipperId,
            ];
        });


        return Inertia::render('Shipper/Orders/Index', [
            'orders'  => $mappedOrders,
            'shopLat' => $shopLat,
            'shopLng' => $shopLng,
        ]);
    }

    /**
     * Shipper nhận đơn, chuyển trạng thái sang DELIVERING.
     */
    public function accept(Order $order)
    {
        // Kiểm tra đơn có còn khả dụng không
        if ($order->status !== 'READY' || $order->shipper_id) {
            return back()->with('error', 'Đơn hàng không khả dụng.');
        }

        $order->load('payment');
        if (!$order->payment || $order->payment->payment_status !== 'PAID') {
            return back()->with('error', 'Đơn hàng chưa được thanh toán (ứng tiền) tại quầy. Vui lòng yêu cầu nhân viên thu tiền trước khi nhận đơn.');
        }

        // Không cho shipper nhận thêm đơn nếu đang giao 1 đơn khác
        $hasDeliveringOrder = Order::where('shipper_id', auth()->id())
            ->where('status', 'DELIVERING')
            ->exists();

        if ($hasDeliveringOrder) {
            return back()->withErrors([
                'error' => 'Bạn đang có một đơn hàng đang giao.',
            ]);
        }

        $order->update([
            'status'     => 'DELIVERING',
            'shipper_id' => auth()->id(),
        ]);

        broadcast(new OrderStatusUpdated($order));

        return redirect()->route('shipper.delivery', $order->id)
            ->with('success', 'Bạn đã nhận đơn giao hàng.');
    }

    /**
     * Trang chi tiết giao hàng (sau khi đã nhận).
     */
    public function show(Order $order)
    {

        if($order->shipper_id !== auth()->id()) {
            return redirect()->route('shipper.orders.index')
                ->with('error', 'Bạn không có quyền xem đơn hàng này.');
        }

        // Chỉ load chi tiết món ăn, không cần load user
        $order->load([
            'details.product:id,product_name,image_url', 
            'details.variant', 
            'payment' => function ($query) {
                // Lấy id, order_id (bắt buộc) và cột status từ bảng payments
                $query->select('id', 'order_id', 'payment_status', 'payment_method'); 
            }
        ]);

        
        // Gán thêm chuỗi địa chỉ đầy đủ để Frontend dùng luôn cho tiện
        $order->full_address = implode(', ', array_filter([
            $order->address_detail,
            $order->ward,
            $order->city,
        ]));

        $shopLat = Setting::getValue('shop_lat');
        $shopLng = Setting::getValue('shop_lng');

        return Inertia::render('Shipper/Delivery', [
            'order'            => $order,
            'shopLat'          => $shopLat,
            'shopLng'          => $shopLng,
        ]);
    }

    /**
     * Hoàn thành giao hàng, upload ảnh và chuyển COMPLETED.
     */

    public function complete(CompleteDeliveryRequest $request, Order $order)
    {

        $cloudinary = new \Cloudinary\Cloudinary();

        $result = $cloudinary->uploadApi()->upload($request->file('delivery_photo')->getRealPath(), [
            'folder'         => 'uploads_du_an/delivery_photos', // Đổi tên folder cho phù hợp với logic giao hàng
            'transformation' => ['quality' => 'auto', 'fetch_format' => 'auto'],
        ]);

        $order->update([
            'delivery_photo'           => $result['secure_url'],
            'delivery_photo_public_id' => $result['public_id'],
            'status'                   => 'COMPLETED',
        ]);

        // Broadcast cho customer biết đơn đã hoàn thành
        event(new OrderStatusUpdated($order));

        return redirect()->route('shipper.orders.index')
            ->with('success', 'Giao hàng thành công!');
    }

}