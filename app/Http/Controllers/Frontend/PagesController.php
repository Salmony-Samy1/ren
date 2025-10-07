<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     * جلب الصفحات المتاحة للمستخدم الحالي
     */
    public function index(Request $request): JsonResponse
    {
        $userType = auth()->check() ? auth()->user()->type : null;
        $pageType = $request->query('page_type');
        
        $pages = $pageType 
            ? $this->pageService->getPagesByType($pageType, $userType ? $userType . 's' : null)
            : $this->pageService->getAccessiblePages($userType);

        return response()->json([
            'data' => PageResource::collection($pages),
            'message' => 'تم جلب الصفحات بنجاح'
        ]);
    }

    /**
     * جلب صفحة واحدة حسب الـ slug
     */
    public function show(string $slug): JsonResponse
    {
        $page = $this->pageService->getPageBySlug($slug);
        $userType = auth()->check() ? auth()->user()->type : null;

        if (!$page) {
            return response()->json(['message' => 'الصفحة غير موجودة'], 404);
        }

        if (!$this->pageService->canAccessPage($page, $userType)) {
            return response()->json(['message' => 'ليس لديك صلاحية للوصول لهذه الصفحة'], 403);
        }

        return response()->json([
            'data' => new PageResource($page),
            'message' => 'تم جلب الصفحة بنجاح'
        ]);
    }

    /**
     * البحث في الصفحات
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q');
        $userType = auth()->check() ? auth()->user()->type : null;
        $pageType = $request->query('page_type');

        if (!$query) {
            return response()->json(['message' => 'يرجى إدخال كلمة البحث'], 400);
        }

        $pages = $this->pageService->searchPages($query, $userType, $pageType);

        return response()->json([
            'data' => PageResource::collection($pages),
            'message' => 'نتائج البحث',
            'search_query' => $query,
            'results_count' => $pages->count()
        ]);
    }

    /**
     * جلب الصفحات حسب الجمهور المستهدف
     */
    public function byAudience(string $audience): JsonResponse
    {
        $userType = auth()->check() ? auth()->user()->type : null;

        // التحقق من الصلاحيات لطلب هذه الفئة من المستخدمين
        if ($audience === 'admins' && $userType !== 'admin') {
            return response()->json(['message' => 'ليس لديك صلاحية للوصول لهذه الصفحات'], 403);
        }

        $pages = $this->pageService->getPagesForAudience($audience);

        return response()->json([
            'data' => PageResource::collection($pages),
            'message' => "صفحات الجمهور: {$audience}"
        ]);
    }

    /**
     * جلب أنواع الصفحات المتاحة
     */
    public function getAvailableTypes(): JsonResponse
    {
        $types = [
            'general' => 'عام - محتوى مفتوح للجميع',
            'legal' => 'قانوني - سياسات وشروط',
            'provider_app' => 'تطبيق مقدم الخدمة',
            'customer_app' => 'تطبيق العميل',
            'provider_info' => 'معلومات مقدم الخدمة',
            'customer_info' => 'معلومات العميل',
            'provider_frontend' => 'فرونت اند لمقدم الخدمة',
            'customer_frontend' => 'فرونت اند للعميل',
            'public_frontend' => 'فرونت اند عام للجميع',
            'admin_panel' => 'لوحة تحكم الأدمن',
            'help_support' => 'مساعدة ودعم',
            'announcements' => 'إعلانات وإشعارات',
            'faq' => 'أسئلة شائعة',
            'terms_conditions' => 'شروط الاستخدام',
            'privacy_policy' => 'سياسة الخصوصية',
            'about_us' => 'عنا',
            'contact_us' => 'اتصل بنا'
        ];

        return response()->json([
            'data' => $types,
            'message' => 'أنواع الصفحات المتاحة'
        ]);
    }

    /**
     * جلب الجماهير المستهدفة المتاحة
     */
    public function getAvailableAudiences(): JsonResponse
    {
        $audiences = [
            'all' => 'للجميع',
            'providers' => 'مقدمي الخدمات فقط',
            'customers' => 'العملاء فقط',
            'admins' => 'الأدمن فقط',
            'guest_users' => 'المستخدمين غير المسجلين',
            'registered_users' => 'المستخدمين المسجلين فقط'
        ];

        return response()->json([
            'data' => $audiences,
            'message' => 'الجماهير المستهدفة المتاحة'
        ]);
    }
}
