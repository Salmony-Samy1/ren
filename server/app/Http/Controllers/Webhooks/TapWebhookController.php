<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\Payments\TapGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TapWebhookController extends Controller
{
    public function __construct(private readonly TapGatewayService $tapGatewayService)
    {
        // تعطيل CSRF للـ webhooks
        $this->middleware('throttle:60,1');
    }

    /**
     * معالجة webhooks من Tap
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $rawBody = $request->getContent();
            $payload = $request->all();
            $signature = $request->header('X-Tap-Signature');

            // التحقق من صحة التوقيع باستخدام raw body
            if (!$this->tapGatewayService->verifyWebhookSignature($rawBody, $signature)) {
                Log::channel('webhook')->warning('Invalid Tap webhook signature', [
                    'payload_hash' => hash('sha256', $rawBody),
                    'signature' => $signature,
                    'ip' => $request->ip()
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $eventType = $payload['type'] ?? 'unknown';
            $chargeId = $payload['id'] ?? null;

            Log::info('Tap webhook received', [
                'event_type' => $eventType,
                'charge_id' => $chargeId,
                'payload' => $payload
            ]);

            // البحث عن المعاملة
            $transaction = PaymentTransaction::where('transaction_id', $chargeId)->first();
            
            if (!$transaction) {
                Log::warning('Transaction not found for Tap webhook', [
                    'charge_id' => $chargeId,
                    'event_type' => $eventType
                ]);
                
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // معالجة الحدث حسب النوع
            switch ($eventType) {
                case 'charge.authorized':
                    $this->handleChargeAuthorized($transaction, $payload);
                    break;
                    
                case 'charge.captured':
                    $this->handleChargeCaptured($transaction, $payload);
                    break;
                    
                case 'charge.failed':
                    $this->handleChargeFailed($transaction, $payload);
                    break;
                    
                case 'charge.voided':
                    $this->handleChargeVoided($transaction, $payload);
                    break;
                    
                case 'charge.refunded':
                    $this->handleChargeRefunded($transaction, $payload);
                    break;
                    
                default:
                    Log::info('Unhandled Tap webhook event', [
                        'event_type' => $eventType,
                        'charge_id' => $chargeId
                    ]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Tap webhook processing error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * معالجة حدث تفويض الشحنة
     */
    private function handleChargeAuthorized(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'authorized',
            'gateway_response' => $payload
        ]);

        Log::info('Transaction authorized', [
            'transaction_id' => $transaction->id,
            'charge_id' => $payload['id']
        ]);
    }

    /**
     * معالجة حدث تأكيد الشحنة
     */
    private function handleChargeCaptured(PaymentTransaction $transaction, array $payload): void
    {
        DB::beginTransaction();
        
        try {
            $transaction->update([
                'status' => 'completed',
                'gateway_response' => $payload
            ]);

            // فحص metadata لتحديد نوع العملية
            $metadata = $transaction->metadata ?? [];
            $transactionType = $metadata['type'] ?? 'unknown';

            switch ($transactionType) {
                case 'booking':
                    $this->handleBookingPayment($transaction);
                    break;
                    
                case 'wallet_topup':
                    $this->handleWalletTopUp($transaction, $metadata);
                    break;
                    
                default:
                    Log::warning('Unknown transaction type in webhook', [
                        'transaction_id' => $transaction->id,
                        'type' => $transactionType,
                        'metadata' => $metadata
                    ]);
            }

            DB::commit();

            Log::info('Transaction captured successfully', [
                'transaction_id' => $transaction->id,
                'charge_id' => $payload['id'],
                'type' => $transactionType
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process charge captured', [
                'transaction_id' => $transaction->id,
                'charge_id' => $payload['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * معالجة دفع الحجز
     */
    private function handleBookingPayment(PaymentTransaction $transaction): void
    {
        if ($transaction->booking_id) {
            $booking = $transaction->booking;
            if ($booking) {
                $booking->update(['status' => 'confirmed']);
                event(new \App\Events\BookingStatusUpdated($booking));
                
                Log::info('Booking confirmed via webhook', [
                    'booking_id' => $booking->id,
                    'transaction_id' => $transaction->id
                ]);
            }
        }
    }

    /**
     * معالجة شحن المحفظة
     */
    private function handleWalletTopUp(PaymentTransaction $transaction, array $metadata): void
    {
        $user = $transaction->user;
        $amount = $metadata['topup_amount'] ?? $transaction->amount;
        $paymentMethod = $transaction->payment_method;

        // إيداع المبلغ في المحفظة
        $walletService = app(\App\Services\WalletService::class);
        $walletService->deposit($user, $amount);

        // تسجيل معاملة المحفظة
        $user->walletTransactions()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'description' => 'Wallet top-up via ' . $paymentMethod,
            'metadata' => [
                'payment_method' => $paymentMethod,
                'transaction_id' => $transaction->transaction_id,
                'gateway' => 'tap',
                'webhook_confirmed' => true
            ]
        ]);

        // إرسال إشعار للمستخدم
        app(\App\Services\NotificationService::class)->created([
            'user_id' => $user->id,
            'action' => 'wallet_topup_completed',
            'message' => "تم شحن محفظتك بمبلغ {$amount} بنجاح",
            'metadata' => [
                'amount' => $amount,
                'transaction_id' => $transaction->transaction_id,
                'payment_method' => $paymentMethod
            ]
        ]);

        Log::info('Wallet topped up via webhook', [
            'user_id' => $user->id,
            'amount' => $amount,
            'transaction_id' => $transaction->id,
            'payment_method' => $paymentMethod
        ]);
    }

    /**
     * معالجة حدث فشل الشحنة
     */
    private function handleChargeFailed(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'failed',
            'gateway_response' => $payload
        ]);

        Log::info('Transaction failed', [
            'transaction_id' => $transaction->id,
            'charge_id' => $payload['id'],
            'failure_reason' => $payload['failure_reason'] ?? 'Unknown'
        ]);
    }

    /**
     * معالجة حدث إلغاء الشحنة
     */
    private function handleChargeVoided(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'voided',
            'gateway_response' => $payload
        ]);

        Log::info('Transaction voided', [
            'transaction_id' => $transaction->id,
            'charge_id' => $payload['id']
        ]);
    }

    /**
     * معالجة حدث استرداد الشحنة
     */
    private function handleChargeRefunded(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'refunded',
            'gateway_response' => $payload
        ]);

        Log::info('Transaction refunded', [
            'transaction_id' => $transaction->id,
            'charge_id' => $payload['id'],
            'refund_amount' => $payload['refund_amount'] ?? 0
        ]);
    }
}
