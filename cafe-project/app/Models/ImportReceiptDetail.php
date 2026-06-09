<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportReceiptDetail extends Model
{
    use HasFactory;

    // Chỉ định chính xác tên bảng trong database
    protected $table = 'import_receipt_details';

    // Cho phép điền dữ liệu hàng loạt (Mass Assignment)
    protected $fillable = [
        'receipt_id',
        'material_id',
        'quantity',
        'unit_price',
    ];

    // Ép kiểu dữ liệu cho các thuộc tính số thập phân và số nguyên
    protected $casts = [
        'receipt_id'  => 'integer',
        'material_id' => 'integer',
        'quantity'    => 'decimal:2',
        'unit_price'  => 'decimal:2',
    ];

    /**
     * 📌 Mối quan hệ: Nhiều dòng chi tiết thuộc về một Phiếu nhập kho tổng (Many-to-One)
     * Liên kết ngược lại với bảng import_receipts qua khóa ngoại receipt_id
     */
    public function receipt()
    {
        return $this->belongsTo(ImportReceipt::class, 'receipt_id');
    }

    /**
     * 📌 Mối quan hệ: Chi tiết phiếu nhập liên kết tới một Nguyên liệu cụ thể (Many-to-One)
     * Liên kết ngược lại với bảng materials qua khóa ngoại material_id
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * 💡 Accessor: Tự động tính thành tiền của dòng chi tiết này khi gọi ở giao diện
     * Cách dùng trong Blade hoặc Controller: $detail->total_price
     */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->unit_price;
    }
}