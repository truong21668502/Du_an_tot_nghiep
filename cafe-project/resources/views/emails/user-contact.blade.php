<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cảm ơn bạn đã liên hệ</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <div style="background: #4A3728; padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Nắng Coffee</h1>
        </div>
        <div style="padding: 30px;">
            <h2 style="color: #4A3728; margin-top: 0;">Xin chào {{ $data['name'] }},</h2>
            <p style="color: #555; line-height: 1.8; font-size: 15px;">
                Cảm ơn bạn đã liên hệ với <strong>Nắng Coffee</strong>. Chúng tôi đã nhận được tin nhắn của bạn với nội dung:
            </p>
            <div style="background: #f9f5f0; border-left: 4px solid #4A3728; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0; color: #555; font-style: italic;">"{{ $data['message'] }}"</p>
            </div>
            <p style="color: #555; line-height: 1.8; font-size: 15px;">
                Chúng tôi sẽ phản hồi lại bạn trong thời gian sớm nhất qua email <strong> truong21668502@gmail.com </strong>.
            </p>
            <p style="color: #555; line-height: 1.8; font-size: 15px;">
                Nếu bạn có bất kỳ câu hỏi nào khác, đừng ngần ngại liên hệ với chúng tôi.
            </p>
            <p style="color: #555; margin-top: 30px;">
                Trân trọng,<br>
                <strong>Đội ngũ Nắng Coffee</strong>
            </p>
        </div>
        <div style="background: #f9f5f0; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="color: #999; font-size: 12px; margin: 0;">© {{ date('Y') }} Nắng Coffee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>