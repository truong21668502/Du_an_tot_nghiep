<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In mã QR bàn</title>

    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        /* Khung chứa nút điều khiển */
        .controls {
            position: sticky;
            top: 0;
            background: white;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border: 2px solid #665d50
;
            border-radius: 5px;
            background-color: white;
            color: #665d50
;
            transition: all 0.3s;
        }

        .btn:hover {
            background-color: #665d50
;
            color: white;
        }

        /* Container chính */
        .container {
            display: flex;
            flex-wrap: wrap;
            max-width: 210mm; /* Chuẩn chiều ngang A4 */
            margin: 20px auto;
            background: white;
        }

        /* Thẻ chứa từng QR */
        .item {
            width: 50%;
            box-sizing: border-box;
            padding: 30px 20px; /* Tăng padding để thoáng hơn */
            text-align: center;
            page-break-inside: avoid;
            position: relative;
        }

        /* Lớp viền dùng để cắt (Mặc định ẩn, hiện khi nhấn "In có viền") */
        .container.has-border .item {
            outline: 1px dashed #ccc;
        }

        /* Tăng kích thước ảnh QR */
        .item img {
            width: 240px;  /* Tăng từ 180px lên 240px */
            height: 240px; /* Tăng từ 180px lên 240px */
            display: block;
            margin: 0 auto;
        }

        .item h3 {
            margin: 0 0 15px 0;
            font-size: 24px;
            color: #333;
        }

        .item p {
            margin: 15px 0 0 0;
            font-size: 16px;
            font-weight: bold;
            color: #555;
        }

        /* Cấu hình khi IN */
        @media print {
            body {
                background: white;
            }
            .controls {
                display: none; /* Ẩn thanh công cụ khi in */
            }
            .container {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="controls">
    <button class="btn" onclick="printWithBorder(true)">🖨️ In có viền (Có đường cắt)</button>
    <button class="btn" onclick="printWithBorder(false)">🖨️ In không viền</button>
</div>

<div class="container" id="printContainer">

@foreach($tables as $table)

    @if(!$table->qr_code)
        @continue
    @endif

    <div class="item">
        <h3>{{ $table->table_name }}</h3>

        <img 
            src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data={{ urlencode(route('table.order', ['qr_code' => $table->qr_code])) }}" 
            alt="QR Code"
        >

        <p>Quét để gọi món</p>
    </div>

@endforeach

</div>

<script>
// Hàm xử lý bật/tắt viền và kích hoạt lệnh in
function printWithBorder(hasBorder) {
    const container = document.getElementById('printContainer');
    
    if (hasBorder) {
        container.classList.add('has-border');
    } else {
        container.classList.remove('has-border');
    }

    // Kích hoạt in
    window.print();
}

window.onafterprint = () => {
    window.close(); 
};

window.onload = () => {
    const images = Array.from(document.images);
    Promise.all(
        images.map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = resolve;
                img.onerror = resolve;
            });
        })
    ).then(() => {
        console.log("Tất cả mã QR đã tải xong, sẵn sàng in!");
    });
};
</script>

</body>
</html>