<?php

namespace App\Providers;

use App\Models\TestRealTime;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Observers\TestRealTimeObserver;

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

    }
}
