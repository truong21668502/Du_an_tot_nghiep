<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khôi phục mật khẩu - {{ config('app.name') }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f5f3f1; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 520px; margin: 40px auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .header { background: #665d50; padding: 32px 24px; text-align: center; }
        .header img { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); margin-bottom: 12px; }
        .header h1 { color: #ffffff; font-size: 20px; font-weight: 700; margin: 0; }
        .body { padding: 32px 28px; color: #3a3a3a; line-height: 1.7; font-size: 15px; }
        .body p { margin: 0 0 16px; }
        .btn { display: inline-block; background: #665d50; color: #ffffff !important; text-decoration: none; padding: 14px 36px; border-radius: 50px; font-size: 15px; font-weight: 600; margin: 8px 0 20px; }
        .btn:hover { background: #544d42; }
        .url { word-break: break-all; color: #665d50; font-size: 13px; }
        .footer { background: #fbf9f7; padding: 20px 28px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #e4e2e0; }
        .divider { height: 1px; background: #e4e2e0; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1780996916/NangCoffee_logo_fullmau_wl8jbz.png" alt="Logo" />
            <h1>Khôi phục mật khẩu</h1>
        </div>
        <div class="body">
            <p>Xin chào <strong>{{ $name }}</strong>,</p>
            <p>Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản tại <strong>{{ config('app.name') }}</strong>. Nhấn nút bên dưới để tiếp tục:</p>
            <div style="text-align: center; color: #fff;">
                <a href="{{ $url }}" class="btn">Đặt lại mật khẩu</a>
            </div>
            <p class="url">Hoặc sao chép liên kết này: <br/>{{ $url }}</p>
            <div class="divider"></div>
            <p style="color: #999; font-size: 13px;">Liên kết sẽ hết hạn sau <strong>{{ $expire }} phút</strong>. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Tất cả quyền được bảo lưu.
        </div>
    </div>
</body>
</html>
