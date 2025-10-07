<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\EscrowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\Settlements\SettlementFilterRequest;
use App\Http\Resources\Admin\SettlementResource;

class SettlementController extends Controller
{
    public function __construct(private readonly EscrowService $escrowService)
    {
    }

    public function pending(SettlementFilterRequest $request)
    {
        $q = PaymentTransaction::with(['booking.service.user', 'user'])
            ->where('settlement_status', 'held');

        if ($request->filled('provider_id')) {
            $q->whereHas('booking.service', fn($s) => $s->where('user_id', $request->provider_id));
        }
        if ($request->filled('service_type')) {
            $q->whereHas('booking.service', function ($s) use ($request) {
                return match ($request->service_type) {
                    'event' => $s->whereHas('event'),
                    'restaurant' => $s->whereHas('restaurant'),
                    'property' => $s->whereHas('property'),
                    'catering' => $s->whereHas('cateringItem'),
                };
            });
        }
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->integer('per_page', 20);
        $txs = $q->orderByDesc('id')->paginate($perPage);

        return format_response(true, __('admin.settlements.pending_payouts'), [
            'data' => SettlementResource::collection($txs->items()),
            'pagination' => [
                'current_page' => $txs->currentPage(),
                'last_page' => $txs->lastPage(),
                'per_page' => $txs->perPage(),
                'total' => $txs->total(),
            ],
            'filters' => $request->validated(),
        ]);
    }

    public function process(PaymentTransaction $transaction, Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:approve,reject',
            'remarks' => 'required_if:action,reject|nullable|string|max:1000',
        ]);

        if ($transaction->settlement_status !== 'held') {
            return format_response(false, 'Transaction not in held status', code: 400);
        }

        if ($data['action'] === 'approve') {
            $this->escrowService->releaseToProvider($transaction);
            $transaction->update([
                'processed_by' => $request->user()->id,
                'admin_remarks' => $data['remarks'] ?? null,
            ]);

            // Notify provider about payout
            $providerId = optional($transaction->booking->service)->user_id;
            if ($providerId) {
                app('App\\Services\\NotificationService')->created([
                    'user_id' => $providerId,
                    'action' => 'payout_released',
                    'message' => 'تم تحويل أرباحك لعملية الحجز #' . $transaction->booking_id,
                ]);
            }

            return format_response(true, 'Payout released');
        }

        // reject path
        $this->escrowService->refundToCustomer($transaction);
        $transaction->update([
            'processed_by' => $request->user()->id,
            'admin_remarks' => $data['remarks'],
            'settlement_status' => 'rejected',
        ]);

        // Notify provider about rejection
        $providerId = optional($transaction->booking->service)->user_id;
        if ($providerId) {
            app('App\\Services\\NotificationService')->created([
                'user_id' => $providerId,
                'action' => 'payout_rejected',
                'message' => 'تم رفض تحويل أرباحك لعملية الحجز #' . $transaction->booking_id . '. السبب: ' . $data['remarks'],
            ]);
        }

        return format_response(true, 'Payout rejected and refunded to customer');
    }

    public function partial(PaymentTransaction $transaction, \App\Http\Requests\Admin\Settlements\PartialSettlementRequest $request)
    {
        if ($transaction->settlement_status !== 'held') {
            return format_response(false, 'Transaction not in held status', code: 400);
        }
        $data = $request->validated();
        if (!empty($data['percentage'])) {
            $providerAmount = round(((float) $transaction->held_amount) * ((float)$data['percentage'] / 100), 2);
            $customerAmount = round(((float) $transaction->held_amount) - $providerAmount, 2);
        } else {
            $providerAmount = (float) $data['provider_amount'];
            $customerAmount = isset($data['customer_amount']) ? (float) $data['customer_amount'] : max(0, (float)$transaction->held_amount - $providerAmount);
        }
        $this->escrowService->partialSettle($transaction, $providerAmount, $customerAmount, $data['remarks'] ?? null);
        return format_response(true, 'Partial settlement processed');
    }
}

