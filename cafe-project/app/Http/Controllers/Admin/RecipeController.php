<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\MaterialCostService;

class RecipeController extends Controller
{
    public function index()
    {
        $products = Product::query()
            ->select('id', 'product_name', 'image_url')
            ->with([
                'variants' => function ($query) {
                    $query->select('id', 'product_id', 'size', 'price')
                        ->withCount('recipes')
                        ->orderBy('size');
                }
            ])
            ->orderBy('product_name')
            ->get();

        $materials = Material::query()
            ->select('id', 'material_name', 'base_unit')
            ->orderBy('material_name')
            ->get();

        return Inertia::render('Admin/Recipes/Index', [
            'products' => $products,
            'materials' => $materials,
        ]);
    }

    public function show(Request $request, ProductVariant $variant)
    {
        $recipes = $variant->recipes()
            ->with('material:id,material_name,base_unit')
            ->get(['id', 'variant_id', 'material_id', 'quantity_needed']);

        if ($request->wantsJson()) {
            return response()->json(['recipes' => $recipes]);
        }

        return back();
    }

    public function sync(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required', 'integer', 'exists:materials,id'],
            'items.*.quantity_needed' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($variant, $validated) {
            $keepIds = [];

            foreach ($validated['items'] as $item) {
                $recipe = Recipe::updateOrCreate(
                    [
                        'variant_id' => $variant->id,
                        'material_id' => $item['material_id'],
                    ],
                    ['quantity_needed' => $item['quantity_needed']]
                );

                $keepIds[] = $recipe->id;
            }

            Recipe::where('variant_id', $variant->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        });

        $recipes = $variant->recipes()
            ->with('material:id,material_name,base_unit')
            ->get(['id', 'variant_id', 'material_id', 'quantity_needed']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã lưu công thức thành công.',
                'recipes' => $recipes,
            ]);
        }

        return back()->with('toast-success', 'Đã lưu công thức thành công.');
    }

    public function destroy(Request $request, Recipe $recipe)
    {
        $recipe->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa nguyên liệu khỏi công thức.']);
        }

        return back()->with('toast-success', 'Đã xóa nguyên liệu khỏi công thức.');
    }

    public function copy(Request $request, ProductVariant $fromVariant, ProductVariant $toVariant)
    {
        $validated = $request->validate([
            'scale' => ['nullable', 'numeric', 'min:0.1', 'max:10'], // hệ số nhân, ví dụ 1.2 cho size L
        ]);

        $scale = $validated['scale'] ?? 1;

        if ($fromVariant->id === $toVariant->id) {
            return response()->json(['message' => 'Không thể sao chép công thức vào chính nó.'], 422);
        }

        $sourceRecipes = $fromVariant->recipes()->get(['material_id', 'quantity_needed']);

        if ($sourceRecipes->isEmpty()) {
            return response()->json(['message' => 'Công thức nguồn đang trống, không có gì để sao chép.'], 422);
        }

        DB::transaction(function () use ($sourceRecipes, $toVariant, $scale) {
            // Ghi đè toàn bộ công thức đích bằng công thức nguồn (đã nhân hệ số)
            Recipe::where('variant_id', $toVariant->id)->delete();

            foreach ($sourceRecipes as $recipe) {
                Recipe::create([
                    'variant_id' => $toVariant->id,
                    'material_id' => $recipe->material_id,
                    'quantity_needed' => round($recipe->quantity_needed * $scale, 2),
                ]);
            }
        });

        $recipes = $toVariant->recipes()->with('material:id,material_name,base_unit')
            ->get(['id', 'variant_id', 'material_id', 'quantity_needed']);

        return response()->json([
            'message' => 'Đã sao chép công thức thành công.',
            'recipes' => $recipes,
        ]);
    }

    public function cost(ProductVariant $variant, MaterialCostService $materialCostService)
    {
        return response()->json(
            $materialCostService->calculateVariantCost($variant)
        );
    }
}