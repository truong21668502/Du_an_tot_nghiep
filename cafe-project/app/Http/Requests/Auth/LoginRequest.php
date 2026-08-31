<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Xác định người dùng có được phép gửi request hay không.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Quy tắc kiểm tra dữ liệu.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Thông báo lỗi.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ];
    }

    /**
     * Tên hiển thị của các trường.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email',
            'password' => 'mật khẩu',
        ];
    }

    /**
     * Xử lý đăng nhập.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        // Kiểm tra giới hạn số lần đăng nhập
        $this->ensureIsNotRateLimited();
        
        $user = User::where('email', $this->email)->first();

        if ($user && $user->google_id) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản này đã đăng ký bằng Google. Vui lòng đăng nhập bằng Google.',
            ]);
        }

        // Kiểm tra thông tin đăng nhập
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Nếu đăng nhập thất bại, tăng số lần đăng nhập(ghi nhận vào RateLimiter)
            RateLimiter::hit($this->throttleKey());

            // Ném ra ngoại lệ với thông báo lỗi
            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ]);
        }

        // Nếu đăng nhập thành công, xóa giới hạn đăng nhập
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Kiểm tra giới hạn số lần đăng nhập.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        // Nếu số lần đăng nhập vượt quá giới hạn, ném ra ngoại lệ
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        // Gửi sự kiện khóa đăng nhập
        event(new Lockout($this));

        // Lấy thời gian còn lại trước khi có thể thử lại
        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Ném ra ngoại lệ với thông báo lỗi
        throw ValidationException::withMessages([
            'email' => "Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.",
        ]);
    }

    /**
     * Tạo khóa giới hạn đăng nhập.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')) . '|' . $this->ip()
        );
    }
}