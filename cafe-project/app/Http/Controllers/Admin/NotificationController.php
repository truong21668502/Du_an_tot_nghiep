<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;
use App\Models\Material;

class NotificationController extends Controller
{
    public function getNotifications()
    {
        try {
            $notifications = [];

            // ----------------------------------------------------------------
            // 1. BÁO ĐỘNG KHO (Nguyên liệu)
            // ----------------------------------------------------------------
            $lowStockItems = Material::query()
                ->where(function ($query) {
                    $query->where('quantity_in_stock', '<=', 0)
                        ->orWhere(function ($q) {
                        $q->where('min_stock', '>', 0)
                        ->whereColumn('quantity_in_stock', '<=', 'min_stock');
                    });
                })
                ->select('id', 'material_name', 'quantity_in_stock', 'base_unit', 'min_stock')
                ->orderBy('quantity_in_stock', 'asc')
                ->limit(5)
                ->get();

            // Tạo danh sách thông báo
            foreach ($lowStockItems as $item) {
                $notifications[] = [
                    'id'      => 'stock_' . $item->id,
                    'type'    => 'danger',
                    'title'   => 'Cảnh báo tồn kho',
                    'message' => "Nguyên liệu '{$item->material_name}' chỉ còn {$item->quantity_in_stock} {$item->base_unit} trong kho.",
                    'link'    => '/quan-tri/kho',
                    'time'    => 'Mới nhất',
                ];
            }

            // ----------------------------------------------------------------
            // 2. CẢNH BÁO MÃ GIẢM GIÁ (Hết hạn hoặc hết lượt)
            // ----------------------------------------------------------------
            $expiredVouchers = DB::table('coupons')
                ->where(function ($q) {
                    $q->where('expiration_date', '<', Carbon::now())
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('usage_limit')
                            ->whereColumn('used_count', '>=', 'usage_limit');
                    });
                })
                ->select('id', 'code', 'expiration_date', 'used_count', 'usage_limit')
                ->limit(3)
                ->get();

            foreach ($expiredVouchers as $voucher) {
                $isLimitReached = !is_null($voucher->usage_limit) && $voucher->used_count >= $voucher->usage_limit;
                $reason = $isLimitReached ? 'đã hết lượt sử dụng' : 'đã hết hạn';

                $notifications[] = [
                    'id' => 'voucher_' . $voucher->id,
                    'type' => 'warning',
                    'title' => 'Mã giảm giá ' . $reason,
                    'message' => "Mã '{$voucher->code}' {$reason}. Hãy cập nhật mã mới.",
                    'link' => '/quan-tri/ma-giam-gia',
                    'time' => 'Hôm nay'
                ];
            }

            // ----------------------------------------------------------------
            // 3. CẢNH BÁO ĐƠN HÀNG BỊ HỦY
            // ----------------------------------------------------------------
            $cancelledOrders = DB::table('orders')
                ->where('status', 'CANCELLED')
                ->whereDate('created_at', Carbon::today())
                ->select('id', 'cancel_reason', 'created_at')
                ->latest()
                ->limit(3)
                ->get();

            foreach ($cancelledOrders as $order) {
                $notifications[] = [
                    'id' => 'order_' . $order->id,
                    'type' => 'info',
                    'title' => 'Đơn hàng bị hủy',
                    'message' => "Đơn hàng #{$order->id} đã bị hủy. Lý do: " . ($order->cancel_reason ?? 'Không rõ'),
                    'link' => '/quan-tri/don-hang',
                    'time' => Carbon::parse($order->created_at)->diffForHumans()
                ];
            }

            // ----------------------------------------------------------------
            // KẾT QUẢ TRẢ VỀ
            // ----------------------------------------------------------------
            return response()->json([
                'status' => true,
                'unread_count' => count($notifications),
                'notifications' => array_slice($notifications, 0, 10)
            ]);

        } catch (Throwable $e) {
            // Bắt lỗi để không bị văng 500 ra giao diện và dễ debug trong log
            return response()->json([
                'status' => false,
                'message' => 'Lỗi Server: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}