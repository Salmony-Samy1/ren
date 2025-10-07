<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\AdminAuthTrait;
use App\Models\Booking; // أو Order حسب البنية الحالية
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    use AdminAuthTrait;

    public function __construct()
    {
        // حماية إضافية لضمان أن المدير فقط يمكنه الوصول
        $this->middleware(['auth:api', 'user_type:admin', 'throttle:admin']);
    }

    /**
     * جلب قائمة الطلبات مع فلترة وتقسيم الصفحات
     * GET /api/v1/admin/orders
     */
    public function index(Request $request)
    {
        if (!$this->validateAdminAccess()) {
            return $this->forbiddenResponse('عرض قائمة الطلبات');
        }

        try {
            $query = Booking::query()
                ->with([
                    'user:id,full_name,email,phone',
                    'service:id,name',
                    'service.user:id,full_name',
                    'service.category:id,name'
                ])
                ->where('status', 'confirmed');

            // فلترة حسب الفعالية - عبر خدمة تحتوي على event
            if ($request->filled('event_id')) {
                $query->whereHas('service.event', function ($q) use ($request) {
                    $q->where('events.id', $request->event_id);
                });
            }

            // فلترة حسب حالة الطلب
            if ($request->filled('status')) {
                $validStatuses = ['confirmed', 'pending', 'cancelled'];
                if (in_array($request->status, $validStatuses)) {
                    $query->where('status', $request->status);
                }
            }

            // البحث في بريد العميل
            if ($request->filled('customer_email')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->customer_email . '%');
                });
            }

            // فلترة حسب التاريخ
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // الترتيب
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // تقسيم الصفحات
            $orders = $query->paginate($request->input('limit', 20));

            // إحصائيات ملخص
            $summary = $this->getOrdersSummary($request);

            // تنسيق البيانات للإرسال
            $ordersData = $orders->map(function ($order) {
                return [
                    'order_id' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'event_title' => $order->service->name ?? 'N/A',
                    'event_id' => $order->service_id ?? null,
                    'customer_name' => $order->user->full_name ?? 'N/A',
                    'customer_email' => $order->user->email ?? 'N/A',
                    'customer_phone' => $order->user->phone ?? 'N/A',
                    'ticket_count' => 1, // Booking يحسب كتذكرة واحدة
                    'total_price' => floatval($order->total ?? 0),
                    'payment_method' => $order->payment_method ?? 'unknown',
                    'status' => $order->status,
                    'order_date' => $order->created_at->toISOString(),
                    'provider_name' => $order->service->user->full_name ?? 'System Admin'
                ];
            });

            return format_response(true, 'تم جلب الطلبات بنجاح', [
                'orders' => $ordersData,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'has_next' => $orders->hasMorePages()
                ],
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            return $this->handleAdminException($e, 'جلب قائمة الطلبات', [
                'request_params' => $request->all()
            ]);
        }
    }

    /**
     * الحصول على إحصائيات الطلبات
     */
    private function getOrdersSummary(Request $request): array
    {
        $query = Booking::query();

        // تطبيق نفس الفلاتر
        if ($request->filled('event_id')) {
            $query->whereHas('service.event', function ($q) use ($request) {
                $q->where('events.id', $request->event_id);
            });
        }

        $totalOrders = $query->count();
        $totalRevenue = $query->sum('total');
        
        return [
            'total_orders' => $totalOrders,
            'total_revenue' => floatval($totalRevenue),
            'confirmed_orders' => $query->clone()->where('status', 'confirmed')->count(),
            'pending_orders' => $query->clone()->where('status', 'pending')->count(),
            'cancelled_orders' => $query->clone()->where('status', 'cancelled')->count()
        ];
    }
}
