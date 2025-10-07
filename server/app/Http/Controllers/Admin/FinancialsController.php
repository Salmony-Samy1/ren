<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialsController extends Controller
{
    public function __construct(private FinancialReportService $financialReportService)
    {
    }

    // GET /financials/revenue-summary
    public function revenueSummary(Request $request)
    {
        $this->authorizeAdmin();
        $filters = $request->only(['start_date','end_date','user_id','status']);
        $report = $this->financialReportService->getRevenueReport($filters);
        return format_response(true, __('Fetched successfully'), $report);
    }

    // GET /financials/commissions
    public function commissionsIndex(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'provider_id' => ['sometimes','integer','exists:users,id'],
            'status' => ['sometimes','in:pending,paid,cancelled'],
            'date_from' => ['sometimes','date'],
            'date_to' => ['sometimes','date','after_or_equal:date_from'],
            'per_page' => ['sometimes','integer','min:1','max:50'],
        ]);
        $perPage = (int)($data['per_page'] ?? 15);

        $q = Invoice::query()
            ->where('invoice_type', 'provider')
            ->where('commission_amount', '>', 0)
            ->with(['user:id,full_name,email', 'booking.service.user:id,full_name']);

        if (!empty($data['provider_id'])) {
            // Limit by provider (owner of service)
            $q->whereHas('booking.service', fn($qq)=>$qq->where('user_id', $data['provider_id']));
        }
        if (!empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (!empty($data['date_from'])) { $q->whereDate('created_at', '>=', $data['date_from']); }
        if (!empty($data['date_to'])) { $q->whereDate('created_at', '<=', $data['date_to']); }

        $p = $q->orderByDesc('created_at')->paginate($perPage)->withQueryString();
        return format_response(true, __('Fetched successfully'), [
            'items' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ]);
    }

    // POST /financials/commissions/{invoice}/mark-as-paid
    public function commissionsMarkAsPaid(Invoice $invoice)
    {
        $this->authorizeAdmin();
        if ($invoice->invoice_type !== 'provider' || (float)$invoice->commission_amount <= 0) {
            return format_response(false, __('Invalid commission invoice'), null, 422);
        }
        $invoice->markAsPaid();
        return format_response(true, __('Marked as paid'), $invoice->fresh());
    }

    // GET /financials/reports/vat
    public function vatReport(Request $request)
    {
        $this->authorizeAdmin();
        $filters = $request->only(['start_date','end_date','user_id']);
        $report = $this->financialReportService->getTaxReport($filters);
        return format_response(true, __('Fetched successfully'), $report);
    }

    private function authorizeAdmin(): void
    {
        $user = auth('api')->user();
        abort_unless($user && $user->type === 'admin', 403);
    }
}

