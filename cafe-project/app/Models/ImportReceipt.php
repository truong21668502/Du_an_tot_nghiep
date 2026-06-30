<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportReceipt extends Model
{
    use HasFactory;

    // Chỉ định chính xác tên bảng trong database
    protected $table = 'import_receipts';

    // Cho phép điền dữ liệu hàng loạt (Mass Assignment)
    protected $fillable = [
        'user_id',
        'supplier_name',
        'total_cost',
        'note',
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    // Ép kiểu dữ liệu cho các thuộc tính đặc thù
    protected $casts = [
        'user_id' => 'integer',
        'total_cost' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    /**
     * 📌 Mối quan hệ: Nhiều phiếu nhập được tạo bởi một Nhân viên/Người dùng (Many-to-One)
     * Liên kết ngược lại với bảng users qua khóa ngoại user_id
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 📌 Mối quan hệ: Một phiếu nhập có nhiều dòng Chi tiết phiếu nhập (One-to-Many)
     * Liên kết tới bảng import_receipt_details (nếu bạn có phát triển bảng này)
     */
    public function details()
    {
        return $this->hasMany(ImportReceiptDetail::class, 'receipt_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}