<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CateringItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CateringItemCategoriesController extends Controller
{
    /**
     * جلب جميع التصنيفات النشطة للبروفايدرز
     */
    public function index()
    {
        try {
            $categories = CateringItemCategory::active()
                ->withCount(['cateringItems' => function($query) {
                    $query->whereHas('service', function($serviceQuery) {
                        $serviceQuery->where('user_id', auth()->id());
                    });
                }])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'تم جلب تصنيفات الكيترينج آيتمز بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching active catering item categories for provider', [
                'error' => $e->getMessage(),
                'provider_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب التصنيفات'
            ], 500);
        }
    }

    /**
     * جلب تصنيف محدد مع آيتمز البروفايدر فقط
     */
    public function show(string $id)
    {
        try {
            $category = CateringItemCategory::active()->findOrFail($id);
            
            // جلب آيتمز الكيترينج الخاصة بالبروفايدر الحالي فقط
            $category->provider_catering_items = $category->cateringItems()
                ->whereHas('service', function($query) {
                    $query->where('user_id', auth()->id());
                })
                ->with(['service:id,name,user_id', 'catering:id,catering_name'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'تم جلب التصنيف بنجاح'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود أو غير نشط'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching catering item category for provider', [
                'error' => $e->getMessage(),
                'category_id' => $id,
                'provider_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب التصنيف'
            ], 500);
        }
    }

    /**
     * البحث في التصنيفات (للمحتوى الإضافي)
     */
    public function search(Request $request)
    {
        try {
            $query = CateringItemCategory::active();

            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            $categories = $query->withCount(['cateringItems' => function($query) {
                $query->whereHas('service', function($serviceQuery) {
                    $serviceQuery->where('user_id', auth()->id());
                });
            }])
            ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'تم البحث في التصنيفات بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching catering item categories', [
                'error' => $e->getMessage(),
                'provider_id' => auth()->id(),
                'search_term' => $request->search ?? 'none'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في البحث'
            ], 500);
        }
    }

    /**
     * جلب التصنيفات مع عدد الآيتمز المرتبطة لكل بروفايدر
     */
    public function stats(Request $request)
    {
        try {
            $categories = CateringItemCategory::active()
                ->withCount(['cateringItems' => function($query) {
                    $query->whereHas('service', function($serviceQuery) {
                        $serviceQuery->where('user_id', auth()->id());
                    });
                }])
                ->having('catering_items_count', '>', 0) // فقط التصنيفات التي لها آيتمز
                ->get();

            $totalItems = CateringItemCategory::where('is_active', true)
                ->join('catering_items', 'catering_item_categories.id', '=', 'catering_items.category_id')
                ->join('services', 'catering_items.service_id', '=', 'services.id')
                ->where('services.user_id', auth()->id())
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'total_items_count' => $totalItems,
                    'active_categories_count' => $categories->count()
                ],
                'message' => 'تم جلب إحصائيات التصنيفات بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching catering categories stats', [
                'error' => $e->getMessage(),
                'provider_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الإحصائيات'
            ], 500);
        }
    }
}