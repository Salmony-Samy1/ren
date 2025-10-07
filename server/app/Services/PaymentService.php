<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\PaymentTransaction;
use App\Models\SavedCard;
use App\Services\Payments\TapGatewayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Contracts\Payments\PaymentGatewayInterface;

class PaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly TapGatewayService $tapGatewayService
    ) {
    }

    /**
     * معالجة الدفع
     */
    public function charge(array $data, User $user): array
    {
        $escrowService = app(\App\Services\EscrowService::class);
        $paymentMethod = $data['payment_method'];
        $amount = $data['amount'];
        $bookingId = $data['booking_id'] ?? null;

        try {
            // إنشاء Idempotency Key لمنع التكرار
            $idempotencyKey = $data['idempotency_key'] ?? $this->generateIdempotencyKey($data);
            
            // التحقق من عدم تكرار العملية
            $existingTransaction = $this->checkIdempotency($idempotencyKey);
            if ($existingTransaction) {
                return [
                    'success' => true,
                    'message' => 'Payment already processed',
                    'transaction_id' => $existingTransaction->transaction_id,
                    'status' => $existingTransaction->status
                ];
            }

            DB::beginTransaction();

            // إنشاء معاملة دفع مع metadata
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'gateway_response' => null,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $data['metadata'] ?? null, // حفظ metadata للاستخدام في webhook
            ]);

            // معالجة الدفع حسب الطريقة
            $result = match ($paymentMethod) {
                'wallet' => $this->processWalletPayment($user, $amount, $transaction),
                'test' => $this->processTestPayment($user, $amount, $transaction),
                'apple_pay' => $this->processApplePayPayment($data, $transaction),
                'visa' => $this->processVisaPayment($data, $transaction),
                'mada' => $this->processMadaPayment($data, $transaction),
                'samsung_pay' => $this->processSamsungPayPayment($data, $transaction),
                'benefit' => $this->processBenefitPayment($data, $transaction),
                'stcpay' => $this->processSTCPayPayment($data, $transaction),
                // طرق الدفع الجديدة مع Tap
                'tap_card' => $this->processTapCardPayment($data, $transaction),
                'tap_benefit' => $this->processTapBenefitPayment($data, $transaction),
                'tap_apple_pay' => $this->processTapApplePayPayment($data, $transaction),
                'tap_google_pay' => $this->processTapGooglePayPayment($data, $transaction),
                'tap_benefitpay' => $this->processTapBenefitPayPayment($data, $transaction),
                default => ['success' => false, 'message' => 'Unsupported payment method']
            };

            if ($result['success']) {
                $transaction->update([
                    'status' => 'completed',
                    'currency' => $data['currency'] ?? (optional($transaction->booking)->service->price_currency ?? get_setting('default_service_currency','SAR')),
                    'gateway_response' => $result['gateway_response'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? null,
                ]);

                // تحديث حالة الحجز إذا كان موجود
                if ($bookingId) {
                    $booking = Booking::find($bookingId);
                    if ($booking) {
                        $booking->update(['status' => 'confirmed']);
                        event(new \App\Events\BookingStatusUpdated($booking));
                    }

	                // Hold only provider net in escrow and send platform commission now
	                if ($bookingId && isset($booking)) {
	                    $escrowService->holdFundsNetForBooking($booking, $transaction);
	                }

                }

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'transaction_id' => $result['transaction_id'] ?? $transaction->id,
                    'status' => 'confirmed'
                ];
            }

            $transaction->update([
                'status' => 'failed',
                'gateway_response' => $result['message'] ?? null,
            ]);

            DB::rollBack();

            return $result;

            // When gateway requires user action (e.g., 3DS redirect), keep transaction pending and return redirect
            if (!empty($result['requires_action'])) {
                $transaction->update([
                    'status' => 'pending',
                    'currency' => $data['currency'] ?? (optional($transaction->booking)->service->price_currency ?? get_setting('default_service_currency','SAR')),
                    'gateway_response' => $result['gateway_response'] ?? ($result['raw'] ?? null),
                    'transaction_id' => $result['transaction_id'] ?? null,
                ]);
                DB::commit();
                return array_merge($result, [ 'status' => $result['status'] ?? 'INITIATED' ]);
            }


        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function processTapPayment(array $data, PaymentTransaction $transaction, string $mode): array
    {
        $tap = app(\App\Services\Payments\TapGatewayService::class);
        $payload = [
            'amount' => (float) $data['amount'],
            'currency' => $data['currency'] ?? (optional($transaction->booking)->service->price_currency ?? get_setting('default_service_currency','SAR')),
            'reference' => [ 'transaction' => (string) $transaction->id ],
            'metadata' => [ 'booking_id' => $transaction->booking_id, 'user_id' => $transaction->user_id ],
            'save_card' => (bool) ($data['save_card'] ?? false),
        ];

        if ($mode === 'token') {
            $payload['source'] = [ 'id' => $data['tap_token'] ];
        } else { // source mode
            $payload['source'] = [ 'id' => $data['tap_source'] ];
        }

        // Customer info if available (for saving cards)
        $user = \App\Models\User::find($transaction->user_id);
        if ($user) {
            $payload['customer'] = [
                'first_name' => $user->full_name ?? $user->name ?? 'Customer',
                'email' => $user->email,
                'phone' => [ 'country_code' => $user->country_code ?? 'SA', 'number' => $user->phone ?? '' ],
            ];
        }

        $result = $tap->createCharge($payload);
        if ($result['success']) {
            return [
                'success' => true,
                'transaction_id' => $result['transaction_id'] ?? uniqid('tap_'),
                'gateway_response' => $result['raw'] ?? null,
            ];
        }
        return $result;
    }

    /**
     * معالجة الدفع التجريبي (للفحص والاختبار)
     */
    private function processTestPayment(User $user, float $amount, PaymentTransaction $transaction): array
    {
        // التحقق من أن هذا في بيئة التطوير أو الاختبار
        if (app()->environment('production') && !config('app.debug')) {
            return [
                'success' => false,
                'message' => 'Test payment method is only available in development environment',
                'error' => 'TEST_MODE_ONLY'
            ];
        }

        // التحقق من أن هذا شحن محفظة وليس دفع
        $metadata = $transaction->metadata;
        if (!isset($metadata['type']) || $metadata['type'] !== 'wallet_topup') {
            return [
                'success' => false,
                'message' => 'Test payment can only be used for wallet top-up',
                'error' => 'INVALID_OPERATION'
            ];
        }

        // شحن المحفظة مباشرة (بدون دفع حقيقي)
        try {
            $walletService = app(\App\Services\WalletService::class);
            $walletService->deposit($user, $amount);
            
            // تحديث حالة المعاملة
            $transaction->update([
                'status' => 'completed',
                'gateway_response' => json_encode([
                    'type' => 'test_payment',
                    'amount' => $amount,
                    'new_balance' => $walletService->getBalance($user),
                    'test_mode' => true,
                    'message' => 'This is a test payment - no real money was charged'
                ])
            ]);

            return [
                'success' => true,
                'message' => 'Test payment completed successfully - Wallet topped up',
                'transaction_id' => $transaction->transaction_id,
                'status' => 'completed',
                'amount' => $amount,
                'new_balance' => $walletService->getBalance($user),
                'test_mode' => true
            ];

        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'failed',
                'gateway_response' => json_encode(['error' => $e->getMessage()])
            ]);

            return [
                'success' => false,
                'message' => 'Test payment failed: ' . $e->getMessage(),
                'error' => 'TEST_PAYMENT_FAILED'
            ];
        }
    }

    /**
     * معالجة الدفع من المحفظة
     */
    private function processWalletPayment(User $user, float $amount, PaymentTransaction $transaction): array
    {
        // Verify wallet payments are enabled via settings
        if (!get_setting('wallet_payments_enabled', true)) {
            return ['success' => false, 'message' => 'Wallet payments are disabled'];
        }

        // Convert currencies if needed: user's wallet currency -> service currency
        $serviceCurrency = optional($transaction->booking)->service->price_currency ?? null;
        $walletCurrency = strtoupper(optional($user->wallet)->currency ?? '');
        if ($walletCurrency === '' || $walletCurrency === 'DEFAULT') {
            $walletCurrency = (string) get_setting('default_wallet_currency','SAR');
        }
        $amountToWithdraw = $amount;
        if ($serviceCurrency && $walletCurrency && strtoupper($serviceCurrency) !== strtoupper($walletCurrency)) {
            $converted = app(\App\Services\ExchangeRateService::class)->convert($amount, $serviceCurrency, $walletCurrency);
            if ($converted === null) {
                return ['success' => false, 'message' => 'Exchange rate not configured'];
            }
            $amountToWithdraw = $converted;
        }

        // Ensure user has sufficient balance in wallet currency
        if ($user->balance < $amountToWithdraw) {
            return [
                'success' => false,
                'message' => __('Insufficient wallet balance'),
                'error' => 'INSUFFICIENT_FUNDS'
            ];
        }

        // Withdraw from customer to a clearing account (admin-configured) to avoid double-transfer later
        // Fallback: use escrow_system_user_id when wallet_clearing_user_id is not configured (tests seed escrow only)
        $clearingUserId = (int) get_setting('wallet_clearing_user_id');
        if ($clearingUserId <= 0) {
            $clearingUserId = (int) get_setting('escrow_system_user_id');
        }
        if ($clearingUserId <= 0) {
            return ['success' => false, 'message' => 'Wallet clearing account not configured'];
        }
        $clearing = \App\Models\User::find($clearingUserId);
        if (!$clearing) {
            return ['success' => false, 'message' => 'Wallet clearing user not found'];
        }

        // Use transfer to clearing to reflect capture; later EscrowService will route funds to escrow/admin
        $user->transfer($clearing, $amountToWithdraw, [
            'description' => 'Order payment (wallet) captured to clearing',
            'payment_transaction_id' => $transaction->id,
            'service_currency' => $serviceCurrency,
            'wallet_currency' => $walletCurrency,
            'amount_service_currency' => $amount,
            'amount_wallet_currency' => $amountToWithdraw,
        ]);

        return [
            'success' => true,
            'transaction_id' => uniqid('wallet_'),
            'gateway_response' => 'Wallet payment successful (captured to clearing)'
        ];
    }

    /**
     * معالجة الدفع عبر Apple Pay
     */
    private function processApplePayPayment(array $data, PaymentTransaction $transaction): array
    {
        // هنا يتم ربط Apple Pay SDK
        $applePayToken = $data['apple_pay_token'] ?? null;

        if (!$applePayToken) {
            return [
                'success' => false,
                'message' => 'Apple Pay token is required'
            ];
        }

        // محاكاة معالجة Apple Pay
        $success = $this->simulateGatewayPayment('apple_pay', $data);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => uniqid('apple_'),
                'gateway_response' => 'Apple Pay payment successful'
            ];
        }

        return [
            'success' => false,
            'message' => 'Apple Pay payment failed'
        ];
    }

    /**
     * معالجة الدفع عبر Visa
     */
    private function processVisaPayment(array $data, PaymentTransaction $transaction): array
    {
        $cardNumber = $data['card_number'] ?? null;
        $expiryDate = $data['expiry_date'] ?? null;
        $cvv = $data['cvv'] ?? null;

        if (!$cardNumber || !$expiryDate || !$cvv) {
            return [
                'success' => false,
                'message' => 'Card details are required'
            ];
        }

        // محاكاة معالجة Visa
        $success = $this->simulateGatewayPayment('visa', $data);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => uniqid('visa_'),
                'gateway_response' => 'Visa payment successful'
            ];
        }

        return [
            'success' => false,
            'message' => 'Visa payment failed'
        ];
    }

    /**
     * معالجة الدفع عبر Mada
     */
    private function processMadaPayment(array $data, PaymentTransaction $transaction): array
    {
        $cardNumber = $data['card_number'] ?? null;
        $expiryDate = $data['expiry_date'] ?? null;
        $cvv = $data['cvv'] ?? null;

        if (!$cardNumber || !$expiryDate || !$cvv) {
            return [
                'success' => false,
                'message' => 'Card details are required'
            ];
        }

        // محاكاة معالجة Mada
        $success = $this->simulateGatewayPayment('mada', $data);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => uniqid('mada_'),
                'gateway_response' => 'Mada payment successful'
            ];
        }

        return [
            'success' => false,
            'message' => 'Mada payment failed'
        ];
    }

    /**
     * معالجة الدفع عبر Samsung Pay
     */
    private function processSamsungPayPayment(array $data, PaymentTransaction $transaction): array
    {
        $samsungPayToken = $data['samsung_pay_token'] ?? null;

        if (!$samsungPayToken) {
            return [
                'success' => false,
                'message' => 'Samsung Pay token is required'
            ];
        }

        // محاكاة معالجة Samsung Pay
        $success = $this->simulateGatewayPayment('samsung_pay', $data);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => uniqid('samsung_'),
                'gateway_response' => 'Samsung Pay payment successful'
            ];
        }

        return [
            'success' => false,
            'message' => 'Samsung Pay payment failed'
        ];
    }

    /**
     * معالجة الدفع عبر Benefit
     */
    private function processBenefitPayment(array $data, PaymentTransaction $transaction): array
    {
        $phoneNumber = $data['phone_number'] ?? null;
        $otp = $data['otp'] ?? null;

        if (!$phoneNumber || !$otp) {
            return [
                'success' => false,
                'message' => 'Phone number and OTP are required'
            ];
        }

        // محاكاة معالجة Benefit
        $success = $this->simulateGatewayPayment('benefit', $data);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => uniqid('benefit_'),
                'gateway_response' => 'Benefit payment successful'
            ];
        }

        return [
            'success' => false,
            'message' => 'Benefit payment failed'
        ];
    }

    /**
     * معالجة الدفع عبر STC Pay
     */
    private function processSTCPayPayment(array $data, PaymentTransaction $transaction): array
    {
        $phoneNumber = $data['phone_number'] ?? null;
        $otp = $data['otp'] ?? null;

        if (!$phoneNumber || !$otp) {
            return [
                'success' => false,
                'message' => 'Phone number and OTP are required'
            ];
        }

        // محاكاة معالجة STC Pay
        $success = $this->simulateGatewayPayment('stcpay', $data);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => uniqid('stcpay_'),
                'gateway_response' => 'STC Pay payment successful'
            ];
        }

        return [
            'success' => false,
            'message' => 'STC Pay payment failed'
        ];
    }

    /**
     * محاكاة معالجة بوابة الدفع
     */
    private function simulateGatewayPayment(string $gateway, array $data): bool
    {
        // في البيئة الحقيقية، هنا يتم الاتصال ببوابة الدفع الفعلية
        // حالياً نستخدم محاكاة بسيطة

        $successRate = get_setting($gateway . '_success_rate', 90); // 90% نجاح افتراضياً

        return rand(1, 100) <= $successRate;
    }

    /**
     * معالجة Webhook من بوابات الدفع
     */
    public function handleWebhook(string $gateway, array $payload): array
    {
        try {
            // التحقق من صحة Webhook
            if (!$this->verifyWebhookSignature($gateway, $payload)) {
                return [
                    'success' => false,
                    'message' => 'Invalid webhook signature'
                ];
            }

            $transactionId = $payload['transaction_id'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$transactionId || !$status) {
                return [
                    'success' => false,
                    'message' => 'Missing required webhook data'
                ];
            }

            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'Transaction not found'
                ];
            }

            // تحديث حالة المعاملة
            $transaction->update([
                'status' => $status,
                'gateway_response' => json_encode($payload),
            ]);

            // تحديث حالة الحجز إذا كان موجود
            if ($transaction->booking_id) {
                $booking = Booking::find($transaction->booking_id);
                if ($booking) {
                    $bookingStatus = $status === 'completed' ? 'confirmed' : 'pending';
                    $booking->update(['status' => $bookingStatus]);
                    event(new \App\Events\BookingStatusUpdated($booking));
                }
            }

            // إرسال إشعار للمستخدم
            $this->notificationService->created([
                'user_id' => $transaction->user_id,
                'action' => 'payment_' . $status,
                'message' => 'Payment status updated to: ' . $status,
            ]);

            return [
                'success' => true,
                'message' => 'Webhook processed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * التحقق من صحة توقيع Webhook
     */
    public function verifyWebhookSignature(string $gateway, array $payload): bool
    {
        // HMAC verification with per-gateway secret.
        // Expect headers: 'X-Gateway-Signature' base64(hmac_sha256(body, secret)) and optional 'X-Gateway-Timestamp'
        try {
            $request = request();
            $signatureHeader = $request->header('X-Gateway-Signature');
            $timestamp = $request->header('X-Gateway-Timestamp');
            $rawBody = $request->getContent();

            if (!$signatureHeader || empty($rawBody)) {
                return false;
            }

            // Optional: replay protection (5 minutes)
            if ($timestamp && (abs(time() - (int)$timestamp) > 300)) {
                return false;
            }

            $secret = (string) get_setting($gateway . '_webhook_secret', '');
            if ($secret === '') {
                // If not configured, reject in production
                if (app()->environment('production')) {
                    return false;
                }
                // In non-prod, allow only when explicitly enabled for testing
                if (!get_setting('webhook_signature_allow_insecure', false)) {
                    return false;
                }
            }

            $computed = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
            // Support format: scheme=HMAC-SHA256, signature=...
            $provided = $signatureHeader;
            if (str_contains($signatureHeader, 'signature=')) {
                // parse key=value pairs
                $parts = [];
                foreach (explode(',', $signatureHeader) as $kv) {
                    $pair = explode('=', trim($kv), 2);
                    if (count($pair) === 2) { $parts[$pair[0]] = $pair[1]; }
                }
                $provided = $parts['signature'] ?? '';
            }

            return hash_equals($computed, $provided);
        } catch (\Throwable $e) {
            \Log::warning('Webhook signature verification failed: '.$e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على إعدادات بوابة الدفع
     */
    public function getGatewaySettings(string $gateway): array
    {
        return [
            'enabled' => get_setting($gateway . '_enabled', false),
            'api_key' => get_setting($gateway . '_api_key', ''),
            'secret_key' => get_setting($gateway . '_secret_key', ''),
            'webhook_url' => get_setting($gateway . '_webhook_url', ''),
            'success_rate' => get_setting($gateway . '_success_rate', 90),
        ];
    }

    /**
     * تحديث إعدادات بوابة الدفع
     */
    public function updateGatewaySettings(string $gateway, array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $gateway . '_' . $key],
                    ['value' => $value]
                );
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update gateway settings: ' . $e->getMessage());
            return false;
        }
    }

    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        // Simulated refund logic
        if ($amount <= 0 || $amount > (float) $transaction->amount) {
            return ['success' => false, 'message' => 'Invalid refund amount'];
        }
        // In a real gateway, call refund API here and persist response
        return [
            'success' => true,
            'message' => 'Refund processed (simulated)',
            'transaction_id' => $transaction->transaction_id,
            'gateway_response' => 'refund_ok',
        ];
    }

    public function createCustomerProfile(User $user, array $options = []): array
    {
        // إنشاء عميل في Tap
        return $this->tapGatewayService->createCustomer($user);
    }

    // ========================================
    // TAP PAYMENT METHODS
    // ========================================

    /**
     * معالجة دفع البطاقات الائتمانية مع Tap
     */
    private function processTapCardPayment(array $data, PaymentTransaction $transaction): array
    {
        try {
            // التأكد من وجود customer_id
            $customerId = $data['customer_id'] ?? $this->getOrCreateCustomer(auth()->user());
            
            $result = $this->tapGatewayService->processCardPayment([
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'tap_token' => $data['tap_token'],
                'customer_id' => $customerId,
                'save_card' => $data['save_card'] ?? false,
                'booking_id' => $data['booking_id'] ?? null,
            ], $transaction);

            // حفظ البطاقة إذا تم طلب ذلك
            if ($result['success'] && ($data['save_card'] ?? false)) {
                $this->saveCardFromResponse($result['raw'], auth()->user(), $customerId);
            }

            // تسجيل محاولة فاشلة للبطاقة إذا فشلت العملية
            if (!$result['success'] && isset($result['error_code'])) {
                $this->handleFailedCardAttempt($result['error_code']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::channel('payment')->error('Tap card payment error', [
                'user_id' => auth()->id(),
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Card payment failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * معالجة دفع Apple Pay مع Tap
     */
    private function processTapApplePayPayment(array $data, PaymentTransaction $transaction): array
    {
        try {
            $customerId = $data['customer_id'] ?? $this->getOrCreateCustomer(auth()->user());
            
            return $this->tapGatewayService->processApplePayPayment([
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'tap_token' => $data['tap_token'],
                'customer_id' => $customerId,
                'booking_id' => $data['booking_id'] ?? null,
            ], $transaction);

        } catch (\Exception $e) {
            Log::error('Tap Apple Pay payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Apple Pay payment failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * معالجة دفع Benefit مع Tap
     */
    private function processTapBenefitPayment(array $data, PaymentTransaction $transaction): array
    {
        try {
            $customerId = $data['customer_id'] ?? $this->getOrCreateCustomer(auth()->user());
            
            return $this->tapGatewayService->processBenefitPayment([
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'BHD',
                'customer_id' => $customerId,
                'phone_number' => $data['phone_number'] ?? auth()->user()->phone,
                'booking_id' => $data['booking_id'] ?? null,
            ], $transaction);

        } catch (\Exception $e) {
            Log::error('Tap Benefit payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Benefit payment failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * معالجة دفع BenefitPay مع Tap
     */
    private function processTapBenefitPayPayment(array $data, PaymentTransaction $transaction): array
    {
        try {
            $customerId = $data['customer_id'] ?? $this->getOrCreateCustomer(auth()->user());
            
            return $this->tapGatewayService->processBenefitPayPayment([
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'BHD',
                'tap_token' => $data['tap_token'],
                'customer_id' => $customerId,
                'phone_number' => $data['phone_number'] ?? auth()->user()->phone,
                'booking_id' => $data['booking_id'] ?? null,
            ], $transaction);

        } catch (\Exception $e) {
            Log::error('Tap BenefitPay payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'BenefitPay payment failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * معالجة دفع Google Pay مع Tap
     */
    private function processTapGooglePayPayment(array $data, PaymentTransaction $transaction): array
    {
        try {
            $customerId = $data['customer_id'] ?? $this->getOrCreateCustomer(auth()->user());
            
            return $this->tapGatewayService->processGooglePayPayment([
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'tap_token' => $data['tap_token'],
                'customer_id' => $customerId,
                'booking_id' => $data['booking_id'] ?? null,
            ], $transaction);

        } catch (\Exception $e) {
            Log::error('Tap Google Pay payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Google Pay payment failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على أو إنشاء عميل في Tap
     */
    private function getOrCreateCustomer(User $user): string
    {
        // البحث عن عميل موجود
        $existingCard = SavedCard::where('user_id', $user->id)->first();
        if ($existingCard) {
            return $existingCard->customer_id;
        }

        // إنشاء عميل جديد
        $result = $this->tapGatewayService->createCustomer($user);
        if ($result['success']) {
            return $result['customer_id'];
        }

        throw new \Exception('Failed to create customer: ' . ($result['message'] ?? 'Unknown error'));
    }

    /**
     * حفظ البطاقة من استجابة Tap
     */
    private function saveCardFromResponse(array $tapResponse, User $user, string $customerId): void
    {
        try {
            $card = $tapResponse['card'] ?? null;
            if (!$card) return;

            DB::transaction(function () use ($card, $user, $customerId) {
                SavedCard::create([
                    'user_id' => $user->id,
                    'customer_id' => $customerId,
                    'card_id' => $card['id'],
                    'last_four' => $card['last_four'],
                    'brand' => $card['brand'],
                    'expiry_month' => $card['expiry_month'],
                    'expiry_year' => $card['expiry_year'],
                    'tap_response' => $card,
                ]);
            });

            Log::channel('payment')->info('Card saved successfully', [
                'user_id' => $user->id,
                'card_id' => $card['id'],
                'brand' => $card['brand']
            ]);

        } catch (\Exception $e) {
            Log::channel('payment')->error('Failed to save card', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * معالجة محاولات البطاقة الفاشلة
     */
    private function handleFailedCardAttempt(string $errorCode): void
    {
        // تسجيل محاولة فاشلة للبطاقة
        if (in_array($errorCode, ['INVALID_CARD', 'CARD_DECLINED', 'INVALID_CVV', 'INVALID_EXPIRY'])) {
            PaymentRateLimitMiddleware::recordFailedCardAttempt(request()->ip());
        }

        Log::channel('security')->warning('Failed card attempt', [
            'error_code' => $errorCode,
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * التحقق من عدم تكرار العملية (Idempotency)
     */
    private function checkIdempotency(string $idempotencyKey): ?PaymentTransaction
    {
        return PaymentTransaction::where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * إنشاء Idempotency Key فريد
     */
    private function generateIdempotencyKey(array $data): string
    {
        $keyData = [
            'user_id' => auth()->id(),
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'SAR',
            'payment_method' => $data['payment_method'],
            'booking_id' => $data['booking_id'] ?? null,
            'timestamp' => now()->timestamp
        ];

        return 'payment_' . hash('sha256', json_encode($keyData));
    }
}
