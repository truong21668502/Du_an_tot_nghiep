<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Material;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::orderBy('id')->get();

        return Inertia::render('Admin/Warehouse/Index', [
            'materials' => $materials,
        ]);
    }
}
