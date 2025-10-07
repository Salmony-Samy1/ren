<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    protected $financialReportService;

    public function __construct(FinancialReportService $financialReportService)
    {
        $this->financialReportService = $financialReportService;

        // Enforce RBAC: must be admin and have 'reports.view' permission
        $this->middleware(function ($request, $next) {
            $u = auth('api')->user();
            if (!$u || $u->type !== 'admin' || !$u->can('reports.view')) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * تقرير الإيرادات العامة
     */
    public function revenueReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'user_id' => 'nullable|exists:users,id',
            'user_type' => 'nullable|in:customer,provider',
            'status' => 'nullable|string',
            'is_paid' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'period', 'user_id', 'user_type', 'status', 'is_paid'
        ]);

        $report = $this->financialReportService->getRevenueReport($filters);

        return response()->json([
            'success' => true,
            'data' => $report,
            'filters' => $filters
        ]);
    }

    /**
     * تقرير الإيرادات الشهرية
     */
    public function monthlyRevenueReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'nullable|integer|min:2020|max:' . (now()->year + 1)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $year = $request->get('year');
        $report = $this->financialReportService->getMonthlyRevenueReport($year);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * تقرير الإيرادات اليومية
     */
    public function dailyRevenueReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $report = $this->financialReportService->getDailyRevenueReport($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * تقرير الإيرادات حسب نوع الخدمة
     */
    public function revenueByServiceType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only(['start_date', 'end_date', 'period']);
        $report = $this->financialReportService->getRevenueByServiceType($filters);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * ملخص التسويات حسب الحالة والتاريخ والمزود
     */
    public function settlementsSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'provider_id' => 'nullable|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $q = PaymentTransaction::query();
        if ($request->filled('start_date')) $q->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $q->whereDate('created_at', '<=', $request->end_date);
        if ($request->filled('provider_id')) {
            $q->whereHas('booking.service', fn($s) => $s->where('user_id', $request->provider_id));
        }

        $summary = $q->select([
            DB::raw("SUM(CASE WHEN settlement_status='held' THEN held_amount ELSE 0 END) as total_held"),
            DB::raw("SUM(CASE WHEN settlement_status='released' THEN held_amount ELSE 0 END) as total_released"),
            DB::raw("SUM(CASE WHEN settlement_status='refunded' THEN held_amount ELSE 0 END) as total_refunded"),
            DB::raw("SUM(CASE WHEN settlement_status='rejected' THEN held_amount ELSE 0 END) as total_rejected"),
            DB::raw('COUNT(*) as transactions_count')
        ])->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_held' => (float) $summary->total_held,
                'total_released' => (float) $summary->total_released,
                'total_refunded' => (float) $summary->total_refunded,
                'total_rejected' => (float) $summary->total_rejected,
                'transactions_count' => (int) $summary->transactions_count,
            ],
        ]);
    }

    /**
     * تصدير التسويات إلى CSV
     */
    public function exportSettlementsCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'provider_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:held,released,refunded,rejected',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $q = PaymentTransaction::with(['booking.service.user', 'user']);
        if ($request->filled('start_date')) $q->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $q->whereDate('created_at', '<=', $request->end_date);
        if ($request->filled('status')) $q->where('settlement_status', $request->status);
        if ($request->filled('provider_id')) {
            $q->whereHas('booking.service', fn($s) => $s->where('user_id', $request->provider_id));
        }

        $data = $q->orderByDesc('id')->get()->map(function($tx) {
            $service = optional($tx->booking->service);
            $provider = optional($service->user);
            return [
                'transaction_id' => $tx->id,
                'booking_id' => $tx->booking_id,
                'amount_gross' => (float) $tx->amount,
                'held_amount' => (float) $tx->held_amount,
                'status' => $tx->settlement_status,
                'released_at' => $tx->released_at,
                'refunded_at' => $tx->refunded_at,
                'provider_id' => $provider->id ?? null,
                'provider_name' => $provider->name ?? null,
                'service_id' => $service->id ?? null,
                'service_name' => $service->name ?? null,
                'created_at' => $tx->created_at,
            ];
        })->values()->toArray();

        $csv = $this->financialReportService->exportToCsv($data, 'settlements_export_' . now()->format('Y-m-d_H-i-s'));
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="settlements_export_' . now()->format('Y-m-d_H-i-s') . '.csv"',
        ]);
    }

    /**
     * تقرير الإيرادات حسب المزود
     */
    public function revenueByProvider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'user_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only(['start_date', 'end_date', 'period', 'user_id']);
        $report = $this->financialReportService->getRevenueByProvider($filters);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * تقرير العمولات
     */
    public function commissionReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'user_id' => 'nullable|exists:users,id',
            'user_type' => 'nullable|in:customer,provider'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'period', 'user_id', 'user_type'
        ]);

        $report = $this->financialReportService->getCommissionReport($filters);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * تقرير الضرائب
     */
    public function taxReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'user_id' => 'nullable|exists:users,id',
            'user_type' => 'nullable|in:customer,provider'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'period', 'user_id', 'user_type'
        ]);

        $report = $this->financialReportService->getTaxReport($filters);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * تقرير الخصومات
     */
    public function discountReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'user_id' => 'nullable|exists:users,id',
            'user_type' => 'nullable|in:customer,provider'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'period', 'user_id', 'user_type'
        ]);

        $report = $this->financialReportService->getDiscountReport($filters);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * تقرير الأداء المالي الشامل
     */
    public function performanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'user_id' => 'nullable|exists:users,id',
            'user_type' => 'nullable|in:customer,provider',
            'status' => 'nullable|string',
            'is_paid' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'period', 'user_id', 'user_type', 'status', 'is_paid'
        ]);

        $report = $this->financialReportService->getFinancialPerformanceReport($filters);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * تصدير التقرير إلى CSV
     */
    public function exportToCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:revenue,monthly_revenue,daily_revenue,service_type,provider,commission,tax,discount,performance',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'user_id' => 'nullable|exists:users,id',
            'user_type' => 'nullable|in:customer,provider',
            'status' => 'nullable|string',
            'is_paid' => 'nullable|boolean',
            'year' => 'nullable|integer|min:2020|max:' . (now()->year + 1)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'period', 'user_id', 'user_type', 'status', 'is_paid'
        ]);

        $reportType = $request->get('report_type');
        $data = [];

        switch ($reportType) {
            case 'revenue':
                $data = $this->financialReportService->getRevenueReport($filters);
                break;
            case 'monthly_revenue':
                $year = $request->get('year');
                $data = $this->financialReportService->getMonthlyRevenueReport($year);
                break;
            case 'daily_revenue':
                $startDate = $request->get('start_date');
                $endDate = $request->get('end_date');
                $data = $this->financialReportService->getDailyRevenueReport($startDate, $endDate);
                break;
            case 'service_type':
                $data = $this->financialReportService->getRevenueByServiceType($filters);
                break;
            case 'provider':
                $data = $this->financialReportService->getRevenueByProvider($filters);
                break;
            case 'commission':
                $data = $this->financialReportService->getCommissionReport($filters);
                break;
            case 'tax':
                $data = $this->financialReportService->getTaxReport($filters);
                break;
            case 'discount':
                $data = $this->financialReportService->getDiscountReport($filters);
                break;
            case 'performance':
                $data = $this->financialReportService->getFinancialPerformanceReport($filters);
                break;
        }

        $filename = $reportType . '_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $csvContent = $this->financialReportService->exportToCsv($data, $filename);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * تقرير سريع للإحصائيات المالية
     */
    public function quickStats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only(['period']);

        // تقرير الإيرادات السريع
        $revenueReport = $this->financialReportService->getRevenueReport($filters);

        // تقرير العمولات السريع
        $commissionReport = $this->financialReportService->getCommissionReport($filters);

        // تقرير الضرائب السريع
        $taxReport = $this->financialReportService->getTaxReport($filters);

        $quickStats = [
            'revenue' => [
                'total' => $revenueReport['total_revenue'],
                'platform' => $revenueReport['total_platform_amount'],
                'invoices' => $revenueReport['total_invoices'],
                'profit_margin' => $revenueReport['profit_margin']
            ],
            'commission' => [
                'total' => $commissionReport['summary']['total_commission'],
                'average' => $commissionReport['summary']['average_commission'],
                'rate' => $commissionReport['summary']['commission_rate']
            ],
            'tax' => [
                'total' => $taxReport['summary']['total_tax'],
                'average' => $taxReport['summary']['average_tax'],
                'rate' => $taxReport['summary']['tax_rate']
            ],
            'period' => $filters['period'] ?? 'all_time'
        ];

        return response()->json([
            'success' => true,
            'data' => $quickStats
        ]);
    }

    /**
     * لوحة تحكم مالية شاملة
     */
    public function dashboard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'compare_with' => 'nullable|in:previous_period,previous_year,same_period_last_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only(['period']);
        $compareWith = $request->get('compare_with');

        // البيانات الأساسية
        $currentData = [
            'revenue' => $this->financialReportService->getRevenueReport($filters),
            'commission' => $this->financialReportService->getCommissionReport($filters),
            'tax' => $this->financialReportService->getTaxReport($filters),
            'discount' => $this->financialReportService->getDiscountReport($filters),
            'service_types' => $this->financialReportService->getRevenueByServiceType($filters),
            'providers' => $this->financialReportService->getRevenueByProvider($filters)
        ];

        // بيانات المقارنة
        $comparisonData = null;
        if ($compareWith) {
            $comparisonFilters = $this->getComparisonFilters($filters, $compareWith);
            $comparisonData = [
                'revenue' => $this->financialReportService->getRevenueReport($comparisonFilters),
                'commission' => $this->financialReportService->getCommissionReport($comparisonFilters),
                'tax' => $this->financialReportService->getTaxReport($comparisonFilters)
            ];
        }

        // حساب النسب المئوية للتغيير
        $growthMetrics = null;
        if ($comparisonData) {
            $growthMetrics = $this->calculateGrowthMetrics($currentData, $comparisonData);
        }

        // مؤشرات الأداء الرئيسية (KPIs)
        $kpis = [
            'total_revenue' => $currentData['revenue']['total_revenue'],
            'platform_revenue' => $currentData['revenue']['total_platform_amount'],
            'profit_margin' => $currentData['revenue']['profit_margin'],
            'total_invoices' => $currentData['revenue']['total_invoices'],
            'average_invoice_value' => $currentData['revenue']['total_invoices'] > 0 ?
                round($currentData['revenue']['total_revenue'] / $currentData['revenue']['total_invoices'], 2) : 0,
            'commission_rate' => $currentData['commission']['summary']['commission_rate'],
            'tax_rate' => $currentData['tax']['summary']['tax_rate']
        ];

        // ملخص سريع
        $summary = [
            'period' => $filters['period'] ?? 'all_time',
            'generated_at' => now()->toISOString(),
            'total_providers' => $currentData['providers']['summary']['providers_count'],
            'service_categories' => count($currentData['service_types']['service_types'])
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'current_period' => $currentData,
                'comparison_period' => $comparisonData,
                'growth_metrics' => $growthMetrics,
                'kpis' => $kpis,
                'summary' => $summary
            ]
        ]);
    }

    /**
     * الحصول على فلاتر المقارنة
     */
    protected function getComparisonFilters(array $currentFilters, string $compareWith): array
    {
        $filters = $currentFilters;

        switch ($compareWith) {
            case 'previous_period':
                if (isset($filters['period'])) {
                    $filters['period'] = $this->getPreviousPeriod($filters['period']);
                }
                break;

            case 'previous_year':
                if (isset($filters['period'])) {
                    $filters['period'] = $this->getPreviousYearPeriod($filters['period']);
                }
                break;

            case 'same_period_last_year':
                if (isset($filters['period'])) {
                    $filters['period'] = $this->getSamePeriodLastYear($filters['period']);
                }
                break;
        }

        return $filters;
    }

    /**
     * الحصول على الفترة السابقة
     */
    protected function getPreviousPeriod(string $period): string
    {
        $periodMap = [
            'this_month' => 'last_month',
            'this_week' => 'last_week',
            'this_year' => 'last_year',
            'last_7_days' => 'previous_7_days',
            'last_30_days' => 'previous_30_days',
            'last_90_days' => 'previous_90_days'
        ];

        return $periodMap[$period] ?? $period;
    }

    /**
     * الحصول على نفس الفترة من العام السابق
     */
    protected function getSamePeriodLastYear(string $period): string
    {
        // للفترات الشهرية والأسبوعية، نعود للعام السابق
        if (in_array($period, ['this_month', 'this_week'])) {
            return $period . '_last_year';
        }

        return $period;
    }

    /**
     * الحصول على الفترة السابقة من العام السابق
     */
    protected function getPreviousYearPeriod(string $period): string
    {
        return $this->getPreviousPeriod($this->getSamePeriodLastYear($period));
    }

    /**
     * حساب مؤشرات النمو
     */
    protected function calculateGrowthMetrics(array $current, array $previous): array
    {
        $metrics = [];

        // نمو الإيرادات
        if ($previous['revenue']['total_revenue'] > 0) {
            $metrics['revenue_growth'] = round(
                (($current['revenue']['total_revenue'] - $previous['revenue']['total_revenue']) /
                $previous['revenue']['total_revenue']) * 100, 2
            );
        }

        // نمو إيرادات المنصة
        if ($previous['revenue']['total_platform_amount'] > 0) {
            $metrics['platform_revenue_growth'] = round(
                (($current['revenue']['total_platform_amount'] - $previous['revenue']['total_platform_amount']) /
                $previous['revenue']['total_platform_amount']) * 100, 2
            );
        }

        // نمو العمولات
        if ($previous['commission']['summary']['total_commission'] > 0) {
            $metrics['commission_growth'] = round(
                (($current['commission']['summary']['total_commission'] - $previous['commission']['summary']['total_commission']) /
                $previous['commission']['summary']['total_commission']) * 100, 2
            );
        }

        // نمو عدد الفواتير
        if ($previous['revenue']['total_invoices'] > 0) {
            $metrics['invoices_growth'] = round(
                (($current['revenue']['total_invoices'] - $previous['revenue']['total_invoices']) /
                $previous['revenue']['total_invoices']) * 100, 2
            );
        }

        return $metrics;
    }

    /**
     * الحصول على النطاق الزمني من الفترة المحددة
     */
    private function getDateRangeFromPeriod(string $period): array
    {
        $now = now();
        
        return match($period) {
            'today' => [
                'start' => $now->format('Y-m-d'),
                'end' => $now->format('Y-m-d')
            ],
            'week' => [
                'start' => $now->startOfWeek()->format('Y-m-d'),
                'end' => $now->endOfWeek()->format('Y-m-d')
            ],
            'month' => [
                'start' => $now->startOfMonth()->format('Y-m-d'),
                'end' => $now->endOfMonth()->format('Y-m-d')
            ],
            'year' => [
                'start' => $now->startOfYear()->format('Y-m-d'),
                'end' => $now->endOfYear()->format('Y-m-d')
            ],
            default => [
                'start' => $now->subDays(30)->format('Y-m-d'),
                'end' => $now->format('Y-m-d')
            ]
        };
    }

    /**
     * عرض الإيرادات التفصيلي (Task 1)
     */
    public function detailedRevenue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:today,week,month,year,custom',
            'start_date' => 'nullable|date|required_if:period,custom',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:period,custom',
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'source' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:100',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'period', 'start_date', 'end_date', 'status', 'source', 'type', 'search'
        ]);

        // تطبيق الفترات الزمنية المحددة مسبقاً
        if (isset($filters['period']) && $filters['period'] !== 'custom') {
            $dateRange = $this->getDateRangeFromPeriod($filters['period']);
            $filters['start_date'] = $dateRange['start'];
            $filters['end_date'] = $dateRange['end'];
        }

        $report = $this->financialReportService->getDetailedRevenueReport($filters, [
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 15)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الإيرادات التفصيلية بنجاح',
            'data' => $report,
            'filters' => $filters
        ]);
    }

    /**
     * حساب صافي الأرباح (Task 2)
     */
    public function netProfit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:day,month,quarter,year,custom',
            'start_date' => 'nullable|date|required_if:period,custom',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:period,custom'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only(['period', 'start_date', 'end_date']);

        // تطبيق الفترات الزمنية المحددة مسبقاً
        if (isset($filters['period']) && $filters['period'] !== 'custom') {
            $dateRange = $this->getDateRangeFromPeriod($filters['period']);
            $filters['start_date'] = $dateRange['start'];
            $filters['end_date'] = $dateRange['end'];
        }

        $report = $this->financialReportService->getNetProfitReport($filters);

        return response()->json([
            'success' => true,
            'message' => 'تم حساب صافي الأرباح بنجاح',
            'data' => $report,
            'period' => [
                'type' => $filters['period'] ?? 'month',
                'start_date' => $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d'),
                'end_date' => $filters['end_date'] ?? now()->endOfMonth()->format('Y-m-d')
            ]
        ]);
    }

    /**
     * بدء تصدير تقرير (Task 3)
     */
    public function initiateExport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:monthly_revenue,detailed_expenses,profit_loss,comprehensive_financial,tax_report,commissions',
            'format' => 'required|in:pdf,excel,csv',
            'report_name' => 'required|string|max:255',
            'period' => 'nullable|in:day,month,quarter,year,custom',
            'start_date' => 'nullable|date|required_if:period,custom',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:period,custom',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // تطبيق الفترات الزمنية
            $filters = $request->input('filters', []);
            if ($request->has('period') && $request->period !== 'custom') {
                $dateRange = $this->getDateRangeFromPeriod($request->period);
                $filters['start_date'] = $dateRange['start'];
                $filters['end_date'] = $dateRange['end'];
            }

            $report = \App\Models\ExportedReport::create([
                'report_name' => $request->report_name,
                'report_type' => $request->report_type,
                'format' => $request->format,
                'status' => 'processing',
                'filters' => $filters,
                'requested_by' => auth('api')->id(),
                'progress_percentage' => 0
            ]);

            // تشغيل الوظيفة في الخلفية
            \App\Jobs\GenerateReportJob::dispatch($report->id);

            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية تصدير التقرير بنجاح',
                'data' => [
                    'report_id' => $report->id,
                    'status' => 'processing',
                    'estimated_completion' => now()->addMinutes(2)->format('Y-m-d H:i:s')
                ]
            ], 202);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في بدء عملية التصدير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * التحقق من حالة التقرير (Task 3)
     */
    public function checkExportStatus(Request $request, int $reportId)
    {
        $report = \App\Models\ExportedReport::find($reportId);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'التقرير غير موجود'
            ], 404);
        }

        // التحقق من الصلاحيات (المرخص فقط يمكنه رؤية تقاريره)
        if ($report->requested_by !== auth('api')->id()) {
            $user = auth('api')->user();
            if (!$user || $user->type !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول لهذا التقرير'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'report_id' => $report->id,
                'report_name' => $report->report_name,
                'report_type' => $report->report_type,
                'format' => $report->format,
                'status' => $report->status,
                'progress_percentage' => $report->progress_percentage,
                'file_size' => $report->file_size,
                'download_url' => $report->status === 'completed' ? $this->generateDownloadUrl($report) : null,
                'created_at' => $report->created_at,
                'completed_at' => $report->completed_at,
                'error_message' => $report->error_message
            ]
        ]);
    }

    /**
     * قائمة التقارير المصدرة (Task 3)
     */
    public function exportHistory(Request $request)
    {
        $query = \App\Models\ExportedReport::query();
        
        // فلترة حسب المستخدم ما لم يكن أدمن
        $user = auth('api')->user();
        if (!$user || $user->type !== 'admin') {
            $query->where('requested_by', $user->id);
        }

        // تصفح
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        
        $reports = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedReports = $reports->getCollection()->map(function ($report) {
            return [
                'id' => $report->id,
                'report_name' => $report->report_name,
                'report_type' => $this->getReportTypeName($report->report_type),
                'format' => strtoupper($report->format),
                'status' => $this->getStatusLabel($report->status),
                'file_size' => $report->file_size,
                'created_at' => $report->created_at->format('H:i Y-m-d'),
                'completed_at' => $report->completed_at?->format('H:i Y-m-d'),
                'download_url' => $report->status === 'completed' ? $this->generateDownloadUrl($report) : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedReports,
            'meta' => [
                'current_page' => $reports->currentPage(),
                'total' => $reports->total(),
                'per_page' => $reports->perPage(),
                'last_page' => $reports->lastPage()
            ]
        ]);
    }

    /**
     * تحميل التقرير المكتمل
     */
    public function downloadReport(int $reportId)
    {
        $report = \App\Models\ExportedReport::find($reportId);

        if (!$report || !$report->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'التقرير غير متاح للتحميل'
            ], 404);
        }

        $user = auth('api')->user();
        if ($report->requested_by !== $user->id && (!$user || $user->type !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتحميل هذا التقرير'
            ], 403);
        }

        $filePath = storage_path('app/' . $report->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'ملف التقرير غير موجود'
            ], 404);
        }

        return response()->download($filePath, $report->report_name . '.' . strtolower($report->format));
    }

    /**
     * دالة مساعدة: إنشاء رابط التحميل
     */
    private function generateDownloadUrl($report): string
    {
        return url('/api/admin/financial-reports/download/' . $report->id);
    }

    /**
     * دالة مساعدة: الحصول على اسم نوع التقرير
     */
    private function getReportTypeName(string $type): string
    {
        return match($type) {
            'monthly_revenue' => 'تقرير الإيرادات الشهري',
            'detailed_expenses' => 'تقرير المصروفات التفصيلي',
            'profit_loss' => 'تقرير الأرباح والخسائر',
            'comprehensive_financial' => 'التقرير المالي الشامل',
            'tax_report' => 'تقرير الضرائب',
            'commissions' => 'تقرير العمولات',
            default => 'تقرير غير محدد'
        };
    }

    /**
     * دالة مساعدة: الحصول على تسمية الحالة
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'completed' => 'مكتمل',
            'processing' => 'جاري المعالجة',
            'failed' => 'فشل',
            default => 'غير محدد'
        };
    }

    /**
     * إحصائيات سريعة للتصدير
     */
    public function exportStatistics(Request $request)
    {
        $query = \App\Models\ExportedReport::query();
        
        $user = auth('api')->user();
        if (!$user || $user->type !== 'admin') {
            $query->where('requested_by', $user->id);
        }

        $totalReports = $query->count();
        $completedReports = clone $query;
        $completedCount = $completedReports->completed()->count();
        
        $processingReports = clone $query;
        $processingCount = $processingReports->processing()->count();
        
        $failedReports = clone $query;
        $failedCount = $failedReports->failed()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_reports' => $totalReports,
                'completed_reports' => $completedCount,
                'processing_reports' => $processingCount,
                'failed_reports' => $failedCount
            ]
        ]);
    }

    /**
     * تحليل الاتجاهات المالية
     */
    public function trendAnalysis(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:last_3_months,last_6_months,last_12_months'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only(['start_date', 'end_date']);

        // تطبيق الفترات المحددة مسبقاً
        if ($request->has('period')) {
            switch ($request->period) {
                case 'last_3_months':
                    $filters['start_date'] = now()->subMonths(3)->format('Y-m-d');
                    break;
                case 'last_6_months':
                    $filters['start_date'] = now()->subMonths(6)->format('Y-m-d');
                    break;
                case 'last_12_months':
                    $filters['start_date'] = now()->subMonths(12)->format('Y-m-d');
                    break;
            }
        }

        $report = $this->financialReportService->getTrendAnalysis($filters);

        return response()->json([
            'success' => true,
            'data' => $report,
            'filters' => $filters
        ]);
    }

    /**
     * تقرير الربحية حسب المزود
     */
    public function providerProfitability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,yesterday,this_week,this_month,this_year,last_7_days,last_30_days,last_90_days',
            'min_revenue' => 'nullable|numeric|min:0',
            'min_invoices' => 'nullable|integer|min:0',
            'sort_by' => 'nullable|in:revenue,profitability_score,invoices_count,average_invoice_value'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'period', 'min_revenue', 'min_invoices'
        ]);

        $report = $this->financialReportService->getProviderProfitabilityReport($filters);

        // تطبيق الفلترة الإضافية
        if (isset($filters['min_revenue']) || isset($filters['min_invoices'])) {
            $report['providers'] = $report['providers']->filter(function ($provider) use ($filters) {
                if (isset($filters['min_revenue']) && $provider['total_revenue'] < $filters['min_revenue']) {
                    return false;
                }
                if (isset($filters['min_invoices']) && $provider['invoices_count'] < $filters['min_invoices']) {
                    return false;
                }
                return true;
            });
        }

        if ($request->has('sort_by')) {
            $sortBy = $request->sort_by;
            $report['providers'] = $report['providers']->sortByDesc($sortBy);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'filters' => $filters
        ]);
    }
}
