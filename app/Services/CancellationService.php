<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use Carbon\Carbon;

class CancellationService
{
    /**
     * Computes refund amount and percent based on rules.
     * Rules example (hours):
     *  - 336 (14 days): refund 100%
     *  - 168 (7 days): refund 50%
     *  - 72 hours: refund 25%
     * If cancellation happens after last threshold, refund 0%.
     */
    public function computeRefund(Booking $booking, Carbon $cancelledAt = null): array
    {
        $cancelledAt = $cancelledAt ?: now();

        // Fetch policy: prefer service-specific; else default platform policy (service_id null)
        $policy = CancellationPolicy::where('service_id', $booking->service_id)
            ->first()
            ?: CancellationPolicy::whereNull('service_id')->first();

        $total = (float) $booking->total;
        if (!$policy) {
            return ['refund_percent' => 0, 'refund_amount' => 0.0];
        }

        $start = Carbon::parse($booking->start_date);
        // Use signed difference so cancellations after start yield negative, then clamp to 0
        $hoursBeforeStart = max(0, $cancelledAt->diffInHours($start, false));

        $rules = collect($policy->rules)->sortByDesc('threshold_hours');
        $refundPercent = 0;
        foreach ($rules as $rule) {
            $threshold = (int)($rule['threshold_hours'] ?? 0);
            if ($hoursBeforeStart >= $threshold) {
                $refundPercent = (int)($rule['refund_percent'] ?? 0);
                break;
            }
        }

        $refundAmount = round($total * ($refundPercent / 100), 2);
        return ['refund_percent' => $refundPercent, 'refund_amount' => $refundAmount];
    }
}

