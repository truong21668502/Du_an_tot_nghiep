<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    // Định nghĩa tên bảng thực tế trong Database
    protected $table = 'materials';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'material_name',
        'base_unit',
        'input_unit',
        'exchange_rate',
        'quantity_in_stock',
        'min_stock',
        'max_stock',
        'supplier',
        'price',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ cơ sở dữ liệu ra Eloquent Model.
     */
    protected $casts = [
        'quantity_in_stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * Mối quan hệ: Một nguyên liệu có thể nằm trong nhiều Công thức pha chế (Recipes) của các món khác nhau
     * Liên kết thông qua khóa ngoại 'material_id' ở bảng recipes
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'material_id', 'id');
    }

    public function importReceiptDetails()
    {
        return $this->hasMany(ImportReceiptDetail::class, 'material_id');
    }

    public function latestImportDetail()
    {
        return $this->hasOne(ImportReceiptDetail::class, 'material_id')
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipts.status', 'active')   // bỏ qua phiếu đã huỷ
            ->orderByDesc('import_receipt_details.created_at')
            ->select([
                'import_receipt_details.*',
                'import_receipts.supplier_name',
            ]);
    }
    public function nearestExpiryDetail()
    {
        return $this->hasOne(ImportReceiptDetail::class, 'material_id')
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipts.status', 'active')
            ->where('import_receipt_details.remaining_quantity', '>', 0)
            ->whereNotNull('import_receipt_details.expiry_date')
            ->where('import_receipt_details.expiry_date', '>=', now()->toDateString())
            ->orderBy('import_receipt_details.expiry_date')
            ->select('import_receipt_details.*');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'material_id', 'id');
    }

    /**
     * Lô có hạn sử dụng gần "hôm nay" nhất — dùng cho màn điều chỉnh kiểm kê.
     * Khác với nearestExpiryDetail: KHÔNG loại trừ lô đã hết hạn,
     * vì lý do "Hết hạn" chính là để sửa lại lô đã quá hạn.
     */
    public function adjustableExpiryDetail()
    {
        return $this->hasOne(ImportReceiptDetail::class, 'material_id')
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipts.status', 'active')
            ->orderByDesc('import_receipt_details.created_at')
            ->select('import_receipt_details.*');
    }

    /**
     * Lô cũ nhất còn tồn thực tế — đại diện cho "hàng đang được dùng hiện tại" theo FIFO.
     * Dùng để hiển thị nhà cung cấp đúng với lô đang tiêu thụ, khác latestImportDetail
     * (vốn chỉ cho biết lần nhập gần nhất, bất kể còn hàng hay không).
     */
    public function oldestActiveDetail()
    {
        return $this->hasOne(ImportReceiptDetail::class, 'material_id')
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipts.status', 'active')
            ->where('import_receipt_details.remaining_quantity', '>', 0)
            ->orderBy('import_receipt_details.created_at') // cũ nhất trước — đúng thứ tự FIFO
            ->select([
                'import_receipt_details.*',
                'import_receipts.supplier_name',
            ]);
    }
}