<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Table; // Đảm bảo bạn đã có Model Table kết nối bảng 'tables'
use App\Http\Requests\Admin\TableStoreRequest;
use App\Http\Requests\Admin\TableUpdateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function store(TableStoreRequest $request)
    {
        Table::create($request->validated());
        return redirect()->back()->with('toast-success', 'Thêm bàn phục vụ mới thành công!');
    }

    public function update(TableUpdateRequest $request, $id)
    {
        $table = Table::findOrFail($id);
        $table->update($request->validated());
        return redirect()->back()->with('toast-success', 'Cập nhật thông tin bàn thành công!');
    }

    public function destroy($id)
    {
        $table = Table::findOrFail($id);
        $table->delete();
        return redirect()->back()->with('toast-success', 'Xóa bàn phục vụ thành công!');
    }
    public function print()
    {
        $tables = Table::orderBy('id')->get();

        return view('admin.tables.print', compact('tables'));
    }
}