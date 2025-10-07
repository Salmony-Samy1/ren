<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantMenuCategoryController extends Controller
{
    /**
     * Display a listing of all active menu categories.
     * This endpoint is PUBLIC.
     */
    public function index()
    {
        try {
            // استخدام DB facade مباشرة لتجنب مشاكل Model loading
            $categories = DB::table('restaurant_menu_categories')
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created menu category.
     * This endpoint is for ADMINS ONLY.
     */
    public function store(RestaurantMenuCategoryRequest $request)
    {
        $category = RestaurantMenuCategory::create($request->validated());
        return new RestaurantMenuCategoryResource($category);
    }

    /**
     * Display the specified menu category.
     * (Optional - can be added if needed)
     */
    public function show(RestaurantMenuCategory $category)
    {
        return new RestaurantMenuCategoryResource($category);
    }

    /**
     * Update the specified menu category.
     * This endpoint is for ADMINS ONLY.
     */
    public function update(RestaurantMenuCategoryRequest $request, RestaurantMenuCategory $category)
    {
        $category->update($request->validated());
        return new RestaurantMenuCategoryResource($category);
    }

    /**
     * Remove the specified menu category.
     * This endpoint is for ADMINS ONLY.
     */
    public function destroy(RestaurantMenuCategory $category)
    {
        // Authorize the action using the Form Request's logic
        if (! (new RestaurantMenuCategoryRequest())->authorize()) {
            abort(403, 'Unauthorized action.');
        }

        $category->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted successfully.'], 200);
    }
}
