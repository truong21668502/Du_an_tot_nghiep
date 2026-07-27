<?php

namespace App\Http\Controllers\Customer;

use App\Models\Coupon;
use App\Models\CouponUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class VoucherController extends Controller
{
    /**
     * Trang ví voucher - hiển thị danh sách voucher đã lưu của user.
     * Dùng Inertia render, không reload toàn trang khi chuyển hướng.
     */
    public function index()
    {
        $vouchers = CouponUser::with('coupon')
            ->where('user_id', Auth::id())
            ->latest()                         // mới lưu gần đây lên trước
            ->paginate(10);

        return Inertia::render('Profile/Partials/Coupon', [
            'vouchers' => $vouchers,
        ]);
    }

    /**
     * Lưu voucher vào ví (API).
     * Nhận mã giảm giá, kiểm tra hợp lệ rồi thêm vào bảng coupon_user.
     * Trả về JSON để component Vue gọi bằng axios, không reload trang.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $user = Auth::user();
        $code = strtoupper(trim($request->code));

        // Tìm coupon theo code
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([ 'success' => false , 'message' => 'Mã giảm giá không tồn tại.'], 404);
        }

        // Kiểm tra trạng thái
        if ($coupon->status !== 'ACTIVE') {
            return response()->json([ 'success' => false , 'message' => 'Mã giảm giá không còn hoạt động.'], 400);
        }

        // Kiểm tra hạn sử dụng (tự động cập nhật trạng thái hết hạn nếu quá ngày)
        if ($coupon->expiration_date->isPast()) {
            $coupon->update(['status' => 'EXPIRED']);
            return response()->json([ 'success' => false , 'message' => 'Mã giảm giá đã hết hạn.'], 400);
        }

        // Kiểm tra giới hạn số lượng phát hành (usage_limit)
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([ 'success' => false , 'message' => 'Mã giảm giá đã hết lượt sử dụng.'], 400);
        }

        // Không cho lưu trùng mã đang có trong ví và chưa dùng
        $alreadySaved = CouponUser::where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->where('is_used', false)
            ->exists();

        if ($alreadySaved) {
            return response()->json(['message' => 'Bạn đã lưu mã này vào ví rồi.'], 400);
        }

        // Tiến hành lưu voucher và tăng used_count trong transaction
        DB::beginTransaction();
        try {
            // Tạo bản ghi trong ví
            $voucher = CouponUser::create([
                'user_id'   => $user->id,
                'coupon_id' => $coupon->id,
                'is_used'   => false,
                'used_at'   => null,
            ]);

            // Nếu sau khi tăng đã đạt đến giới hạn thì chuyển trạng thái thành INACTIVE
            $coupon->refresh();
            if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                $coupon->update(['status' => 'INACTIVE']);
            }

            DB::commit();

            // Load quan hệ coupon để trả về đầy đủ thông tin
            $voucher->load('coupon');

            return response()->json([
                'message' => '🎉 Lưu mã giảm giá thành công!',
                'voucher' => $voucher,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống, vui lòng thử lại sau.'], 500);
        }
    }
}