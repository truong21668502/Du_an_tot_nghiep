<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;
use App\Models\Material;
use App\Services\MaterialExpiryService;
use App\Models\AdminNotification;

class NotificationController extends Controller
{
    
    /**
     * Ngưỡng cảnh báo cận hạn (ngày). Đồng bộ với isExpiringSoon() bên frontend (Warehouse/Index.vue).
     */
    private const EXPIRING_SOON_DAYS = 7;

    public function getNotifications(MaterialExpiryService $expiryService)
    {
        try {
            $liveNotifications = array_merge(
                $this->buildStockNotifications(),
                $this->buildVoucherNotifications(),
                $this->buildCancelledOrderNotifications(),
                $this->buildExpiryNotifications($expiryService)
            );

            foreach ($liveNotifications as $item) {
                // Tự động phân loại category dựa vào prefix của ID (stock, voucher, order, expired/expiring)
                $category = explode('_', $item['id'])[0] ?? 'general';

                AdminNotification::firstOrCreate(
                    ['dedup_key' => $item['id']],
                    [
                        'category' => $category, 
                        'type'     => $item['type'],
                        'title'    => $item['title'],
                        'message'  => $item['message'],
                        'link'     => $item['link'],
                    ]
                );
            }

            // Lấy danh sách từ DB
            $dbNotifications = AdminNotification::latest('updated_at')
                ->limit(10)
                ->get()
                ->map(fn($n) => [
                    'id'       => $n->id,
                    'type'     => $n->type,
                    'title'    => $n->title,
                    'message'  => $n->message,
                    'link'     => $n->link,
                    'is_read'  => !is_null($n->read_at),
                    'time'     => $n->updated_at ? $n->updated_at->diffForHumans() : $n->created_at->diffForHumans(),
                ]);

            $unreadCount = AdminNotification::whereNull('read_at')->count();

            return response()->json([
                'status'        => true,
                'unread_count'  => $unreadCount,
                'notifications' => $dbNotifications,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi Server: ' . $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Đánh dấu thông báo là đã đọc
     */
    public function markAsRead($id)
    {
        AdminNotification::where('id', $id)->update(['read_at' => now()]);

        return response()->json(['status' => true]);
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     */
    public function markAllAsRead()
    {
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['status' => true]);
    }

    /**
     * Format số lượng: bỏ số 0 thừa sau dấu thập phân (2000.00 -> 2000, 1500.50 -> 1500.5).
     */
    private function formatQty($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    /**
     * 1. Cảnh báo tồn kho: hết hàng hoặc dưới ngưỡng min_stock.
     */
    private function buildStockNotifications(): array
    {
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

        return $lowStockItems->map(fn($item) => [
            'id' => 'stock_' . $item->id,
            'type' => 'danger',
            'title' => 'Cảnh báo tồn kho',
            'message' => "Nguyên liệu '{$item->material_name}' chỉ còn " . $this->formatQty($item->quantity_in_stock) . " {$item->base_unit} trong kho.",
            'link' => '/quan-tri/kho',
            'time' => 'Mới nhất',
        ])->all();
    }

    /**
     * 2. Cảnh báo mã giảm giá: hết hạn hoặc hết lượt sử dụng.
     */
    private function buildVoucherNotifications(): array
    {
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

        return $expiredVouchers->map(function ($voucher) {
            $isLimitReached = !is_null($voucher->usage_limit) && $voucher->used_count >= $voucher->usage_limit;
            $reason = $isLimitReached ? 'đã hết lượt sử dụng' : 'đã hết hạn';

            return [
                'id' => 'voucher_' . $voucher->id,
                'type' => 'warning',
                'title' => 'Mã giảm giá ' . $reason,
                'message' => "Mã '{$voucher->code}' {$reason}. Hãy cập nhật mã mới.",
                'link' => '/quan-tri/ma-giam-gia',
                'time' => 'Hôm nay',
            ];
        })->all();
    }

    /**
     * 3. Cảnh báo đơn hàng bị huỷ trong ngày hôm nay.
     */
    private function buildCancelledOrderNotifications(): array
    {
        $cancelledOrders = DB::table('orders')
            ->where('status', 'CANCELLED')
            ->select('id', 'cancel_reason', 'created_at')
            ->latest()
            ->limit(3)
            ->get();

        return $cancelledOrders->map(fn($order) => [
            'id' => 'order_' . $order->id,
            'type' => 'info',
            'title' => 'Đơn hàng bị hủy',
            'message' => "Đơn hàng #{$order->id} đã bị hủy. Lý do: " . ($order->cancel_reason ?? 'Không rõ'),
            'link' => '/quan-tri/don-hang',
            'time' => Carbon::parse($order->created_at)->diffForHumans(),
        ])->all();
    }

    /**
     * 4. Cảnh báo nguyên liệu cận hạn / đã hết hạn.
     * Dùng hạn hiệu lực (đã tính opened_at + shelf_life_after_opening_days), không dùng expiry_date thô.
     * Không cần cron/job riêng — tính real-time mỗi lần gọi API, giống 3 mục trên.
     */
    private function buildExpiryNotifications(MaterialExpiryService $expiryService): array
    {
        $today = Carbon::today();
        $threshold = $today->copy()->addDays(self::EXPIRING_SOON_DAYS);

        $materialsWithStock = Material::where('quantity_in_stock', '>', 0)
            ->select('id', 'material_name', 'base_unit')
            ->get();

        if ($materialsWithStock->isEmpty()) {
            return [];
        }

        $nearestByMaterial = $expiryService->nearestExpiryForMany(
            $materialsWithStock->pluck('id')->all()
        );

        $notifications = [];

        foreach ($materialsWithStock as $material) {
            $nearest = $nearestByMaterial[$material->id] ?? null;
            if (!$nearest) {
                continue;
            }

            // Chuẩn hoá về đầu ngày để so sánh/diff không dính giờ:phút:giây
            $nearestDate = $nearest->copy()->startOfDay();

            if ($nearestDate->lt($today)) {
                $notifications[] = [
                    'id' => 'expired_' . $material->id,
                    'type' => 'danger',
                    'title' => 'Nguyên liệu đã hết hạn',
                    'message' => "'{$material->material_name}' có lô đã hết hạn sử dụng, cần kiểm tra và loại bỏ.",
                    'link' => '/quan-tri/kho',
                    'time' => 'Cần xử lý ngay',
                ];
            } elseif ($nearestDate->lte($threshold)) {
                $daysLeft = (int) $today->diffInDays($nearestDate);
                $notifications[] = [
                    'id' => 'expiring_' . $material->id,
                    'type' => 'warning',
                    'title' => 'Nguyên liệu sắp hết hạn',
                    'message' => "'{$material->material_name}' còn {$daysLeft} ngày là hết hạn.",
                    'link' => '/quan-tri/kho',
                    'time' => $nearestDate->format('d/m/Y'),
                ];
            }
        }

        return $notifications;
    }
}