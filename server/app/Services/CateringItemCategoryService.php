<?php

namespace App\Services;

use App\Models\CateringItemCategory;
use App\Models\CateringItem;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CateringItemCategoryService
{
    /**
     * إنشاء تصنيف جديد
     */
    public function createCategory(array $data): CateringItemCategory
    {
        DB::beginTransaction();
        try {
            // إذا لم يتم تحديد ترتيب، اضعه في النهاية
            if (!isset($data['sort_order'])) {
                $maxOrder = CateringItemCategory::max('sort_order') ?? 0;
                $data['sort_order'] = $maxOrder + 1;
            }

            $category = CateringItemCategory::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'sort_order' => $data['sort_order'],
                'is_active' => $data['is_active'] ?? true
            ]);

            DB::commit();

            Log::info('Catering item category created successfully', [
                'category_id' => $category->id,
                'name' => $category->name
            ]);

            return $category->load('cateringItems');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create catering item category', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * تحديث تصنيف موجود
     */
    public function updateCategory(int $id, array $data): CateringItemCategory
    {
        $category = CateringItemCategory::findOrFail($id);

        DB::beginTransaction();
        try {
            $category->update(array_filter($data, function($value) {
                return $value !== null && $value !== '';
            }));

            DB::commit();

            Log::info('Catering item category updated successfully', [
                'category_id' => $category->id,
                'changes' => $data
            ]);

            return $category->load('cateringItems');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update catering item category', [
                'category_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * حذف تصنيف
     */
    public function deleteCategory(int $id): bool
    {
        $category = CateringItemCategory::findOrFail($id);

        // تحقق من وجود آيتمز مرتبطة
        $itemsCount = $category->cateringItems()->count();
        if ($itemsCount > 0) {
            throw new \Exception("لا يمكن حذف التصنيف لأنه مرتبط بـ {$itemsCount} صنف كيترينج");
        }

        DB::beginTransaction();
        try {
            $categoryName = $category->name;
            $category->delete();

            DB::commit();

            Log::info('Catering item category deleted successfully', [
                'category_id' => $id,
                'name' => $categoryName
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete catering item category', [
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * تبديل حالة التفعيل
     */
    public function toggleStatus(int $id): CateringItemCategory
    {
        $category = CateringItemCategory::findOrFail($id);

        DB::beginTransaction();
        try {
            $category->update(['is_active' => !$category->is_active]);

            DB::commit();

            Log::info('Catering item category status toggled', [
                'category_id' => $id,
                'new_status' => $category->is_active,
                'name' => $category->name
            ]);

            return $category;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to toggle catering item category status', [
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * جلب تصنيفات البروفايدر مع الإحصائيات
     */
    public function getProviderCategories(int $providerId): \Illuminate\Database\Eloquent\Collection
    {
        return CateringItemCategory::active()
            ->withCount(['cateringItems' => function($query) use ($providerId) {
                $query->whereHas('service', function($serviceQuery) use ($providerId) {
                    $serviceQuery->where('user_id', $providerId);
                });
            }])
            ->get();
    }

    /**
     * إحصائيات البروفايدر
     */
    public function getProviderStats(int $providerId): array
    {
        $categories = $this->getProviderCategories($providerId);

        $totalItems = CateringItemCategory::where('is_active', true)
            ->join('catering_items', 'catering_item_categories.id', '=', 'catering_items.category_id')
            ->join('services', 'catering_items.service_id', '=', 'services.id')
            ->where('services.user_id', $providerId)
            ->count();

        return [
            'categories' => $categories,
            'total_items_count' => $totalItems,
            'active_categories_count' => $categories->count(),
            'categories_with_items' => $categories->filter(fn($cat) => $cat->catering_items_count > 0)->count()
        ];
    }

    /**
     * البحث في التصنيفات
     */
    public function searchCategories(string $searchTerm, int $providerId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CateringItemCategory::active()->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        });

        if ($providerId) {
            $query->withCount(['cateringItems' => function($query) use ($providerId) {
                $query->whereHas('service', function($serviceQuery) use ($providerId) {
                    $serviceQuery->where('user_id', $providerId);
                });
            }]);
        } else {
            $query->withCount('cateringItems');
        }

        return $query->get();
    }

    /**
     * إعادة ترتيب التصنيفات
     */
    public function reorderCategories(array $categoryIds): bool
    {
        DB::beginTransaction();
        try {
            foreach ($categoryIds as $index => $categoryId) {
                CateringItemCategory::where('id', $categoryId)
                    ->update(['sort_order' => $index + 1]);
            }

            DB::commit();

            Log::info('Catering item categories reordered', [
                'new_order' => $categoryIds
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder catering item categories', [
                'error' => $e->getMessage(),
                'category_ids' => $categoryIds
            ]);
            throw $e;
        }
    }

    /**
     * الحصول على التصنيفات الأساسية (للاستخدام الأولي)
     */
    public function getDefaultCategories(): array
    {
        return [
            [
                'name' => 'أطباق رئيسية',
                'description' => 'الأطباق الرئيسية والوجبات الأساسية',
                'icon' => '🍽️',
                'sort_order' => 1,
            ],
            [
                'name' => 'مشروبات',
                'description' => 'المشروبات الباردة والساخنة',
                'icon' => '🥤',
                'sort_order' => 2,
            ],
            [
                'name' => 'حلويات',
                'description' => 'أنواع الحلويات المختلفة',
                'icon' => '🍰',
                'sort_order' => 3,
            ],
            [
                'name' => 'سلطات',
                'description' => 'أنواع السلطات والمقبلات',
                'icon' => '🥗',
                'sort_order' => 4,
            ],
            [
                'name' => 'مقبلات',
                'description' => 'المقبلات والوجبات الخفيفة',
                'icon' => '🥙',
                'sort_order' => 5,
            ],
            [
                'name' => 'فواكه',
                'description' => 'السلات الفواكه الموسمية',
                'icon' => '🍇',
                'sort_order' => 6,
            ],
            [
                'name' => 'قسمات إضافية',
                'description' => 'الأصناف الإضافية والخاصة',
                'icon' => '⭐',
                'sort_order' => 7,
            ],
        ];
    }
}
