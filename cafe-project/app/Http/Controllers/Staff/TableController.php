<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Events\TableStatusUpdated;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function updateStatus(Request $request, Table $table)
    {
        // Validate dữ liệu truyền lên
        $validated = $request->validate([
            'status' => 'required|in:EMPTY,OCCUPIED,RESERVED'
        ]);

        // Cập nhật Database
        $table->update(['status' => $validated['status']]);

        // Phát sóng Real-time cho toàn bộ hệ thống (cả nhân viên khác và khách hàng)
        broadcast(new TableStatusUpdated($table))->toOthers();

        return redirect()->back();
    }
}