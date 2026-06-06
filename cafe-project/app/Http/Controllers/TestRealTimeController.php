<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\TestRealTime;
use Illuminate\Http\Request;

class TestRealTimeController extends Controller
{
    public function index()
    {
        return Inertia::render('TestRealTime/Index');
    }

    public function list()
    {
        return TestRealTime::latest()->get();
    }

    public function store(Request $request)
    {
        return TestRealTime::create(
            $request->validate([
                'content' => ['required']
            ])
        );
    }

    public function update(
        Request $request,
        TestRealTime $testRealTime
    ) {
        $testRealTime->update(
            $request->validate([
                'content' => ['required']
            ])
        );

        return response()->json([
            'success' => true
        ]);
    }

    public function destroy(
        TestRealTime $testRealTime
    ) {
        $testRealTime->delete();

        return response()->json([
            'success' => true
        ]);
    }
}