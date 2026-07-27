<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\OrderStatusUpdated;
use App\Services\RecipeStockService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->with([
                'table:id,table_name,area',
                'user:id,full_name,phone_number',
                'payment',
                'details.product:id,product_name,image_url',
                'details.variant:id,size,price',
            ])
            ->withCount('details');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->input('order_type'));
        }

        if ($request->filled('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest('created_at')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'tables' => Table::select('id', 'table_name')->orderBy('table_name')->get(),
            'filters' => $request->only(['status', 'order_type', 'table_id', 'date_from', 'date_to', 'search', 'source']),
        ]);
    }

    public function show(Request $request, Order $order, RecipeStockService $recipeStockService)
    {
        $order->load([
            'table:id,table_name,area',
            'user:id,full_name,phone_number,email',
            'coupon:id,code,discount_type,discount_value',
            'payment',
            'details.product:id,product_name,image_url',
            'details.variant:id,size,price',
        ]);
        $order->loadCount('details');

        if ($request->wantsJson()) {
            return response()->json($order);
        }

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'missingRecipeVariants' => $recipeStockService->findVariantsWithoutRecipe($order),
        ]);
    }

    public function updateStatus(Request $request, Order $order, RecipeStockService $recipeStockService)
    {
        $validated = $request->validate([
            'status' => 'required|in:PENDING,PROCESSING,READY,DELIVERING,COMPLETED,CANCELLED',
            'cancel_reason' => 'required_if:status,CANCELLED|nullable|string|max:255',
        ]);

        $result = DB::transaction(function () use ($order, $validated) {
            // Lock đơn hàng ngay từ đầu — chặn 2 request đổi trạng thái cùng lúc (idempotency)
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if (in_array($order->status, ['COMPLETED', 'CANCELLED'])) {
                return ['error' => 'Đơn hàng đã ở trạng thái cuối, không thể thay đổi.'];
            }

            if ($validated['status'] === 'DELIVERING' && $order->order_type !== 'DELIVERY') {
                return ['error' => 'Trạng thái "Đang giao" chỉ áp dụng cho đơn giao hàng.'];
            }

            if ($validated['status'] === 'CANCELLED' && $order->status === 'DELIVERING') {
                return ['error' => 'Không thể hủy đơn đang giao hàng.'];
            }

            if ($validated['status'] === 'COMPLETED') {
                // KHÔNG trừ kho ở đây nữa — kho đã được trừ từng món
                // ngay khi barista hoàn thành ở BaristaController::updateStatus().
                // Ở đây chỉ kiểm tra xem tất cả món đã pha xong (hoặc đã bị huỷ) chưa.
                $order->loadMissing('details');

                $notReady = $order->details
                    ->where('barista_status', '!=', 'COMPLETED')
                    ->where('barista_status', '!=', 'CANCELLED');

                if ($notReady->isNotEmpty()) {
                    $names = $notReady->pluck('product.product_name')->filter()->implode(', ');
                    return [
                        'error' => "Còn món chưa pha chế xong, không thể hoàn tất đơn"
                            . ($names ? ": {$names}" : '.'),
                    ];
                }
            }

            $order->status = $validated['status'];
            $order->cancel_reason = $validated['status'] === 'CANCELLED'
                ? $validated['cancel_reason']
                : null;
            $order->save();

            return [
                'order' => $order->fresh([
                    'table',
                    'payment',
                    'details.product',
                    'details.variant',
                ])
            ];
        });

        if (isset($result['error'])) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $result['error'],
                    'insufficient' => $result['insufficient'] ?? null,
                ], 422);
            }
            return back()->with('toast-error', $result['error']);
        }

        broadcast(new OrderStatusUpdated($result['order']));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cập nhật trạng thái đơn hàng thành công.',
                'order' => $result['order'],
            ]);
        }

        return back()->with('toast-success', 'Cập nhật trạng thái đơn hàng thành công.');
    }
}