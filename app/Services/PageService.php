<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;

class PageService
{
    /**
     * جلب الصفحات حسب نوع معين
     */
    public function getPagesByType(string $type, ?string $audience = null): Collection
    {
        $query = Page::where('page_type', $type)
                    ->where('is_published', true);

        if ($audience) {
            $query->where(function($q) use ($audience) {
                $q->where('target_audience', $audience)
                  ->orWhere('target_audience', 'all');
            });
        }

        return $query->orderBy('sort_order')->get();
    }

    /**
     * جلب صفحات الجمهور المستهدف
     */
    public function getPagesForAudience(string $audience): Collection
    {
        return Page::where('is_published', true)
                  ->where(function($q) use ($audience) {
                      $q->where('target_audience', $audience)
                        ->orWhere('target_audience', 'all');
                  })
                  ->orderBy('sort_order')
                  ->get();
    }

    /**
     * جلب صفحات مقدمي الخدمات
     */
    public function getProviderPages(): Collection
    {
        return $this->getPagesForAudience('providers');
    }

    /**
     * جلب صفحات العملاء
     */
    public function getCustomerPages(): Collection
    {
        return $this->getPagesForAudience('customers');
    }

    /**
     * جلب صفحات الأدمن
     */
    public function getAdminPages(): Collection
    {
        return $this->getPagesForAudience('admins');
    }

    /**
     * جلب صفحة حسب الـ slug
     */
    public function getPageBySlug(string $slug): ?Page
    {
        return Page::where('slug', $slug)
                  ->where('is_published', true)
                  ->first();
    }

    /**
     * فحص الصلاحيات لفحة معينة
     */
    public function canAccessPage(?Page $page, ?string $userType = null): bool
    {
        if (!$page || !$page->is_published) {
            return false;
        }

        switch ($page->target_audience) {
            case 'all':
                return true;
            case 'guest_users':
                return $userType === null; // المستخدمين غير المسجلين فقط
            case 'registered_users':
                return $userType !== null; // المستخدمين المسجلين فقط
            case 'providers':
                return $userType === 'provider';
            case 'customers':
                return $userType === 'customer';
            case 'admins':
                return $userType === 'admin';
            default:
                return false;
        }
    }

    /**
     * جلب الصفحات المناسبة للمستخدم الحالي
     */
    public function getAccessiblePages(?string $userType = null): Collection
    {
        $query = Page::where('is_published', true);

        if (!$userType) {
            // زائر غير مسجل - يمكنه رؤية الصفحات العامة أو المخصصة للزوار
            $query->where(function($q) {
                $q->where('target_audience', 'all')
                  ->orWhere('target_audience', 'guest_users');
            });
        } elseif ($userType === 'admin') {
            // الأدمن يمكنه رؤية كل شيء
            return $query->orderBy('sort_order')->get();
        } else {
            // مقدم خدمة أو عميل
            $query->where(function($q) use ($userType) {
                $q->where('target_audience', 'all')
                  ->orWhere('target_audience', 'registered_users')
                  ->orWhere('target_audience', $userType . 's'); // providers أو customers
            });
        }

        return $query->orderBy('sort_order')->get();
    }

    /**
     * البحث في صفحات
     */
    public function searchPages(string $query, ?string $userType = null, ?string $pageType = null): Collection
    {
        $builder = Page::where('is_published', true)
                      ->where(function($q) use ($query) {
                          $q->where('title', 'LIKE', "%{$query}%")
                            ->orWhere('content', 'LIKE', "%{$query}%");
                      });

        if ($pageType) {
            $builder->where('page_type', $pageType);
        }

        if ($userType) {
            $builder->where(function($q) use ($userType) {
                $q->where('target_audience', 'all')
                  ->orWhere('target_audience', 'registered_users')
                  ->orWhere('target_audience', $userType . 's');
            });
        }

        return $builder->orderBy('sort_order')->get();
    }
}
