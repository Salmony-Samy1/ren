<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Traits\LogUserActivity;
use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingService;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\QuoteBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\CheckAvailabilityRequest;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use LogUserActivity;
    private readonly BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * إنشاء حجز جديد مع معالجة الدفع المتقدمة
     */
    public function store(StoreBookingRequest $request)
    {
        $result = $this->bookingService->createBooking($request->validated(), auth()->user());

        if ($result['success']) {
            // تسجيل نشاط إنشاء الحجز
            if (isset($result['data']['booking']['id'])) {
                $this->logBooking($result['data']['booking']['id'], 'create_booking');
            }
            
            // التحقق من حالة الدفع
            if (isset($result['data']['payment_status'])) {
                switch ($result['data']['payment_status']) {
                    case 'requires_action':
                        // إعادة توجيه للدفع (مثل Benefit)
                        return response()->json([
                            'success' => false,
                            'message' => 'Payment requires additional action',
                            'data' => [
                                'requires_action' => true,
                                'redirect_url' => $result['data']['redirect_url'],
                                'booking_id' => $result['data']['booking']['id'],
                                'status' => 'payment_pending'
                            ]
                        ], 202);
                        
                    case 'confirmed':
                        // الدفع نجح فوراً (مثل Apple Pay)
                        return response()->json([
                            'success' => true,
                            'message' => $result['message'],
                            'data' => $result['data']
                        ], 201);
                        
                    case 'pending':
                        // الدفع معلق (في انتظار webhook)
                        return response()->json([
                            'success' => true,
                            'message' => 'Booking created successfully. Payment is being processed.',
                            'data' => $result['data']
                        ], 201);
                }
            }
            
            // الحالة الافتراضية للحجز المجاني أو المكتمل
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error' => $result['error'] ?? null
        ], $result['code'] ?? 500);
    }

    /**
     * Bulk booking for multiple catering services from same provider
     */
    public function storeBulk(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array|min:1',
            'orders.*.service_id' => 'required|integer|exists:services,id',
            'orders.*.start_date' => 'sometimes|date|after_or_equal:now',
            'orders.*.end_date' => 'sometimes|date|after_or_equal:orders.*.start_date',
            'orders.*.booking_details' => 'required|array',
            'orders.*.booking_details.number_of_items' => 'required|integer|min:1',
            'orders.*.booking_details.add_ons' => 'sometimes|array',
            'orders.*.booking_details.add_ons.*.id' => 'required_with:orders.*.booking_details.add_ons|integer|exists:catering_items,id',
            'orders.*.booking_details.add_ons.*.qty' => 'required_with:orders.*.booking_details.add_ons|integer|min:1',
            'orders.*.booking_details.notes' => 'sometimes|string|max:1000',
            'orders.*.booking_details.details' => 'sometimes|array',
            'orders.*.fulfillment_method' => 'required|in:delivery,pickup,on_site',
            'orders.*.address_id' => 'required_if:orders.*.fulfillment_method,delivery|integer|exists:user_addresses,id',
            'payment_method' => 'required|in:wallet,apple_pay,visa,mada,samsung_pay,benefit,stcpay',
            'idempotency_key' => 'sometimes|string|max:100',
            'notes' => 'sometimes|string|max:1000',
        ]);

        $user = auth()->user();
        $result = $this->bookingService->createBulkCateringBookings($validated, $user);
        $status = ($result['success'] ?? false) ? 201 : ($result['code'] ?? 422);
        return response()->json([
            'success' => (bool)($result['success'] ?? false),
            'message' => $result['message'] ?? (($result['success'] ?? false) ? 'Created' : 'Failed'),
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ], $status);
    }

    /**
     *
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'date_from', 'date_to']);
        $result = $this->bookingService->getUserBookings(auth()->user(), $filters);

        return response()->json($result);
    }

    /**
     *
     */
    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        $booking->load(['service.category', 'service.user', 'service.event', 'invoice']);

        // HTTP caching validators
        $etag = \App\Support\HttpCache::makeEtag(['booking',$booking->id,$booking->updated_at?->timestamp,$booking->status]);
        $lastModified = $booking->updated_at ?? $booking->created_at;
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy())) {
            return $resp304;
        }

        return \App\Support\HttpCache::withValidators(
            response()->json([
                'success' => true,
                'data' => $booking
            ]),
            $etag,
            optional($lastModified)->copy(),
            60
        );
    }

    /**
     * Update booking privacy
     */
    public function updatePrivacy(\App\Http\Requests\UpdateBookingPrivacyRequest $request, Booking $booking)
    {
        $this->authorize('updateStatus', $booking); // same owner/provider constraint
        $booking->update(['privacy' => $request->validated()['privacy']]);
        return response()->json(['success' => true, 'data' => $booking]);
    }

    public function updatePrivacyViewers(\App\Http\Requests\UpdateBookingPrivacyViewersRequest $request, Booking $booking)
    {
        $this->authorize('updateStatus', $booking);
        if ($booking->privacy !== 'custom') {
            return response()->json(['success' => false, 'message' => 'Booking is not set to custom privacy'], 422);
        }
        $ids = $request->validated()['viewer_user_ids'];
        \DB::table('booking_privacy_users')->where('booking_id', $booking->id)->delete();
        $rows = collect($ids)->unique()->map(fn($id) => ['booking_id' => $booking->id, 'viewer_user_id' => $id, 'created_at' => now(), 'updated_at' => now()])->all();
        if (!empty($rows)) { \DB::table('booking_privacy_users')->insert($rows); }
        return response()->json(['success' => true]);
    }

    /**
     *
     */
    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking)
    {
        $this->authorize('updateStatus', $booking);

        $validated = $request->validated();

        $result = $this->bookingService->updateBookingStatus(
            $booking,
            $validated['status'],
            $validated['notes'] ?? null
        );

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'message' => $result['message'],
            'error' => $result['error'] ?? null
        ], 500);
    }

    /**
     *
     */
    public function cancel(CancelBookingRequest $request, Booking $booking)
    {
        $this->authorize('cancel', $booking);

        $validated = $request->validated();

        $result = $this->bookingService->cancelBooking($booking, $validated['reason'] ?? null);

        if ($result['success']) {
            // تسجيل نشاط إلغاء الحجز
            $this->logBooking($booking->id, 'cancel_booking');
            
            return response()->json([
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'message' => $result['message']
        ], 400);
    }

    /**
     *
     */
    public function checkAvailability(CheckAvailabilityRequest $request)
    {
        $validated = $request->validated();

        $service = Service::findOrFail($validated['service_id']);
        $isAvailable = $this->bookingService->isServiceAvailable(
            $service,
            $validated['start_date'],
            $validated['end_date']
        );

        // provide optional capacity preview
        $capacityPreview = null;
        if (!empty($validated['booking_details'])) {
            $capacityPreview = $this->bookingService->capacityPreview($service, $validated['start_date'], $validated['end_date'], $validated['booking_details']);
        }

        return response()->json([
            'success' => true,
            'available' => $isAvailable,
            'capacity' => $capacityPreview,
            'service' => $service->only(['id', 'name', 'price'])
        ]);
    }

    /**
     * Return a quote (price breakdown) prior to booking/payment
     */
    public function quote(QuoteBookingRequest $request)
    {
        $validated = $request->validated();

        // Multi-orders quote support
        if (!empty($validated['orders']) && is_array($validated['orders'])) {
            $orders = $validated['orders'];
            $serviceIds = collect($orders)->pluck('service_id')->unique()->values()->all();
            $services = Service::with(['event','catering','restaurant','property'])->whereIn('id', $serviceIds)->get()->keyBy('id');

            $subtotal = 0.0; $tax = 0.0; $discount = 0.0; $grand = 0.0; $items = [];

            foreach ($orders as $ord) {
                $svc = $services->get($ord['service_id']);
                if (!$svc) { return response()->json(['success'=>false,'message'=>'Service not found: '.$ord['service_id']],404); }
                $feeInput = array_merge($ord['booking_details'], [
                    'start_date' => $ord['start_date'],
                    'end_date' => $ord['end_date'],
                ]);
                $fees = $this->bookingService->calculateBookingFees($svc, $feeInput);
                $subtotal += (float)($fees['subtotal'] ?? 0);
                $tax += (float)($fees['tax_amount'] ?? 0);
                $discount += (float)($fees['discount'] ?? 0);
                $grand += (float)($fees['total_amount'] ?? 0);
                $items[] = [
                    'service_id' => $svc->id,
                    'breakdown' => $fees,
                ];
            }

            // Apply coupon preview once across the total
            $appliedCoupon = null;
            if (!empty($validated['coupon_code'])) {
                try {
                    $couponService = app(\App\Services\CouponService::class);
                    $coupon = $couponService->validateCoupon($validated['coupon_code'], auth()->user(), $grand);
                    $couponRes = $couponService->applyCoupon($coupon, $grand);
                    $discount += (float) $couponRes['discount'];
                    $grand = (float) $couponRes['total_after_discount'];
                    $appliedCoupon = ['code' => $coupon->code, 'discount' => (float) $couponRes['discount']];
                } catch (\Throwable $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
                }
            }

            // Apply points preview across the total
            $appliedPoints = null;
            if (!empty($validated['points_to_use']) && $grand > 0) {
                $ledger = app(\App\Services\PointsLedgerService::class);
                $balance = $ledger->balance(auth()->user());
                $eligible = min((int)$validated['points_to_use'], $balance);
                $rate = (float) get_setting('points_to_wallet_rate');
                $maxRatio = (float) get_setting('points_max_redeem_ratio');
                if ($rate > 0 && $maxRatio > 0) {
                    $maxByPolicy = max(0.0, round($grand * $maxRatio, 2));
                    $pointsValueEligible = round($eligible * $rate, 2);
                    $pointsValueToApply = (float) min($pointsValueEligible, $maxByPolicy);
                    $discount += $pointsValueToApply;
                    $grand = max(0, round($grand - $pointsValueToApply, 2));
                    $appliedPoints = ['used' => $eligible, 'value' => $pointsValueToApply];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'subtotal' => round($subtotal, 2),
                    'discount' => round($discount, 2),
                    'tax_total' => round($tax, 2),
                    'total_amount' => round($grand, 2),
                    'coupon' => $appliedCoupon,
                    'points' => $appliedPoints,
                ],
            ]);
        }

        // Single-item quote (existing behavior)
        $service = Service::with(['event','catering','restaurant','property'])->findOrFail($validated['service_id']);
        // Compute fees
        $feeInput = array_merge($validated['booking_details'], [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);
        $fees = $this->bookingService->calculateBookingFees($service, $feeInput);

        // Apply coupon preview if provided
        if (!empty($validated['coupon_code'])) {
            try {
                $couponService = app(\App\Services\CouponService::class);
                $coupon = $couponService->validateCoupon($validated['coupon_code'], auth()->user(), (float)($fees['total_amount'] ?? 0));
                $couponResult = $couponService->applyCoupon($coupon, (float)$fees['total_amount']);
                $fees['discount'] = ($fees['discount'] ?? 0) + $couponResult['discount'];
                $fees['total_amount'] = $couponResult['total_after_discount'];
                $fees['coupon'] = ['code' => $coupon->code, 'discount' => $couponResult['discount']];
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        // Apply points preview if requested
        if (!empty($validated['points_to_use'])) {
            $ledger = app(\App\Services\PointsLedgerService::class);
            $balance = $ledger->balance(auth()->user());
            $pointsEligible = min((int)$validated['points_to_use'], $balance);
            $rate = get_setting('points_to_wallet_rate');
            $maxRatio = get_setting('points_max_redeem_ratio');
            if ($rate !== null && $maxRatio !== null) {
                $rate = (float)$rate; $maxRatio = (float)$maxRatio;
                $maxByPolicy = max(0.0, round(($fees['total_amount'] ?? 0) * $maxRatio, 2));
                $pointsValueEligible = round($pointsEligible * $rate, 2);
                $pointsValueToApply = (float) min($pointsValueEligible, $maxByPolicy);
                $fees['discount'] = ($fees['discount'] ?? 0) + $pointsValueToApply;
                $fees['total_amount'] = max(0, round(($fees['total_amount'] ?? 0) - $pointsValueToApply, 2));
                $fees['points'] = ['used' => (int) floor($pointsValueToApply / max($rate, 0.000001)), 'value' => $pointsValueToApply];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $fees,
        ]);
    }
}
