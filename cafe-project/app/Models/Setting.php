<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'description'];

    /**
     * Lấy một giá trị setting theo key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Lấy tất cả setting theo nhóm, trả về mảng key => value.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    protected static function booted()
    {
        // Khi Admin Thêm mới hoặc Cập nhật dữ liệu thành công
        static::saved(function ($setting) {
            Cache::forget('shop_settings_grouped');
        });

        // Khi Admin Xóa một dòng dữ liệu cấu hình
        static::deleted(function ($setting) {
            Cache::forget('shop_settings_grouped');
        });
    }

}