<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'discount_type', 'status']);
        $query = Coupon::query();

        //Bộ lọc tìm kiếm theo chuỗi mã code
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        //Bộ lọc theo loại giảm giá (FIXED hoặc PERCENTAGE)
        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        //Bộ lọc theo trạng thái hoạt động
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $coupons = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
            'filters' => $filters,
        ]);
    }

    public function store(CouponStoreRequest $request)
    {
        Coupon::create($request->validated());
        return redirect()->back()->with('toast-success', 'Phát hành mã giảm giá mới thành công!');
    }

    public function update(CouponUpdateRequest $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->validated());
        return redirect()->back()->with('toast-success', 'Cập nhật mã giảm giá thành công!');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        
        // Tránh lỗi gãy dữ liệu nếu mã này đã được áp dụng cho đơn hàng cũ
        if ($coupon->orders()->exists()) {
            return redirect()->back()->with('toast-error', 'Không thể xóa! Mã giảm giá này đã có lịch sử sử dụng trong đơn hàng.');
        }

        $coupon->delete();
        return redirect()->back()->with('toast-success', 'Xóa mã giảm giá thành công!');
    }
}