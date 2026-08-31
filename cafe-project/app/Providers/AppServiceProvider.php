<?php

namespace App\Providers;

use App\Models\TestRealTime;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Observers\TestRealTimeObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Models\Brand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        TestRealTime::observe(TestRealTimeObserver::class);

        // Kiểm tra bảng tồn tại trước để tránh lỗi khi chạy migrate lần đầu
        if (Schema::hasTable('brands')) {
            View::composer('*', function ($view) {
                // Dùng Cache để tối ưu tốc độ, lấy bản ghi thương hiệu đầu tiên
                $currentBrand = Cache::rememberForever('global_brand', function () {
                    return Brand::first();
                });

                $view->with('currentBrand', $currentBrand);
            });
        }
    }
}
