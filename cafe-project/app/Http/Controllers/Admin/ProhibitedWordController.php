<?php
// app/Http/Controllers/Admin/ProhibitedWordController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProhibitedWordRequest;
use App\Models\ProhibitedWord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProhibitedWordController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', ''); // 'active', 'inactive', or ''
        
        $prohibitedWords = ProhibitedWord::query()
            ->when($search, function ($query, $search) {
                $query->search($search);
            })
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($word) {
                return [
                    'id' => $word->id,
                    'word' => $word->word,
                    'is_active' => $word->is_active,
                    'created_at' => $word->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $word->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Admin/ProhibitedWords/Index', [
            'prohibitedWords' => $prohibitedWords,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'total' => ProhibitedWord::count(),
                'active' => ProhibitedWord::where('is_active', true)->count(),
                'inactive' => ProhibitedWord::where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(ProhibitedWordRequest $request)
    {
        ProhibitedWord::create($request->validated());

        return redirect()->back()->with('message', 'Thêm từ khóa vi phạm thành công!');
    }

    public function update(ProhibitedWordRequest $request, ProhibitedWord $prohibitedWord)
    {
        $prohibitedWord->update($request->validated());

        return redirect()->back()->with('message', 'Cập nhật từ khóa vi phạm thành công!');
    }

    public function destroy(ProhibitedWord $prohibitedWord)
    {
        $prohibitedWord->delete();

        return redirect()->back()->with('message', 'Xóa từ khóa vi phạm thành công!');
    }

    // API toggle active status
    public function toggleActive(ProhibitedWord $prohibitedWord)
    {
        $prohibitedWord->update([
            'is_active' => !$prohibitedWord->is_active
        ]);

        // Trả về stats mới để cập nhật giao diện
        $newStats = [
            'total' => ProhibitedWord::count(),
            'active' => ProhibitedWord::where('is_active', true)->count(),
            'inactive' => ProhibitedWord::where('is_active', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => $prohibitedWord->is_active ? 'Đã kích hoạt từ khóa.' : 'Đã vô hiệu hóa từ khóa.',
            'data' => [
                'id' => $prohibitedWord->id,
                'is_active' => $prohibitedWord->is_active,
            ],
            'stats' => $newStats
        ]);
    }
}