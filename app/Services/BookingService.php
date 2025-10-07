<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Setting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct()
    {
    }


    /**
     * Capacity preview helper: returns used, requested, and remaining based on service type.
     */
    public function capacityPreview(Service $service, string $startDate, string $endDate, array $details): array
    {
        $start = Carbon::parse($startDate); $end = Carbon::parse($endDate);
        $overlapping = Booking::where('service_id', $service->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })
            ->get();
        if ($service->event) {
            $used = $overlapping->sum(fn($b) => (int)($b->booking_details['number_of_people'] ?? 1));
            $req = (int)($details['number_of_people'] ?? 1);
            $cap = (int)($service->event->max_individuals ?? 0);
            return ['used' => $used, 'requested' => $req, 'capacity' => $cap, 'remaining' => max(0, $cap - $used)];
        } elseif ($service->catering) {
            $used = $overlapping->sum(fn($b) => (int)($b->booking_details['number_of_items'] ?? 0));
            $req = (int)($details['number_of_items'] ?? 0);
            $cap = (int)($service->catering->available_stock ?? 0);
            return ['used' => $used, 'requested' => $req, 'capacity' => $cap, 'remaining' => max(0, $cap - $used)];
        } elseif ($service->restaurant) {
            // Unified restaurant capacity preview
            return \App\Services\Booking\Support\RestaurantCapacity::preview($service, $start, $details);
        } else {
            return ['used' => $overlapping->count(), 'requested' => 1, 'capacity' => 1, 'remaining' => 0];
        }
    }

    /**
     *
     */
    public function createBooking(array $data, User $user): array
    {
        $service = Service::with(['user', 'event', 'cateringItem', 'restaurant', 'property'])
            ->findOrFail($data['service_id']);

        $validation = $this->validateBooking($data, $user, $service);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'] ?? 'Invalid booking request',
                'code' => $validation['code'] ?? 400,
            ];
        }

        // Fulfillment method validation for catering bookings (single)
        if ($service->catering) {
            try {
                $this->normalizeAndValidateCateringFulfillment($service, $data['booking_details'], $data['fulfillment_method'] ?? null, $data['address_id'] ?? null, $user);
            } catch (\InvalidArgumentException $e) {
                return ['success' => false, 'message' => $e->getMessage(), 'code' => 422];
            }
        }

        // Normalize dates for events: always use event window, ignore client dates
        if ($service->event) {
            $data['start_date'] = optional($service->event->start_at)->toDateTimeString();
            $data['end_date'] = optional($service->event->end_at)->toDateTimeString();
        }

        // حساب الرسوم
        $feeInput = array_merge($data['booking_details'], [
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'order_id' => $data['order_id'] ?? null,
        ]);

        // تطبيق القسيمة إن وجدت
        $couponCode = $data['coupon_code'] ?? null;
        $fees = $this->calculateBookingFees($service, $feeInput);

        // تطبيق النقاط إذا طُلب ذلك وفق إعدادات الأدمن (دون أي قيم هاردكود)
        $pointsToUse = (int) ($data['points_to_use'] ?? 0);
        if ($pointsToUse > 0) {
            $ledger = app(\App\Services\PointsLedgerService::class);
            $balance = $ledger->balance($user);
            $pointsEligible = min($pointsToUse, $balance);

            // إعدادات من لوحة التحكم
            $rate = get_setting('points_to_wallet_rate'); // قيمة الريال لكل نقطة
            $maxRatio = get_setting('points_max_redeem_ratio'); // نسبة قصوى من إجمالي الحجز
            if ($rate === null || $maxRatio === null) {
                abort(500, 'Points redemption settings are not configured');
            }
            $rate = (float) $rate;
            $maxRatio = (float) $maxRatio;

            $maxByPolicy = max(0.0, round(($fees['total_amount'] ?? 0) * $maxRatio, 2));
            $pointsValueEligible = round($pointsEligible * $rate, 2);
            $pointsValueToApply = (float) min($pointsValueEligible, $maxByPolicy);
            // حوّل قيمة الريال إلى عدد نقاط فعلي سيتم خصمه (تقريب للأسفل)
            $pointsToSpend = (int) floor($pointsValueToApply / max($rate, 0.000001));
            if ($pointsToSpend > 0 && ($fees['total_amount'] ?? 0) > 0) {
                $spent = $ledger->spend($user, $pointsToSpend, 'booking_redeem', ['service_id' => $service->id]);
                $appliedValue = round($spent * $rate, 2);
                $fees['discount'] = ($fees['discount'] ?? 0) + $appliedValue;
                $fees['total_amount'] = max(0, round(($fees['total_amount'] ?? 0) - $appliedValue, 2));
                $fees['points'] = ['used' => $spent, 'value' => $appliedValue];
            }
        }

        if ($couponCode) {
            $couponService = app(\App\Services\CouponService::class);
            try {
                $coupon = $couponService->validateCoupon($couponCode, $user, (float)($fees['total_amount'] ?? 0));
                $couponResult = $couponService->applyCoupon($coupon, (float)$fees['total_amount']);
                $fees['discount'] = ($fees['discount'] ?? 0) + $couponResult['discount'];
                $fees['total_amount'] = $couponResult['total_after_discount'];
                $fees['coupon'] = ['code' => $coupon->code, 'discount' => $couponResult['discount']];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => 422,
                ];
            }
        }

        // منع الحجوزات ذات المبلغ الصفري إلا إذا تم السماح بها صراحةً
        if (($fees['total_amount'] ?? 0) <= 0) {
            // السماح بالحجز المجاني للمطاعم حتى لو كان الإعداد العام لا يسمح بذلك
            $allowFree = get_setting('allow_free_bookings', false) || ($service->restaurant !== null);
            if (!$allowFree) {
                return [
                    'success' => false,
                    'message' => 'Total amount must be greater than 0',
                    'code' => 422,
                ];
            }
            // السماح بالحجز المجاني بدون إنشاء معاملة دفع
            $paymentResult = [
                'success' => true,
                'message' => 'Free booking (zero amount)',
                'transaction_id' => null,
                'status' => 'confirmed',
            ];
        } else {
            // معالجة الدفع مع metadata للحجز
            $paymentData = array_merge($data, [
                'amount' => $fees['total_amount'],
                'metadata' => [
                    'type' => 'booking',
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'provider_id' => $service->user_id,
                    'booking_details' => $data['booking_details'] ?? null,
                    'fees_breakdown' => $fees
                ]
            ]);
            
            $paymentResult = $this->processPayment($user, $fees['total_amount'], $paymentData);
            if (!($paymentResult['success'] ?? false)) {
                return $paymentResult;
            }
        }

        try {
            DB::beginTransaction();

            // Idempotency: prevent duplicate bookings with the same key per user
            $idempotencyKey = $data['idempotency_key'] ?? request()->header('Idempotency-Key');
            if ($idempotencyKey) {
                $existing = $this->findExistingIdempotentBooking($user, $idempotencyKey);
                if ($existing) {
                    DB::commit();
                    return [
                        'success' => true,
                        'message' => 'Booking already created (idempotent replay)',
                        'data' => ['booking' => $existing, 'invoice' => $existing->invoice ?? null],
                    ];
                }
            }

            // Concurrency control: lock service row and verify availability atomically
            $lockedService = Service::where('id', $service->id)->lockForUpdate()->first();
            // Also lock catering head row to safely read/update available_stock under concurrency
            $lockedCatering = null;
            if ($lockedService->catering) {
                $lockedCatering = \App\Models\Catering::where('service_id', $lockedService->id)->lockForUpdate()->first();
                if (!$lockedCatering) {
                    throw new \RuntimeException('Catering stock row not found for service');
                }
            }

            // Lock overlapping bookings rows for this service to prevent race conditions under high concurrency
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);
            $overlappingBookings = Booking::where('service_id', $lockedService->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('start_date', '<=', $start)
                              ->where('end_date', '>=', $end);
                        });
                })
                ->lockForUpdate() // lock matching rows to serialize concurrent creates
                ->get();

            // Capacity checks per service type (people/items-aware)
            if ($lockedService->event) {
                $used = $overlappingBookings->sum(function($b){ return (int)($b->booking_details['number_of_people'] ?? 1); });
                $req = (int)($data['booking_details']['number_of_people'] ?? 1);
                $cap = (int)($lockedService->event->max_individuals ?? 1);
                if ($used + $req > $cap) {
                    throw new \RuntimeException('Event capacity exceeded for the selected time');
                }
            } elseif ($lockedService->restaurant) {
                } elseif ($lockedService->event) {
                    // Optionally, reflect reserved seats at service level (non-authoritative; bookings are the source of truth)
                    // Not decrementing a column here to avoid drift; availability is validated by overlapping bookings above.

                // Unified restaurant capacity validation
                \App\Services\Booking\Support\RestaurantCapacity::validate($lockedService, $start, $data['booking_details']);
            } elseif ($lockedService->catering) {
                $this->validateCateringCapacityAndAddons($lockedService, $lockedCatering, $data['booking_details'], $overlappingBookings);
            } else {
                $maxBookings = $this->getMaxBookingsForService($lockedService);
                if ($overlappingBookings->count() >= $maxBookings) {
                    throw new \RuntimeException('Service just became unavailable for the selected dates');
                }
            }

            // Deduct stock atomically under the same transaction
            if ($lockedService->catering) {
                $this->deductCateringStock($lockedCatering, $data['booking_details']);
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'service_id' => $service->id,

	                'payment_method' => $data['payment_method'],
	                'transaction_id' => $paymentResult['transaction_id'],

                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'booking_details' => $data['booking_details'],
                'tax' => $fees['tax_amount'],
                'subtotal' => $fees['subtotal'],
                'discount' => $fees['discount'],
                'total' => $fees['total_amount'],
                'currency' => $service->price_currency ?? get_setting('default_service_currency','SAR'),
                'wallet_currency' => optional($user->wallet)->currency ?? get_setting('default_wallet_currency','SAR'),
                'total_wallet_currency' => app(\App\Services\ExchangeRateService::class)->convert($fees['total_amount'], ($service->price_currency ?? get_setting('default_service_currency','SAR')), (optional($user->wallet)->currency ?? get_setting('default_wallet_currency','SAR'))),
                'payment_method' => $data['payment_method'],
                'transaction_id' => $paymentResult['transaction_id'],
                'status' => $paymentResult['status'],
                'idempotency_key' => $idempotencyKey ?? null,
            ]);

            // Create Order and Invoice via factories to keep logic cohesive
            [$order, $orderNumber, $invoiceNumber] = app(\App\Services\Booking\Contracts\IOrderFactory::class)
                ->createForSingle($booking, $service, $user, $fees, $paymentResult, $data['payment_method'] ?? null);

            // Create one invoice tied to the order (not booking)
            $invoice = app(\App\Services\Booking\Contracts\IInvoiceFactory::class)
                ->createForSingle($booking, $fees, $invoiceNumber, $booking->currency, $data['payment_method'] ?? null, $paymentResult['transaction_id'] ?? null);


            // تسجيل استخدام القسيمة إن وُجدت
            if (!empty($fees['coupon']['code'] ?? null)) {
                app(\App\Services\CouponService::class)->redeem($user, \App\Models\Coupon::where('code', $fees['coupon']['code'])->first(), $booking);
            }
            // حفظ معلومات النقاط المستخدمة
            if (!empty($fees['points'])) {
                $booking->update([
                    'points_used' => (int) ($fees['points']['used'] ?? 0),
                    'points_value' => (float) ($fees['points']['value'] ?? 0.0),
                ]);
                if ($invoice) {
                    $invoice->update([
                        'points_used' => (int) ($fees['points']['used'] ?? 0),
                        'points_value' => (float) ($fees['points']['value'] ?? 0.0),
                    ]);
                }
            }

            // Schedule reminders
            $this->scheduleReminders($booking);

            // Link payment transaction and hold escrow
            $this->linkPaymentTransactionAndEscrow($booking, $paymentResult);

            // Dispatch domain event (legacy name); downstream logic will gate by status
            // Note: relies on listeners to send notifications instead of inline
            event(new \App\Events\BookingCompleted($booking));

	            // Invalidate search caches so remaining seats/stock reflects immediately
            $this->bumpSearchCache();


            // Eager load relations for API response
            $booking->load(['service.category', 'service.user', 'service.event']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'booking' => $booking,
                    'invoice' => $invoice,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking create failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Failed to create booking',
                'error' => $e->getMessage()
            ];
        }
    }
    /**
     * Bulk create bookings for multiple catering services (same provider)
     * Validates that all services belong to the same provider and deducts stocks atomically.
     */
    public function createBulkCateringBookings(array $data, User $user): array
    {
        $orders = $data['orders'] ?? [];
        if (empty($orders) || !is_array($orders)) {
            return ['success' => false, 'message' => 'orders array is required', 'code' => 422];
        }

        // Load services and ensure all catering and same provider
        $serviceIds = collect($orders)->pluck('service_id')->unique()->values()->all();
        $services = Service::with(['user','catering'])->whereIn('id', $serviceIds)->get()->keyBy('id');
        if ($services->count() !== count($serviceIds)) {
            return ['success' => false, 'message' => 'One or more services not found', 'code' => 404];
        }
        // Ensure all are catering and same provider
        $providerId = null;
        foreach ($orders as $ord) {
            $svc = $services->get($ord['service_id']);
            if (!$svc || !$svc->catering) {
                return ['success' => false, 'message' => 'All orders must be catering services', 'code' => 422];
            }
            if ($providerId === null) { $providerId = $svc->user_id; }
            if ($svc->user_id !== $providerId) {
                return ['success' => false, 'message' => 'All catering orders must belong to the same provider', 'code' => 422];
            }
        }

        try {
            // Idempotency pre-check: if base key provided, derive per-order keys and return existing bookings to avoid duplicate errors
            $baseKey = $data['idempotency_key'] ?? null;
            if ($baseKey !== null) {
                $derivedKeys = [];
                foreach ($orders as $i => $_) { $derivedKeys[] = $baseKey . '-' . ($i+1); }
                $existing = Booking::where('user_id', $user->id)
                    ->whereIn('idempotency_key', $derivedKeys)
                    ->with('invoice')
                    ->get()
                    ->sortBy(function($b){ return $b->idempotency_key; })
                    ->values();
                if ($existing->isNotEmpty()) {
                    return [
                        'success' => true,
                        'message' => 'Bulk bookings already created (idempotent replay)',
                        'data' => [
                            'summary' => [
                                'total_amount' => $existing->sum('total'),
                                'tax_total' => $existing->sum('tax'),
                                'discount_total' => $existing->sum('discount'),
                                'count' => $existing->count(),
                            ],
                            'items' => $existing->map(fn($b) => ['booking' => $b, 'invoice' => $b->invoice])->all(),
                        ],
                    ];
                }
            }

            DB::beginTransaction();

            // Lock all catering rows to guarantee stock consistency
            $lockedCaterings = \App\Models\Catering::whereIn('service_id', $serviceIds)->lockForUpdate()->get()->keyBy('service_id');

            $created = [];
            $grandTotal = 0.0;
            $taxTotal = 0.0;
            $discountTotal = 0.0;
            $subtotalTotal = 0.0;

            foreach ($orders as $index => $ord) {
                $svc = $services->get($ord['service_id']);
                $cat = $lockedCaterings->get($svc->id);
                if (!$cat) { throw new \RuntimeException('Catering not found for service '.$svc->id); }

                // Normalize dates for events not applicable here; catering uses provided dates
                $start = $ord['start_date'] ?? now()->toDateTimeString();
                $end = $ord['end_date'] ?? now()->addHours(2)->toDateTimeString();

                // Capacity check for main meal
                $qty = max(1, (int)($ord['booking_details']['number_of_items'] ?? 0));

                // Overlapping bookings for capacity
                $overlapping = Booking::where('service_id', $svc->id)
                    ->whereIn('status', ['pending','confirmed','completed'])
                    ->where(function($q) use ($start, $end){ $q->whereBetween('start_date', [$start, $end])->orWhereBetween('end_date', [$start, $end]); })
                    ->lockForUpdate()->get();
                $used = $overlapping->sum(fn($b) => (int)($b->booking_details['number_of_items'] ?? 0));
                $cap = (int)($cat->available_stock ?? 0);
                if ($cap <= 0 || ($used + $qty) > $cap) {
                    throw new \RuntimeException('Requested items exceed available stock for service '.$svc->id);
                }

                // Validate add-ons stock if any + fulfillment method for catering using shared helpers
                try {
                    $this->validateCateringCapacityAndAddons($svc, $cat, $ord['booking_details'], $overlapping);
                    $this->normalizeAndValidateCateringFulfillment($svc, $ord['booking_details'], ($ord['fulfillment_method'] ?? ($data['fulfillment_method'] ?? null)), ($ord['address_id'] ?? ($data['address_id'] ?? null)), $user);
                } catch (\InvalidArgumentException $e) {
                    throw new \RuntimeException($e->getMessage());
                }


                // Calculate fees for this order
                $fees = $this->calculateBookingFees($svc, array_merge($ord['booking_details'], [
                    'start_date' => $start,
                    'end_date' => $end,
                ]));

                $grandTotal += (float)($fees['total_amount'] ?? 0);
                $taxTotal += (float)($fees['tax_amount'] ?? 0);
                // Merge common notes at root into booking_details if notes not provided per-order
                if (empty(($ord['booking_details']['notes'] ?? null)) && !empty($data['notes'])) {
                    $ord['booking_details']['notes'] = $data['notes'];
                }

                $discountTotal += (float)($fees['discount'] ?? 0);
                $subtotalTotal += (float)($fees['subtotal'] ?? 0);

                // Create booking row (status pending till payment captured)
                $booking = Booking::create([
                    'service_id' => $svc->id,
                    'user_id' => $user->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'booking_details' => $ord['booking_details'],
                    'status' => 'pending',
                    'tax' => $fees['tax_amount'],
                    'subtotal' => $fees['subtotal'],
                    'discount' => $fees['discount'],
                    'total' => $fees['total_amount'],
                    'currency' => $svc->price_currency ?? get_setting('default_service_currency','SAR'),
                    'payment_method' => $data['payment_method'],
                    'idempotency_key' => isset($data['idempotency_key']) ? ($data['idempotency_key'] . '-' . ($index+1)) : null,
                ]);

                // Deduct stock immediately in the same TX, guarded against negatives
                $this->deductCateringStock($cat, $ord['booking_details']);

                // No invoice per booking; we'll create one consolidated invoice for the order later
                $created[] = ['booking' => $booking];
            }

            // One payment for the total using PaymentService
            $payment = ['success' => true, 'transaction_id' => null];
            if ($grandTotal > 0) {
                $payment = $this->processPayment($user, $grandTotal, [
                    'payment_method' => $data['payment_method'],
                ]);
                if (!($payment['success'] ?? false)) {
                    throw new \RuntimeException($payment['message'] ?? 'Payment failed');
                }
            }

            // Create consolidated Order and link bookings via factory
            [$order, $orderNumber, $invoiceNumber] = app(\App\Services\Booking\Contracts\IOrderFactory::class)
                ->createForBulk($user, $created, $subtotalTotal, $taxTotal, $discountTotal, $grandTotal, $payment, $data['idempotency_key'] ?? null, $data['notes'] ?? null);

            // Create a consolidated invoice via factory
            $invoice = app(\App\Services\Booking\Contracts\IInvoiceFactory::class)->createForBulk(
                $user->id,
                $order->id,
                $invoiceNumber,
                $grandTotal,
                $taxTotal,
                $discountTotal,
                $created[0]['booking']->currency ?? get_setting('default_service_currency','SAR'),
                ($payment['success'] ?? false) ? 'paid' : 'pending',
                $data['payment_method'] ?? null,
                $payment['transaction_id'] ?? null
            );


            DB::commit();

            return [
                'success' => true,
                'message' => 'Bulk bookings created successfully',
                'data' => [
                    'summary' => [
                        'total_amount' => $grandTotal,
                        'tax_total' => $taxTotal,
                        'discount_total' => $discountTotal,
                        'count' => count($created),
                    ],
                    'order' => $order,
                    'items' => $created,
                ],
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Bulk catering booking failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to create bulk bookings',
                'error' => $e->getMessage(),
                'code' => 422,
            ];
        }
    }


    /**
     * التحقق من صحة الحجز
     */
    private function validateBooking(array $data, User $user, Service $service): array
    {
        // التحقق من أن المستخدم لا يحجز خدمته الخاصة
        if ($service->user_id === $user->id) {
            return [
                'valid' => false,
                'message' => 'You cannot book your own service',
                'code' => 403
            ];
        }

        // التحقق من توفر الخدمة (بالنسبة للفعاليات: استخدم start/end الخاصين بالحدث)
        $checkStart = $data['start_date'] ?? optional($service->event)->start_at?->toDateTimeString();
        $checkEnd = $data['end_date'] ?? optional($service->event)->end_at?->toDateTimeString();
        if (!$this->isServiceAvailable($service, (string)$checkStart, (string)$checkEnd)) {
            return [
                'valid' => false,
                'message' => 'Service is not available for the selected dates',
                'code' => 409
            ];
        }

        // التحقق من أن الخدمة معتمدة
        if (!$service->is_approved) {
            return [
                'valid' => false,
                'message' => 'Service is not approved yet',
                'code' => 403
            ];
        }

        // occupancy checks and required details based on service type
        if ($service->event) {
            $num = (int)($data['booking_details']['number_of_people'] ?? 0);
            if ($num < 1) {
                return ['valid' => false, 'message' => 'number_of_people is required and must be >= 1 for events', 'code' => 422];
            }
            if ($service->event->max_individuals && $num > $service->event->max_individuals) {
                return ['valid' => false, 'message' => 'Requested people exceed event capacity', 'code' => 422];
            }
        } elseif ($service->restaurant) {
            $num = (int)($data['booking_details']['number_of_people'] ?? 0);
            if ($num < 1) {
                return ['valid' => false, 'message' => 'number_of_people is required and must be >= 1 for restaurants', 'code' => 422];
            }
        } elseif ($service->catering) {
            $qty = (int)($data['booking_details']['number_of_items'] ?? 0);
            if ($qty < 1) {
                return ['valid' => false, 'message' => 'number_of_items is required and must be >= 1 for catering', 'code' => 422];
            }
            if ((int)$service->catering->available_stock !== null && (int)$qty > (int)$service->catering->available_stock) {
                return ['valid' => false, 'message' => 'Requested items exceed available stock', 'code' => 422];
            }
        } elseif ($service->property) {
            $adults = (int)($data['booking_details']['adults'] ?? 0);
            $children = (int)($data['booking_details']['children'] ?? 0);
            $childrenAges = $data['booking_details']['children_ages'] ?? [];

            if ($adults < 1) {
                return ['valid' => false, 'message' => 'At least 1 adult is required', 'code' => 422];
            }
            if (!empty($childrenAges) && count($childrenAges) !== $children) {
                return ['valid' => false, 'message' => 'children_ages length must match children count', 'code' => 422];
            }
            if ($service->property->max_adults && $adults > $service->property->max_adults) {
                return ['valid' => false, 'message' => 'Adults exceed property limit', 'code' => 422];
            }
            if ($service->property->max_children !== null && $children > $service->property->max_children) {
                return ['valid' => false, 'message' => 'Children exceed property limit', 'code' => 422];
            }
        }

        return ['valid' => true];
    }

    /**
     * التحقق من توفر الخدمة
     */
    public function isServiceAvailable(Service $service, string $startDate, string $endDate): bool
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // التحقق من الحجوزات الموجودة في نفس الفترة
        $existingBookings = Booking::where('service_id', $service->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            // FOR UPDATE applies when wrapped in an explicit transaction and lockForUpdate invoked by caller
            ->sharedLock() // prevent dirty reads while availability is being evaluated
            ->count();

        // التحقق من كتل عدم الإتاحة المحددة من المزود
        $blocked = \App\Models\AvailabilityBlock::where('service_id', $service->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                          ->where('end_date', '>=', $end);
                    });
            })
            ->sharedLock()
            ->exists();
        if ($blocked) { return false; }

        // enforce booking_hours and operating_hours if configured
        $hoursOk = function(array $hours, Carbon $start, Carbon $end): bool {
            if (empty($hours)) return true;
            $day = $start->format('l'); // Monday, Tuesday, ...
            $range = $hours[$day] ?? null;
            if (!$range) return false;
            // support formats like "09:00-22:00"
            if (is_string($range)) { $range = [$range]; }
            foreach ($range as $slot) {
                [$from, $to] = array_map('trim', explode('-', $slot));
                $fromDt = $start->copy()->setTimeFromTimeString($from);
                $toDt = $start->copy()->setTimeFromTimeString($to);
                if ($start->betweenIncluded($fromDt, $toDt) && $end->betweenIncluded($fromDt, $toDt)) {
                    return true;
                }
            }
            return false;
        };

        $serviceHours = [
            'operating' => $service->operating_hours ?? [],
            'booking' => $service->booking_hours ?? [],
        ];
        // if booking_hours defined, must fit inside it; otherwise require operating_hours if defined
        if (!empty($serviceHours['booking'])) {
            if (!$hoursOk((array)$serviceHours['booking'], $start, $end)) { return false; }
        } elseif (!empty($serviceHours['operating'])) {
            if (!$hoursOk((array)$serviceHours['operating'], $start, $end)) { return false; }
        }
        // مطاعم: نسمح مبدئياً ونطبق التحقق الدقيق لاحقاً في createBooking
        if ($service->restaurant) { return true; }

        // غير المطاعم: قارن الحد الأقصى العام
        $maxBookings = $this->getMaxBookingsForService($service);
        return $existingBookings < $maxBookings;

    }

    /**
     * التحقق من وجود حجوزات متداخلة للمستخدم
     */
    public function userHasOverlappingBooking(User $user, string $startDate, string $endDate): bool
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return Booking::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            ->exists();
    }

    /**
     * الحصول على الحد الأقصى للحجوزات حسب نوع الخدمة
     */
    private function getMaxBookingsForService(Service $service): int
    {
        if ($service->event) {
            return $service->event->max_individuals ?? 1;
        } elseif ($service->restaurant) {
            return $service->restaurant->daily_available_bookings ?? 10;
        } elseif ($service->property) {
            return 1; // عقار واحد فقط
        } elseif ($service->cateringItem) {
            return 100; // عدد كبير للخدمات
        }

        return 1;
    }

    /**
     * حساب رسوم الحجز (مع إعادة هيكلة باستخدام Strategy)
     */
    public function calculateBookingFees(Service $service, array $details): array
    {
        $discount = 0.0; // discounts (coupons/points) are applied by callers, keep at 0 here
        $orderValue = 0.0; // amount coming from attached order (already tax-included) — currently unused

        // Delegate subtotal computation to pricing strategies
        $calculator = app(\App\Domain\Booking\Pricing\FeeCalculator::class);
        $subtotal = (float) $calculator->subtotal($service, $details);

        // الضرائب والعمولات كما كانت
        $taxRate = get_setting('tax_rate', 15);
        $taxAmount = ($subtotal * $taxRate) / 100;

        $commissionService = app(\App\Services\CommissionService::class);
        $tempBooking = new \App\Models\Booking([
            'subtotal' => $subtotal,
            'service_id' => $service->id,
        ]);
        $tempBooking->setRelation('service', $service);
        $commissionData = $commissionService->calculateCommission($tempBooking);

        $totalAmount = $subtotal + $taxAmount + $orderValue - $discount;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'commission_data' => $commissionData,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Normalize and validate catering fulfillment details; mutates $details to include method/address.
     * Throws InvalidArgumentException on invalid input.
     */
    private function normalizeAndValidateCateringFulfillment(Service $service, array &$details, ?string $fulfillment, $addressId, User $user): void
    {
        if (!$service->catering) { return; }
        $f = strtolower((string)($fulfillment ?? ''));
        $supported = (array)($service->catering->fulfillment_methods ?? []);
        $supported = array_values(array_filter(array_map('strtolower', is_array($supported) ? $supported : [])));
        if (empty($supported)) {
            $hasDelivery = \App\Models\CateringItem::where('catering_id', $service->catering->id)
                ->where('delivery_included', 1)->exists();
            $supported = $hasDelivery ? ['delivery','pickup'] : ['pickup'];
        }
        $valid = ['delivery','pickup','on_site'];
        if (!in_array($f, $valid, true)) {
            throw new \InvalidArgumentException('Invalid fulfillment method');
        }
        if (!in_array($f, $supported, true)) {
            throw new \InvalidArgumentException('Selected fulfillment method not supported by provider');
        }
        if ($f === 'delivery') {
            $addr = \App\Models\UserAddress::find((int)$addressId);
            if (!$addr || (int)$addr->user_id !== (int)$user->id) {
                throw new \InvalidArgumentException('Invalid delivery address');
            }
            $details['delivery_address_id'] = (int)$addr->id;
        }
        $details['fulfillment_method'] = $f;
    }

    /**
     * Validate capacity and add-ons stock for catering based on overlapping bookings; throws on violations.
     */
    private function validateCateringCapacityAndAddons(Service $lockedService, $lockedCatering, array $details, $overlappingBookings): void
    {
        if (!$lockedService->catering) { return; }
        $req = (int)($details['number_of_items'] ?? 0);
        if ($req <= 0) { throw new \RuntimeException('Requested items must be greater than 0'); }

        $used = $overlappingBookings->sum(function($b){ return (int)($b->booking_details['number_of_items'] ?? 0); });
        $cap = (int)($lockedCatering->available_stock ?? $lockedService->catering->available_stock ?? 0);
        if ($cap <= 0 || ($used + $req) > $cap) {
            throw new \RuntimeException('Requested items exceed available stock');
        }

        if (!empty($details['add_ons']) && is_array($details['add_ons'])) {
            foreach ($details['add_ons'] as $ao) {
                $itemId = (int)($ao['id'] ?? 0); $qty = max(0, (int)($ao['qty'] ?? 0));
                if ($itemId > 0 && $qty > 0) {
                    $addon = \App\Models\CateringItem::where('id', $itemId)
                        ->where('catering_id', $lockedService->catering->id)->first();
                    if ($addon) {
                        $usedAddon = $overlappingBookings->sum(function($b) use ($itemId){
                            $arr = $b->booking_details['add_ons'] ?? [];
                            if (!is_array($arr)) return 0;
                            $sum = 0;
                            foreach ($arr as $x) { if ((int)($x['id'] ?? 0) === $itemId) { $sum += (int)($x['qty'] ?? 0); } }
                            return $sum;
                        });
                        $capAddon = (int)($addon->available_stock ?? 0);
                        if ($capAddon > 0 && ($usedAddon + $qty) > $capAddon) {
                            throw new \RuntimeException('Add-on stock exceeded for item ID ' . $itemId);
                        }
                    }
                }
            }
        }
    }

    /**
     * Deduct catering stock atomically under current transaction; throws on insufficient stock.
     */
    private function deductCateringStock(\App\Models\Catering $lockedCatering, array $details): void
    {
        $mainReq = (int)($details['number_of_items'] ?? 0);
        if ($mainReq > 0) {
            $affected = \App\Models\Catering::where('id', $lockedCatering->id)
                ->where('available_stock', '>=', $mainReq)
                ->decrement('available_stock', $mainReq);
            if ($affected === 0) { throw new \RuntimeException('Insufficient main meal stock'); }
        }
        if (!empty($details['add_ons']) && is_array($details['add_ons'])) {
            foreach ($details['add_ons'] as $ao) {
                $itemId = (int)($ao['id'] ?? 0); $qty = max(0, (int)($ao['qty'] ?? 0));
                if ($itemId > 0 && $qty > 0) {
                    $addon = \App\Models\CateringItem::where('id', $itemId)
                        ->where('catering_id', $lockedCatering->id)
                        ->lockForUpdate()->first();
                    if ($addon) {
                        if ((int)$addon->available_stock < $qty) {
                            throw new \RuntimeException('Insufficient add-on stock for item ID ' . $itemId);
                        }
                        $addon->decrement('available_stock', $qty);
                    }
                }
            }
        }
    }


    /**
     * معالجة الدفع
     */
    private function processPayment(User $user, float $amount, array $data): array
    {
        $paymentService = app(PaymentService::class);

        // Merge calculated amount with original payment payload (card tokens, etc.)
        $payload = array_merge($data, ['amount' => $amount]);

        return $paymentService->charge($payload, $user);
    }

    /**
     * @deprecated Use order-level invoice creation inside createBooking/createBulkCateringBookings
     */
    private function createInvoice(Booking $booking, array $fees): Invoice
    {
        // Deprecated: kept for backward compatibility if any legacy flow calls it.
        // Prefer creating a single invoice per Order.
        $invoice = Invoice::create([
            'user_id' => $booking->user_id,
            'order_id' => $booking->order_id,
            'booking_id' => $booking->order_id ? null : $booking->id,
            'total_amount' => $fees['total_amount'],
            'tax_amount' => $fees['tax_amount'],
            'discount_amount' => $fees['discount'],
            'commission_amount' => $fees['commission_data']['total_commission'] ?? 0,
            'provider_amount' => $fees['commission_data']['provider_amount'] ?? 0,
            'platform_amount' => $fees['commission_data']['platform_amount'] ?? 0,
            'currency' => $booking->currency,
            'invoice_type' => 'customer',
            'status' => 'pending',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Booking for ' . $booking->service->name,
            'quantity' => 1,
            'unit_price' => $fees['subtotal'],
            'total' => $fees['subtotal'],
            'tax_rate' => $fees['tax_rate'] ?? 0,
            'tax_amount' => $fees['tax_amount'] ?? 0,
        ]);

        return $invoice;
    }

    /**
     * إرسال الإشعارات
     */
    private function sendBookingNotifications(Booking $booking, Service $service): void
    {
        // إشعار للعميل
        $this->notificationService->created([
            'user_id' => $booking->user_id,
            'action' => 'booking_confirmed',
            'message' => 'تم تأكيد حجزك بنجاح. يمكنك الاطلاع على الفاتورة.',
        ]);

        // إشعار لمقدم الخدمة
        $this->notificationService->created([
            'user_id' => $service->user_id,
            'action' => 'new_booking',
            'message' => 'لديك حجز جديد على خدمتك ' . $service->name . '.',
        ]);

        // منح النقاط للعميل
        $pointsService = app(\App\Services\PointsService::class);
        $pointsService->awardBookingPoints($booking);

        // منح النقاط لمقدم الخدمة
        $pointsService->awardProviderBookingPoints($booking);
    }

    /**
     * تحديث حالة الحجز
     */
    public function updateBookingStatus(Booking $booking, string $status, ?string $notes = null): array
    {
        try {
            DB::beginTransaction();

            $booking->update([
                'status' => $status,
                'notes' => $notes,
            ]);

            // بدلاً من إرسال الإشعار مباشرة، نعتمد على حدث يتم التقاطه عبر Listener
            DB::commit();
            // Dispatch event after commit to ensure decoupled notifications
            event(new \App\Events\BookingStatusUpdated($booking));

            return [
                'success' => true,
                'message' => 'Booking status updated successfully',
                'data' => $booking
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update booking status',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * إلغاء الحجز
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): array
    {
        if ($booking->status === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Booking is already cancelled'
            ];
        }

        try {
            DB::beginTransaction();

            // Compute refund via policy engine
            $cancellationService = app(\App\Services\CancellationService::class);
            $refund = $cancellationService->computeRefund($booking);

            $booking->update([
                'status' => 'cancelled',
                'notes' => $reason,


            ]);

            // Refund if any and payment was from wallet
            if ($booking->payment_method === 'wallet' && ($refund['refund_amount'] ?? 0) > 0) {
                $booking->user->deposit($refund['refund_amount'], [
                    'description' => "Refund ({$refund['refund_percent']}%) for cancelled booking #{$booking->id}",
                    'booking_id' => $booking->id,
                ]);
            }

            event(new \App\Events\BookingCancelled($booking, $reason));

            DB::commit();

            return [
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => [
                    'booking' => $booking,
                    'refund' => $refund,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to cancel booking',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على حجوزات المستخدم
     */
    public function getUserBookings(User $user, array $filters = []): array
    {
        $query = Booking::where('user_id', $user->id)
            ->with(['service.category', 'service.user', 'service.event']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        $bookings = $query->latest()->paginate(10);

        return [
            'success' => true,
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ]
        ];
    }

    /**
     * Find existing booking by idempotency key for a user under lock.
     */
    private function findExistingIdempotentBooking(User $user, string $key): ?Booking
    {
        return Booking::where('user_id', $user->id)
            ->where('idempotency_key', $key)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Schedule standard booking reminders (24h and 3h before start) and queue jobs.
     */
    private function scheduleReminders(Booking $booking): void
    {
        $start = \Carbon\Carbon::parse($booking->start_date);
        $reminders = [
            ['type' => '24h', 'at' => $start->copy()->subHours(24)],
            ['type' => '3h', 'at' => $start->copy()->subHours(3)],
        ];
        foreach ($reminders as $r) {
            if ($r['at']->isFuture()) {
                $rem = \App\Models\BookingReminder::create([
                    'booking_id' => $booking->id,
                    'type' => $r['type'],
                    'scheduled_at' => $r['at'],
                ]);
                \App\Jobs\SendBookingReminderJob::dispatch($rem->id)->delay($r['at']);
            }
        }
    }

    /**
     * Link payment transaction to booking and hold funds in escrow if applicable.
     */
    private function linkPaymentTransactionAndEscrow(Booking $booking, array $paymentResult): void
    {
        if (!empty($paymentResult['transaction_id'])) {
            $tx = \App\Models\PaymentTransaction::where('transaction_id', $paymentResult['transaction_id'])->first();
            if ($tx) {
                $tx->update(['booking_id' => $booking->id]);
                try {
                    app(\App\Services\EscrowService::class)->holdFundsNetForBooking($booking, $tx);
                } catch (\Throwable $e) {
                    \Log::warning('Escrow hold failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Bump search cache version to reflect changes immediately without waiting for TTL.
     */
    private function bumpSearchCache(): void
    {
        try { \App\Support\CacheVersion::bump('search'); } catch (\Throwable $e) { /* ignore */ }
    }

}
