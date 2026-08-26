<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\VoucherGiftService;

class GoogleOneTapController extends Controller
{

    protected VoucherGiftService $voucherGiftService;

    public function __construct(VoucherGiftService $voucherGiftService)
    {
        $this->voucherGiftService = $voucherGiftService;
    }
        public function handle(Request $request)
        {
            $idToken = $request->input('credential');
            if (!$idToken) {
                return back()->withErrors(['error' => 'Không tìm thấy thông tin xác thực từ Google.']);
            }

            $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
            $payload = $client->verifyIdToken($idToken);
            if (!$payload) {
                return back()->withErrors(['error' => 'Xác thực Google Token thất bại.']);
            }

            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'];
            $avatar = $payload['picture'] ?? null;

            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = User::create([
                    'full_name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'google_id' => $googleId,
                    'is_email_verified' => 1,
                    'email_verified_at' => now(),
                ]);

                // 👇 Tặng voucher cho user mới (nếu có cài đặt)
                $this->voucherGiftService->assignGiftVoucher($user);
            }

            if ($user->status == 'banned') {
                return redirect()->route('login')
                    ->with('toast-warning', 'Tài khoản của bạn đã bị khóa.');
            }

            Auth::login($user, true);

            return redirect()->intended(route('home'))
                ->with('toast-success', 'Đăng nhập thành công bằng Google!');
        }
}
