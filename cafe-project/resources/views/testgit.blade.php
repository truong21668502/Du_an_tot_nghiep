<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Git Project</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        h1 {
            color: #24292e;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            font-size: 16px;
        }
        .btn {
            background-color: #2ea44f;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover {
            background-color: #2c974b;
        }
        .status {
            margin-top: 15px;
            font-weight: bold;
            color: #0366d6;
        }
    </style>
</head>
<body>

    <div class="card">
        <h1>Git Connection Test</h1>
        <p>Nếu bạn thấy trang này, nghĩa là bạn đã mở file thành công trên máy local.</p>
        
        <button class="btn" onclick="testGit()">Bấm vào đây để test</button>
        
        <div id="message" class="status"></div>
    </div>

    <script>
        function testGit() {
            const msgDiv = document.getElementById('message');
            msgDiv.innerText = "🎉 Code đã chạy mượt mà! Thử sửa dòng này rồi commit xem sao.";
            msgDiv.style.color = "#2ea44f";
        }
    </script>
</body>
</html>