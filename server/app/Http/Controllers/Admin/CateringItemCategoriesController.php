<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CateringItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CateringItemCategoriesController extends Controller
{
    /**
     * عرض جميع تصنيفات الكيترينج آيتمز
     */
    public function index()
    {
        try {
            $categories = CateringItemCategory::withCount('cateringItems')
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'تم جلب التصنيفات بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching catering item categories', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب التصنيفات'
            ], 500);
        }
    }

    /**
     * إنشاء تصنيف جديد
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:catering_item_categories,name',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0'
            ]);

            $category = CateringItemCategory::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => true
            ]);

            Log::info('Catering item category created', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $category->load('cateringItems'),
                'message' => 'تم إنشاء التصنيف بنجاح'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات المرسلة غير صحيحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating catering item category', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إنشاء التصنيف'
            ], 500);
        }
    }

    /**
     * عرض تصنيف محدد
     */
    public function show(string $id)
    {
        try {
            $category = CateringItemCategory::with(['cateringItems' => function($query) {
                $query->withCount('catering');
            }])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'تم جلب التصنيف بنجاح'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching catering item category', [
                'error' => $e->getMessage(),
                'category_id' => $id,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب التصنيف'
            ], 500);
        }
    }

    /**
     * تحديث تصنيف موجود
     */
    public function update(Request $request, string $id)
    {
        try {
            $category = CateringItemCategory::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:catering_item_categories,name,' . $id,
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
                'sort_order' => 'sometimes|integer|min:0'
            ]);

            $category->update(array_filter($validated, function($value) {
                return $value !== null && $value !== '';
            }));

            Log::info('Catering item category updated', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'admin_id' => Auth::id(),
                'changes' => $validated
            ]);

            return response()->json([
                'success' => true,
                'data' => $category->load('cateringItems'),
                'message' => 'تم تحديث التصنيف بنجاح'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات المرسلة غير صحيحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating catering item category', [
                'error' => $e->getMessage(),
                'category_id' => $id,
                'admin_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث التصنيف'
            ], 500);
        }
    }

    /**
     * حذف تصنيف
     */
    public function destroy(string $id)
    {
        try {
            $category = CateringItemCategory::findOrFail($id);

            // تحقق من وجود آيتمز مرتبطة بالتصنيف
            $itemsCount = $category->cateringItems()->count();
            if ($itemsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "لا يمكن حذف التصنيف لأنه مرتبط بـ {$itemsCount} صنف كيترينج"
                ], 400);
            }

            $categoryName = $category->name;
            $category->delete();

            Log::info('Catering item category deleted', [
                'category_id' => $id,
                'category_name' => $categoryName,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التصنيف بنجاح'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting catering item category', [
                'error' => $e->getMessage(),
                'category_id' => $id,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف التصنيف'
            ], 500);
        }
    }

    /**
     * تفعيل/إلغاء تفعيل تصنيف
     */
    public function toggleStatus(string $id)
    {
        try {
            $category = CateringItemCategory::findOrFail($id);
            $category->update(['is_active' => !$category->is_active]);

            $status = $category->is_active ? 'مفعل' : 'غير مفعل';

            Log::info('Catering item category status toggled', [
                'category_id' => $id,
                'new_status' => $category->is_active,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => "تم تغيير حالة التصنيف إلى: {$status}"
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error toggling catering item category status', [
                'error' => $e->getMessage(),
                'category_id' => $id,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تغيير حالة التصنيف'
            ], 500);
        }
    }
}