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
        $this->ensureIsNotRateLimited();
        
        $user = User::where('email', $this->email)->first();

        if ($user && $user->google_id) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản này đã đăng ký bằng Google. Vui lòng đăng nhập bằng Google.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Kiểm tra giới hạn số lần đăng nhập.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

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