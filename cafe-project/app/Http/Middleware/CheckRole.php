<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (Auth::check() && Auth::user()->status === 'banned') {
            Auth::logout();

            return redirect()->route('login')
                ->with(
                    'toast-warning','Tài khoản của bạn đã bị khóa.'
                );
        }

        if (!Auth::check()) {
            // Redirect theo role được yêu cầu
            if (in_array('ADMIN', $roles)) {
                return redirect()->route('admin.login');
            }

            if (in_array('CUSTOMER', $roles)) {
                return redirect()->route('login');
            }

            // Default login route
            return redirect()->route('login');
        }

        if (in_array(auth()->user()->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Bạn không có quyền truy cập vào trang này.');
    }
}
