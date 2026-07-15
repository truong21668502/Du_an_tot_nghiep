<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\OrderStatusUpdated;
use App\Services\RecipeStockService;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->with(['table:id,table_name,area', 'user:id,name,phone', 'payment'])
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

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Đơn hàng cần theo dõi realtime nên sắp mới nhất lên đầu (khác pattern
        // alphabetical đang dùng cho kho, vì bản chất workflow khác nhau)
        $orders = $query->latest('created_at')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'tables' => Table::select('id', 'table_name')->orderBy('table_name')->get(),
            'filters' => $request->only(['status', 'order_type', 'table_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    public function show(Request $request, Order $order, RecipeStockService $recipeStockService)
    {
        $order->load([
            'table:id,table_name,area',
            'user:id,name,phone,email',
            'coupon:id,code,discount_type,discount_value',
            'payment',
            'details.product:id,product_name,image_url',
            'details.variant:id,size,price',
        ]);

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

        if (in_array($order->status, ['COMPLETED', 'CANCELLED'])) {
            $message = 'Đơn hàng đã ở trạng thái cuối, không thể thay đổi.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // DELIVERING chỉ hợp lệ với đơn giao hàng
        if ($validated['status'] === 'DELIVERING' && $order->order_type !== 'DELIVERY') {
            $message = 'Trạng thái "Đang giao" chỉ áp dụng cho đơn giao hàng.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // Không cho hủy khi đã bắt đầu giao hàng
        if ($validated['status'] === 'CANCELLED' && $order->status === 'DELIVERING') {
            $message = 'Không thể hủy đơn đang giao hàng.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($validated['status'] === 'COMPLETED') {
            $insufficient = $recipeStockService->checkAvailability($order);

            if (!empty($insufficient)) {
                $names = collect($insufficient)->pluck('material_name')->implode(', ');
                $message = "Không đủ nguyên liệu để hoàn thành đơn: {$names}.";

                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => $message,
                        'insufficient' => $insufficient,
                    ], 422);
                }

                return back()->with('toast-error', $message);
            }
        }

        $order->status = $validated['status'];
        $order->cancel_reason = $validated['status'] === 'CANCELLED'
            ? $validated['cancel_reason']
            : null;
        $order->save();

        broadcast(new OrderStatusUpdated(
            $order->fresh([
                'table',
                'payment',
                'details.product',
                'details.variant',
            ])
        ));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cập nhật trạng thái đơn hàng thành công.',
                'order' => $order->fresh(['table', 'payment']),
            ]);
        }

        return back()->with('toast-success', 'Cập nhật trạng thái đơn hàng thành công.');
    }
}