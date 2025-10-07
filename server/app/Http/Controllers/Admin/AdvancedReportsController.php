<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\GenerateReportRequest;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;

class AdvancedReportsController extends Controller
{
    public function __construct(private FinancialReportService $service)
    {
        // RBAC: admin + reports.view
        $this->middleware(function ($request, $next) {
            $u = auth('api')->user();
            if (!$u || $u->type !== 'admin' || !$u->can('reports.view')) {
                abort(403);
            }
            return $next($request);
        });
    }

    // POST /reports/generate — body: type, filters
    public function generate(GenerateReportRequest $request)
    {
        $type = $request->validated()['type'];
        $filters = $request->only(['start_date','end_date','provider_id','year']);

        return match ($type) {
            'revenue' => format_response(true, __('OK'), $this->service->getRevenueReport($filters)),
            'monthly_revenue' => format_response(true, __('OK'), $this->service->getMonthlyRevenueReport((int)($filters['year'] ?? now()->year))),
            'daily_revenue' => format_response(true, __('OK'), $this->service->getDailyRevenueReport($filters['start_date'] ?? null, $filters['end_date'] ?? null)),
            'service_type' => format_response(true, __('OK'), $this->service->getRevenueByServiceType($filters)),
            'provider' => format_response(true, __('OK'), $this->service->getRevenueByProvider($filters)),
            'commission' => format_response(true, __('OK'), $this->service->getCommissionReport($filters)),
            'tax' => format_response(true, __('OK'), $this->service->getTaxReport($filters)),
            'discount' => format_response(true, __('OK'), $this->service->getDiscountReport($filters)),
            'performance' => format_response(true, __('OK'), $this->service->getFinancialPerformanceReport($filters)),
            'quick_stats' => format_response(true, __('OK'), $this->service->quickStats($filters)),
            'provider_profitability' => format_response(true, __('OK'), $this->service->getProviderProfitabilityReport($filters)),
            'trend' => format_response(true, __('OK'), $this->service->getTrendAnalysis($filters)),
            default => format_response(false, __('Invalid type'), null, 422)
        };
    }

    // POST /reports/schedule — يربط بـ Jobs لاحقًا (placeholder)
    public function schedule(GenerateReportRequest $request)
    {
        $data = $request->validated();
        // Placeholder: enqueue a job for report generation & email delivery
        return format_response(true, __('Scheduled'), ['type' => $data['type'], 'scheduled' => true]);
    }
}

