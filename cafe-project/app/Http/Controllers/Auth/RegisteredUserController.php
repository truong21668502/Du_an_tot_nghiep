<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\VoucherGiftService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    protected VoucherGiftService $voucherGiftService;

    // Inject service qua constructor
    public function __construct(VoucherGiftService $voucherGiftService)
    {
        $this->voucherGiftService = $voucherGiftService;
    }

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // 1. Tạo user
        $user = User::create([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
        ]);

        // 2. Tặng voucher (nếu có)
        $this->voucherGiftService->assignGiftVoucher($user);

        // 3. Sự kiện và đăng nhập
        event(new Registered($user));
        Auth::login($user);

        // 4. Redirect theo role
        return match ($user->role) {
            'ADMIN'   => redirect()->route('admin.dashboard'),
            'STAFF'   => redirect()->route('staff.dashboard'),
            'BARISTA' => redirect()->route('barista.dashboard'),
            'SHIPPER' => redirect()->route('shipper.orders.index'),
            default   => redirect()->route('verification.notice'),
        };
    }
}