<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleOneTapController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Kiểm tra token gửi lên từ frontend
        $idToken = $request->input('credential');
        
        if (!$idToken) {
            return back()->withErrors(['error' => 'Không tìm thấy thông tin xác thực từ Google.']);
        }

        // 2. Xác thực Token với Google API
        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return back()->withErrors(['error' => 'Xác thực Google Token thất bại.']);
        }

        // 3. Lấy thông tin user từ Google Payload
        $googleId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'];
        $avatar = $payload['picture'] ?? null;

        //  Tìm user cũ hoặc tự động đăng ký user mới
        $user = User::where('email', $email)->first();

        

        if (!$user) {
            $user = User::create([
                'full_name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(24)), // Mật khẩu ngẫu nhiên bảo mật
                // Nếu bạn có lưu avatar hoặc google_id, hãy bổ sung fillable vào Model User
                // 'avatar' => $avatar,
                'google_id' => $googleId,
                'is_email_verified' => 1,
                'email_verified_at' => NOW()
            ]);
        }

        if($user->status == 'banned'){
            return redirect()->route('login')
                ->with(
                    'toast-warning','Tài khoản của bạn đã bị khóa.'
                );
        }

        // 5. Đăng nhập user vào hệ thống (Sử dụng Session mặc định của Inertia)
        Auth::login($user, true);

        // 6. Chuyển hướng về trang home hoặc Trang chủ kèm trạng thái thành công
        return redirect()->intended(route('home'))->with('toast-success', 'Đăng nhập thành công bằng Google!');
    }
}
