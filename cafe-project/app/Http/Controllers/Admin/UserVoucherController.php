<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Requests\Admin\GiftVoucherRequest;
use App\Models\Coupon;

class UserVoucherController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_used','coupon_id']);
        
        // Lấy danh sách voucher của khách hàng, kèm thông tin khách hàng và thông tin mã giảm giá
        $query = CouponUser::with([
            'user:id,full_name,phone_number,email', 
            'coupon:id,code,discount_type,discount_value,max_discount_amount,min_order_value,used_count,usage_limit,expiration_date'
        ])->whereHas('user', function ($q) {
            $q->where('role', 'CUSTOMER');
        });

        //Bộ lọc tìm kiếm: Theo Tên khách, Email, Số điện thoại hoặc ký tự Mã giảm giá
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', function ($u) use ($request) {
                    $u->where('full_name', 'like', '%' . $request->search . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
                })->orWhereHas('coupon', function ($c) use ($request) {
                    $c->where('code', 'like', '%' . $request->search . '%');
                });
            });
        }

        // Bộ lọc theo trạng thái sử dụng
        if ($request->filled('is_used')) {
            $query->where('is_used', $request->is_used);
        }

        if ($request->filled('coupon_id')) {
            $query->where('coupon_id', $request->coupon_id);
        }

        // lấy tất cả mã giảm giá trừ các mã đã hết hạn
        $allCoupons = Coupon::select('id', 'code', 'discount_type', 'discount_value', 'expiration_date')->where('expiration_date', '>', now())->get();

        // Lấy danh sách toàn bộ khách hàng đang hoạt động để nạp vào ô tặng lẻ
        $allCustomers = User::where('role', 'CUSTOMER')
                        ->where('status', 'active')
                        ->select('id', 'full_name', 'phone_number', 'email')
                        ->get();

        // Sắp xếp theo ID giảm dần và phân trang 10 bản ghi mỗi trang, giữ nguyên các tham số truy vấn
        $vouchers = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/UserVouchers/Index', [
            'vouchers' => $vouchers,
            'filters'  => $filters,
            'all_coupons'   => $allCoupons,
            'all_customers' => $allCustomers,
        ]);
    }

    //XỬ LÝ PHÁT HÀNH / TẶNG VOUCHER (CẢ LẺ LẪN HÀNG LOẠT)
    public function store(GiftVoucherRequest $request)
    {
        $couponId = $request->coupon_id;

        // Kịch bản 1: Gửi hàng loạt cho TOÀN BỘ khách hàng thành viên để kích cầu
        if ($request->send_to_all) {
            $customerIds = User::where('role', 'CUSTOMER')
                                ->where('status', 'active')
                                ->pluck('id');

            foreach ($customerIds as $userId) {
                // Kiểm tra tránh phát trùng mã nếu khách đã có sẵn trong ví
                $exists = CouponUser::where('user_id', $userId)
                                    ->where('coupon_id', $couponId)
                                    ->where('is_used', false)
                                    ->exists();
                if (!$exists) {
                    CouponUser::create([
                        'user_id'   => $userId,
                        'coupon_id' => $couponId,
                        'is_used'   => false,
                    ]);
                }
            }

            return redirect()->back()->with('toast-success', 'Chiến dịch kích cầu thành công! Đã thêm Voucher hàng loạt vào ví toàn bộ khách hàng.');
        }

        // Kịch bản 2: Tặng lẻ cho 1 khách hàng cụ thể (Ví dụ: Sự kiện Sinh nhật)
        $userId = $request->user_id;
        
        $exists = CouponUser::where('user_id', $userId)
                            ->where('coupon_id', $couponId)
                            ->where('is_used', false)
                            ->exists();

        if ($exists) {
            return redirect()->back()->with('toast-error', 'Khách hàng này hiện đang sở hữu mã này trong ví và chưa sử dụng!');
        }

        CouponUser::create([
            'user_id'   => $userId,
            'coupon_id' => $couponId,
            'is_used'   => false,
        ]);

        return redirect()->back()->with('toast-success', 'Đã tặng thành công Voucher sinh nhật vào ví khách hàng!');
    }

    // Đặc quyền của Admin: Thu hồi thu công voucher khỏi ví của khách hàng nếu cấp nhầm
    public function destroy($id)
    {
        $userVoucher = CouponUser::findOrFail($id);
        $userVoucher->delete();
        
        return redirect()->back()->with('toast-success', 'Đã thu hồi Voucher khỏi ví người dùng thành công!');
    }
}