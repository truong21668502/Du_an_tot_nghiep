<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\FavoriteProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductFavoriteController extends Controller
{
    /**
     * Display a listing of the user's favorited products.
     * Optionally, this could render an Inertia page.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để xem danh sách yêu thích.',
            ], 401);
        }

        // Assuming a 'product' relationship exists on FavoriteProduct model
        $favoriteProducts = $user->favoriteProducts()->with('product')->get()->map(function ($favorite) {
            return $favorite->product;
        });

        // If you were to render an Inertia page, it might look like this:
        // return Inertia::render('Customer/Profile/FavoriteProducts', [
        //     'favoriteProducts' => $favoriteProducts,
        // ]);

        return response()->json([
            'success' => true,
            'data' => $favoriteProducts,
            'message' => 'Danh sách sản phẩm yêu thích đã được tải thành công.',
        ]);
    }
}
