<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Http\Requests\Admin\TableStoreRequest;
use App\Http\Requests\Admin\TableUpdateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class TableController extends Controller
{
    public function index(Request $request)
    {
        // 1. Lấy dữ liệu lọc từ URL query string
        $filters = $request->only(['search', 'area', 'capacity', 'status']);

        // 2. Query dữ liệu kèm bộ lọc nâng cao
        $query = Table::query();

        // Tìm kiếm theo tên bàn hoặc liên kết qr_code
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('table_name', 'like', '%' . $request->search . '%')
                    ->orWhere('qr_code', 'like', '%' . $request->search . '%');
            });
        }

        // Lọc theo khu vực (Khu vực gõ tay hoặc chọn)
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        // Lọc theo số lượng sức chứa (capacity)
        if ($request->filled('capacity')) {
            $query->where('capacity', $request->capacity);
        }

        // Lọc theo trạng thái bàn (EMPTY, OCCUPIED, RESERVED)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sắp xếp mặc định theo khu vực và tên bàn
        $tables = $query->orderBy('area')->orderBy('table_name')->paginate(10)->withQueryString();

        // Thu thập danh sách Khu vực độc nhất (Unique) đang có trong DB để làm dropdown lọc nhanh
        $distinctAreas = Table::whereNotNull('area')->distinct()->pluck('area');

        //Trả dữ liệu về giao diện Vue qua Inertia
        return Inertia::render('Admin/Tables/Index', [
            'tables'        => $tables,
            'filters'       => $filters,
            'distinctAreas' => $distinctAreas
        ]);
    }

    /**
     * Hàm tự tạo mã định danh QR từ tên bàn
     */
    private function generateQrCode(string $name, ?int $ignoreId = null): string
    {
        // Chuyển thành slug không dấu, thay gạch ngang bằng gạch dưới và in hoa
        // Ví dụ: "Bàn VIP 01" -> "BAN_VIP_01"
        $formatted = strtoupper(str_replace('-', '_', Str::slug($name)));
        $baseQr = 'QR_' . $formatted;
        $qrCode = $baseQr;
        $count = 1;

        // Chống trùng lặp định danh
        while (Table::where('qr_code', $qrCode)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $qrCode = "{$baseQr}_{$count}";
            $count++;
        }

        return $qrCode;
    }

    public function store(TableStoreRequest $request)
    {
        // Lấy dữ liệu đã được xác thực từ request
        $data = $request->validated();

        // Tự tạo định danh QR theo tên bàn
        $data['qr_code'] = $this->generateQrCode($data['table_name']);

        // Tạo bản ghi mới trong cơ sở dữ liệu
        Table::create($data);

        return redirect()->back()->with('toast-success', 'Thêm bàn phục vụ mới thành công!');
    }

    public function update(TableUpdateRequest $request, $id)
    {
        // Tìm bàn theo ID, nếu không tìm thấy sẽ ném lỗi 404
        $table = Table::findOrFail($id);

        // Lấy dữ liệu đã được xác thực từ request
        $data = $request->validated();

        // Tự tạo định danh QR nếu chưa có
        if (!isset($data['qr_code'])) {
            $data['qr_code'] = $this->generateQrCode($data['table_name'], $id);
        }

        // Cập nhật thông tin bàn
        $table->update($data);


        return redirect()->back()->with('toast-success', 'Cập nhật thông tin bàn thành công!');
    }

    public function destroy($id)
    {
        $table = Table::findOrFail($id);
        $table->delete();
        return redirect()->back()->with('toast-success', 'Đã thêm bàn vào thùng rác!');
    }
    public function print()
    {
        $tables = Table::orderBy('id')->get();

        return view('admin.tables.print', compact('tables'));
    }

    public function trash()
    {
        // Lấy danh sách các bàn đã bị xóa mềm (soft deleted)
        $trashedTables = Table::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(10);

        return Inertia::render('Admin/Tables/Trash', [
            'trashedTables' => $trashedTables,
        ]);
    }

    // Khôi phục bàn
    public function restore($id)
    {
        $table = Table::onlyTrashed()->findOrFail($id);
        $table->restore();

        return redirect()->back()->with('success', "Đã khôi phục '{$table->table_name}' thành công!");
    }
}