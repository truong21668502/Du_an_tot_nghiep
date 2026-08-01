<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role', 'status', 'gender', 'tab']);
        $query = User::query();

        $tab = $request->input('tab', 'all'); // Mặc định là xem danh sách bình thường

        // Khởi tạo truy vấn
        if ($tab === 'trash') {
            // Nếu bấm sang tab Thùng rác, CHỈ lấy những người đã bị xóa mềm
            $query = User::onlyTrashed()->with('addresses'); // Lấy cả địa chỉ của người dùng trong thùng rác
        } else {
            // Ngược lại lấy danh sách người dùng bình thường (ẩn người trong thùng rác)
            $query = User::query()->with('addresses');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) { $query->where('role', $request->role); }
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('gender')) { $query->where('gender', $request->gender); }

        $users = $query->orderByRaw("CASE 
                    WHEN role = 'ADMIN' THEN 1 
                    WHEN role = 'BARISTA' THEN 2 
                    WHEN role = 'STAFF' THEN 3 
                    WHEN role = 'CUSTOMER' THEN 4 
                    ELSE 5 
                END ASC")
                ->orderBy('id', 'desc')
                ->paginate(10)
                ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => $filters,
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['status'] = 'active'; // Mặc định hoạt động theo Migration
        $data['reward_points'] = 0; // Mặc định 0 điểm

        User::create($data);
        return redirect()->back()->with('toast-success', 'Đã cấp tài khoản và thêm nhân sự mới thành công!');
    }

    public function update(UserUpdateRequest $request, $id)
    {
        $user = User::findOrFail($id);
        
        if (Auth::id() == $user->id && ($request->status !== 'active' || $request->role !== 'ADMIN')) {
            return redirect()->back()->with('toast-error', 'Bạn không được phép tự khóa hoặc hạ quyền của chính mình!');
        }

        $data = $request->validated();
        
        // Xử lý bảo mật: Nếu không nhập mật khẩu mới thì loại bỏ khỏi mảng update, không ghi đè rỗng
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return redirect()->back()->with('toast-success', 'Cập nhật thông tin tài khoản thành công!');
    }

    // Hàm Xóa Mềm
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (Auth::id() == $user->id) {
            return redirect()->back()->with('toast-error', 'Bạn không thể tự xóa chính mình!');
        }

        $user->delete(); // Lệnh này tự động cập nhật deleted_at, không mất dữ liệu

        return redirect()->back()->with('toast-success', 'Đã chuyển tài khoản vào trạng thái lưu trữ (Xóa mềm)!');
    }

    // Hàm Khôi Phục 
    public function restore($id)
    {
        // Tìm tài khoản nằm trong danh sách đã xóa mềm
        $user = User::onlyTrashed()->findOrFail($id);
        
        $user->restore(); // Khôi phục lại trạng thái hoạt động bình thường

        return redirect()->back()->with('toast-success', 'Đã khôi phục tài khoản thành công! Toàn bộ điểm tích lũy được giữ nguyên.');
    }

    // Hàm Xóa Vĩnh Viễn (test hệ thống hoặc dọn dẹp)
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->forceDelete();
        return redirect()->back()->with('toast-success', 'Đã xóa vĩnh viễn tài khoản khỏi cơ sở dữ liệu.');
    }

    // Hàm Cập Nhật Địa Chỉ Người Dùng
    public function updateAddress(Request $request, $id)
    {
        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'receiver_name' => 'required|string|max:100',
            'receiver_phone' => 'required|string|max:15',
            'address_detail' => 'required|string|max:255',
            'ward' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ], [
            'receiver_name.required' => 'Tên người nhận không được để trống.',
            'receiver_phone.required' => 'Số điện thoại không được để trống.',
            'address_detail.required' => 'Chi tiết địa chỉ không được để trống.',
        ]);

        // Tìm và cập nhật địa chỉ
        $address = UserAddress::findOrFail($id);
        $address->update($validated);

        return redirect()->back()->with('toast-success', 'Cập nhật địa chỉ thành công!');
    }
}