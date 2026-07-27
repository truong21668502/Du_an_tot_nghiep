<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BannerController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Banners/Index', [
            'banners' => Banner::orderBy('sort_order')->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Banners/Create');
    }

    public function edit(Banner $banner)
    {
        return Inertia::render('Admin/Banners/Edit', [
            'banner' => $banner,
        ]);
    }

    public function store(StoreBannerRequest $request)
    {
        $data = $request->safe()->except('image');
        $cloudinary = new \Cloudinary\Cloudinary();

        $result = $cloudinary->uploadApi()->upload($request->file('image')->getRealPath(), [
            'folder'         => 'uploads_du_an/Banner',
            'transformation' => ['quality' => 'auto', 'fetch_format' => 'auto'],
        ]);

        Banner::create([
            ...$data,
            'image_url'            => $result['secure_url'],
            'cloudinary_public_id' => $result['public_id'],
            'sort_order'           => Banner::max('sort_order') + 1,
            'is_active'            => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
        ]);

        return redirect()->route('admin.banners.index')->with('toast-success', 'Đã tạo banner mới');
    }

    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $data = $request->safe()->except('image');
        $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('image')) {
            $cloudinary = new \Cloudinary\Cloudinary();

            // Xóa ảnh cũ trên Cloudinary
            if ($banner->cloudinary_public_id) {
                $cloudinary->uploadApi()->destroy($banner->cloudinary_public_id);
            }

            $result = $cloudinary->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder'         => 'uploads_du_an/Banner',
                'transformation' => ['quality' => 'auto', 'fetch_format' => 'auto'],
            ]);

            $data['image_url']            = $result['secure_url'];
            $data['cloudinary_public_id'] = $result['public_id'];
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('toast-success', 'Đã cập nhật banner');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->cloudinary_public_id) {
            $cloudinary = new \Cloudinary\Cloudinary();
            $cloudinary->uploadApi()->destroy($banner->cloudinary_public_id);
        }

        $banner->delete();

        return back()->with('toast-success', 'Đã xóa banner');
    }

    // Drag-drop reorder — trả JSON không phải Inertia
    public function reorder(Request $request)
    {
        $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer', 'exists:banners,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->items as $item) {
            Banner::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->noContent();
    }

    // Toggle is_active inline — trả JSON
    public function toggleActive(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);

        return response()->json(['is_active' => $banner->is_active]);
    }
}