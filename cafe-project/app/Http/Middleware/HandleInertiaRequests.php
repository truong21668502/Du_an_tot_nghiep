<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user(),
            ],

            'brand' => fn () => \App\Models\Brand::first(),

            'auth_config' => [
                'google_client_id' => env('GOOGLE_CLIENT_ID'),
            ],

            'flash' => [
                'toast-success' => fn () => $request->session()->get('toast-success'),
                'toast-error' => fn () => $request->session()->get('toast-error'),
                'toast-warning' => fn () => $request->session()->get('toast-warning'),
            ],

            'globalProducts' => fn () => \App\Models\Product::where('is_active', 'Đang bán')->with('variants')->get(),
            'globalTables' => fn () => \App\Models\Table::orderBy('id', 'asc')->get(),
        ];
    }
}
