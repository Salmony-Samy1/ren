<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CommissionService;
use App\Services\AdvancedCommissionService;
use App\Models\CommissionRule;
use App\Models\FeeStructure;
use App\Models\ReferralTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommissionController extends Controller
{
    public function __construct(private readonly CommissionService $commissionService)
    {
    }

    // ===== TASK 4: Advanced Commission Management =====

    /**
     * عرض قواعد العمولات النشطة (Commission Rules) - Task 4
     */
    public function commissionRules(Request $request)
    {
        $rules = CommissionRule::with([])
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->has('rule_type'), function ($query) use ($request) {
                $query->where('rule_type', $request->rule_type);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('commission_value', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قواعد العمولات بنجاح',
            'data' => $rules->items(),
            'meta' => [
                'current_page' => $rules->currentPage(),
                'total' => $rules->total(),
                'per_page' => $rules->perPage(),
                'last_page' => $rules->lastPage()
            ]
        ]);
    }

    /**
     * إنشاء قاعدة عمولة جديدة (Task 4)
     */
    public function createCommissionRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|string|max:255',
            'rule_type' => 'required|in:service_type,volume_based,rating_based,referral_based',
            'commission_type' => 'required|in:percentage,fixed_amount',
            'commission_value' => 'required|numeric|min:0',
            'rule_parameters' => 'nullable|array',
            'min_commission' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'priority' => 'required|integer|min:0',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $rule = CommissionRule::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء قاعدة العمولة بنجاح',
                'data' => $rule
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء قاعدة العمولة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث قاعدة عمولة موجودة (Task 4)
     */
    public function updateCommissionRule(Request $request, int $id)
    {
        $rule = CommissionRule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'rule_name' => 'sometimes|string|max:255',
            'rule_type' => 'sometimes|in:service_type,volume_based,rating_based,referral_based',
            'commission_type' => 'sometimes|in:percentage,fixed_amount',
            'commission_value' => 'sometimes|numeric|min:0',
            'rule_parameters' => 'sometimes|array',
            'min_commission' => 'sometimes|numeric|min:0',
            'max_commission' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'priority' => 'sometimes|integer|min:0',
            'effective_from' => 'sometimes|nullable|date',
            'effective_until' => 'sometimes|nullable|date|after:effective_from',
            'description' => 'sometimes|nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $rule->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث قاعدة العمولة بنجاح',
                'data' => $rule->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث قاعدة العمولة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف قاعدة عمولة (Task 4)
     */
    public function deleteCommissionRule(int $id)
    {
        try {
            $rule = CommissionRule::findOrFail($id);
            $rule->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف قاعدة العمولة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف قاعدة العمولة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض هيكل الرسوم (Fee Structure) - Task 4
     */
    public function feeStructures(Request $request)
    {
        $fees = FeeStructure::query()
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->has('fee_type'), function ($query) use ($request) {
                $query->where('fee_type', $request->fee_type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب هيكل الرسوم بنجاح',
            'data' => $fees->items(),
            'meta' => [
                'current_page' => $fees->currentPage(),
                'total' => $fees->total(),
                'per_page' => $fees->perPage(),
                'last_page' => $fees->lastPage()
            ]
        ]);
    }

    /**
     * إنشاء رسوم جديدة (Task 4)
     */
    public function createFeeStructure(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fee_name' => 'required|string|max:255',
            'fee_type' => 'required|in:booking,service,processing,penalty,referral',
            'account_type' => 'required|in:percentage,fixed_amount',
            'amount' => 'required|numeric|min:0',
            'applicable_services' => 'nullable|array',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fee = FeeStructure::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الرسوم بنجاح',
                'data' => $fee
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الرسوم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * معاملات العمولات الإحالية (Referral Transactions) - Task 4
     */
    public function referralOperations(Request $request)
    {
        $operations = ReferralTransaction::with(['referrer:id,name', 'referredUser:id,name', 'booking:id'])
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->has('commission_type'), function ($query) use ($request) {
                $query->where('commission_type', $request->commission_type);
            })
            ->when($request->has('referrer_id'), function ($query) use ($request) {
                $query->where('referrer_id', $request->referrer_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب معاملات العمولات الإحالية بنجاح',
            'data' => $operations->items(),
            'meta' => [
                'current_page' => $operations->currentPage(),
                'total' => $operations->total(),
                'per_page' => $operations->perPage(),
                'last_page' => $operations->lastPage()
            ]
        ]);
    }

    /**
     * تقرير العمولات حسب الخدمة - Task 4
     */
    public function commissionReportByService(Request $request)
    {
        $advancedService = app(AdvancedCommissionService::class);

        $report = \App\Models\Booking::with(['service', 'user'])
            ->whereStatus('completed')
            ->when($request->has('start_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->start_date);
            })
            ->when($request->has('end_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->end_date);
            })
            ->get()
            ->groupBy(function ($booking) {
                return $this->getServiceTypeForReport($booking->service);
            })
            ->map(function ($bookings, $serviceType) use ($advancedService) {
                $totalTransactions = $bookings->count();
                $totalRevenue = $bookings->sum('subtotal');
                $totalCommissions = 0;
                $totalFees = 0;

                foreach ($bookings as $booking) {
                    $commissionData = $advancedService->calculateAdvancedCommission($booking);
                    $totalCommissions += $commissionData['total_commission'];
                    $totalFees += $commissionData['additional_fees'];
                }

                $netProfit = $totalRevenue - $totalCommissions - $totalFees;
                $averageTransactionValue = $totalRevenue / $totalTransactions;
                $commissionRate = $totalRevenue > 0 ? ($totalCommissions / $totalRevenue) * 100 : 0;

                return [
                    'service_type' => $serviceType,
                    'transactions_count' => $totalTransactions,
                    'total_revenue' => $totalRevenue,
                    'total_commissions' => $totalCommissions,
                    'total_fees' => $totalFees,
                    'net_profit' => $netProfit,
                    'average_transaction_value' => $averageTransactionValue,
                    'commission_rate' => $commissionRate
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب تقرير العمولات حسب الخدمة بنجاح',
            'data'            => $report->values()->toArray()
        ]);
    }

    /**
     * إعدادات النظام للإحالة المتقدمة (Task 4)
     */
    public function referralConfiguration(Request $request)
    {
        if ($request->isMethod('GET')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'referral_commission_type' => get_setting('referral_commission_type', 'one_time'),
                    'referral_commission_rate' => (float) get_setting('referral_commission_rate', 0),
                    'referral_points' => (int) get_setting('referral_points', 100),
                    'referral_auto_approve' => (bool) get_setting('referral_auto_approve', false)
                ]
            ]);
        }

        // حفظ الإعدادات
        $validator = Validator::make($request->all(), [
            'referral_commission_type' => 'required|in:one_time,recurring',
            'referral_commission_rate' => 'required|numeric|min:0|max:100',
            'referral_points' => 'required|integer|min:0',
            'referral_auto_approve' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            foreach ($request->all() as $key => $value) {
                set_setting($key, $value);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ إعدادات الإحالة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الإعدادات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دالة مساعدة للحصول على نوع الخدمة للتقرير
     */
    private function getServiceTypeForReport($service): string
    {
        if ($service->event) return 'فعاليات';
        if ($service->catering) return 'كيترينق';
        if ($service->restaurant) return 'مطاعم';
        if ($service->property) return 'شقق';
        
        return 'خدمات أخرى';
    }

    /**
     * عرض إعدادات العمولة
     */
    public function settings()
    {
        $settings = $this->commissionService->getCommissionSettings();
        
        return format_response(true, 'تم جلب إعدادات العمولة بنجاح', $settings);
    }

    /**
     * تحديث إعدادات العمولة
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commission_type' => 'required|in:percentage,fixed',
            'commission_amount' => 'required|numeric|min:0',
            'event_commission_rate' => 'required|numeric|min:0|max:100',
            'catering_commission_rate' => 'required|numeric|min:0|max:100',
            'restaurant_commission_rate' => 'required|numeric|min:0|max:100',
            'property_commission_rate' => 'required|numeric|min:0|max:100',
            'volume_1000_rate' => 'required|numeric|min:0|max:100',
            'volume_5000_rate' => 'required|numeric|min:0|max:100',
            'volume_10000_rate' => 'required|numeric|min:0|max:100',
            'rating_4_rate' => 'required|numeric|min:0|max:100',
            'rating_4_5_rate' => 'required|numeric|min:0|max:100',
            'rating_5_rate' => 'required|numeric|min:0|max:100',
            'min_commission' => 'required|numeric|min:0',
            'max_commission' => 'required|numeric|min:0|max:100',
            'max_commission_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return format_response(false, 'بيانات غير صحيحة', $validator->errors(), 422);
        }

        $success = $this->commissionService->updateCommissionSettings($request->all());
        
        if ($success) {
            return format_response(true, 'تم تحديث إعدادات العمولة بنجاح');
        }

        return format_response(false, 'حدث خطأ أثناء تحديث الإعدادات', code: 500);
    }

    /**
     * عرض إحصائيات العمولة
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', 'month');
        $stats = $this->commissionService->getCommissionStats($period);
        
        return format_response(true, 'تم جلب إحصائيات العمولة بنجاح', $stats);
    }

    /**
     * عرض تفاصيل العمولة لحجز محدد
     */
    public function calculateForBooking($bookingId)
    {
        $booking = \App\Models\Booking::with(['service', 'service.user'])->findOrFail($bookingId);
        
        $commissionData = $this->commissionService->calculateCommission($booking);
        
        return format_response(true, 'تم حساب العمولة بنجاح', [
            'booking' => $booking,
            'commission' => $commissionData
        ]);
    }

    /**
     * معالجة العمولة لحجز محدد
     */
    public function processForBooking($bookingId)
    {
        $booking = \App\Models\Booking::with(['service', 'service.user'])->findOrFail($bookingId);
        
        if ($booking->status !== 'completed') {
            return format_response(false, 'لا يمكن معالجة العمولة إلا للحجوزات المكتملة', code: 400);
        }
        
        $success = $this->commissionService->processCommission($booking);
        
        if ($success) {
            return format_response(true, 'تم معالجة العمولة بنجاح');
        }

        return format_response(false, 'حدث خطأ أثناء معالجة العمولة', code: 500);
    }

    /**
     * عرض تقرير العمولة
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'provider_id' => 'nullable|exists:users,id',
            'service_type' => 'nullable|in:event,catering,restaurant,property,other',
        ]);

        if ($validator->fails()) {
            return format_response(false, __('validation.invalid_data'), $validator->errors(), 422);
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $providerId = $request->provider_id;
        $serviceType = $request->service_type;

        $query = \App\Models\Invoice::with(['booking.service.user', 'booking.user'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($providerId) {
            $query->whereHas('booking.service', function ($q) use ($providerId) {
                $q->where('user_id', $providerId);
            });
        }

        if ($serviceType) {
            $query->whereHas('booking.service', function ($q) use ($serviceType) {
                switch ($serviceType) {
                    case 'event':
                        $q->whereHas('event');
                        break;
                    case 'catering':
                        $q->whereHas('cateringItem');
                        break;
                    case 'restaurant':
                        $q->whereHas('restaurant');
                        break;
                    case 'property':
                        $q->whereHas('property');
                        break;
                }
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);

        $summary = [
            'total_commission' => $query->sum('commission_amount'),
            'total_provider_amount' => $query->sum('provider_amount'),
            'total_platform_amount' => $query->sum('platform_amount'),
            'total_invoices' => $query->count(),
        ];

        return format_response(true, 'تم جلب تقرير العمولة بنجاح', [
            'invoices' => $invoices,
            'summary' => $summary
        ]);
    }
}
