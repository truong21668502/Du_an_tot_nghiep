<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Setting;
use App\Models\User;

class VoucherGiftService
{
    /**
     * Gán voucher tặng cho user (nếu có cài đặt).
     *
     * @param User $user
     * @return bool
     */
    public function assignGiftVoucher(User $user): bool
    {
        // Lấy mã voucher từ setting
        $voucherCode = Setting::where('key', 'voucher_gift')->value('value');

        if (empty($voucherCode)) {
            return false;
        }

        // Tìm coupon hợp lệ
        $coupon = Coupon::query()
            ->where('code', $voucherCode)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return false;
        }

        // Tạo bản ghi CouponUser
        CouponUser::create([
            'user_id'   => $user->id,
            'coupon_id' => $coupon->id,
            'is_used'   => false,
            'used_at'   => null,
        ]);

        return true;
    }
}