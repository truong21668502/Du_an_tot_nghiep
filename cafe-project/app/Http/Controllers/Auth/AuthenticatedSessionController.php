<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        //trả về trang chủ khách hàng
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function create_system(): Response
    {
        //trả về trang đăng nhập hệ thống admin
        return Inertia::render('Admin/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if ($user->status === 'banned') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('toast-warning', 'Tài khoản của bạn đã bị khóa.');
        }

        $request->session()->regenerate();

        return match ($user->role) {
            'ADMIN'   => redirect()->route('admin.dashboard'),
            'STAFF'   => redirect()->route('staff.dashboard'),
            'BARISTA' => redirect()->route('barista.queue'),
            'SHIPPER' => redirect()->route('shipper.orders.index'),
            default   => redirect()->intended(route('home')),
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::user()->role !== "CUSTOMER") {
            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/dang-nhap-he-thong');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->with('toast-success', 'Đăng xuất thành công!');
    }
}
