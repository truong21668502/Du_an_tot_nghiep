<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Liên hệ mới từ khách hàng</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <div style="background: #C0392B; padding: 20px 30px;">
            <h2 style="color: #ffffff; margin: 0; font-size: 18px;">Liên hệ mới từ khách hàng</h2>
        </div>
        <div style="padding: 30px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; color: #999; width: 120px; font-size: 14px;">Họ và tên:</td>
                    <td style="padding: 10px 0; color: #333; font-size: 14px; font-weight: bold;">{{ $data['name'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #999; font-size: 14px;">Email:</td>
                    <td style="padding: 10px 0; color: #333; font-size: 14px;">{{ $data['email'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #999; font-size: 14px;">Số điện thoại:</td>
                    <td style="padding: 10px 0; color: #333; font-size: 14px;">{{ $data['phone'] ?? 'Không có' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #999; font-size: 14px;">Chủ đề:</td>
                    <td style="padding: 10px 0; color: #333; font-size: 14px;">{{ $data['subject'] }}</td>
                </tr>
            </table>
            <div style="margin-top: 20px; padding: 20px; background: #fdf2f2; border-left: 4px solid #C0392B; border-radius: 4px;">
                <p style="margin: 0 0 10px 0; color: #999; font-size: 14px;">Nội dung tin nhắn:</p>
                <p style="margin: 0; color: #333; line-height: 1.8; font-size: 14px;">{{ $data['message'] }}</p>
            </div>
            <p style="color: #999; font-size: 12px; margin-top: 30px;">Thời gian gửi: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>