<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\WalletTopUpRequest;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PaymentService $paymentService
    ) {
    }

    /**
     * عرض رصيد المحفظة والمعاملات
     */
    public function index()
    {
        $user = auth()->user();
        $transactions = $this->walletService->transactions($user);

        $lastMonthStartDate = \Carbon\Carbon::now()->subMonth();

        $paid_last_month = $transactions->where('type', 'deposit')
                                        ->where('created_at', '>=', $lastMonthStartDate)
                                        ->sum('amount');

        $withdrawn_last_month = abs($transactions->where('type', 'withdraw')
                                                 ->where('created_at', '>=', $lastMonthStartDate)
                                                 ->sum('amount'));

        $data = [
            'balance' => $this->walletService->getBalance($user),
            'paid_last_month' => (float) $paid_last_month,
            'withdrawn_last_month' => (float) $withdrawn_last_month,
            'transactions' => $transactions
        ];
        
        return response()->json([
            'success' => true,
            'message' => __('Fetched successfully'),
            'data' => $data,
        ]);
    }

    /**
     * شحن رصيد المحفظة باستخدام نظام الدفع Tap
     */
    public function topUp(WalletTopUpRequest $request)
    {
        $user = auth()->user();
        $amount = $request->validated()['amount'];
        $paymentMethod = $request->validated()['payment_method'];

        try {
            // التحقق من الحد الأقصى للشحن
            $maxTopUp = get_setting('max_wallet_topup_amount', 10000);
            if ($amount > $maxTopUp) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum top-up amount is {$maxTopUp}",
                    'code' => 422
                ], 422);
            }

            // التحقق من الحد الأدنى للشحن
            $minTopUp = get_setting('min_wallet_topup_amount', 10);
            if ($amount < $minTopUp) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimum top-up amount is {$minTopUp}",
                    'code' => 422
                ], 422);
            }

            // إعداد بيانات الدفع مع metadata للشحن
            $paymentData = array_merge($request->validated(), [
                'metadata' => [
                    'type' => 'wallet_topup',
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'topup_amount' => $amount,
                    'currency' => $request->validated()['currency'] ?? 'SAR'
                ]
            ]);

            // معالجة الدفع
            $paymentResult = $this->paymentService->charge($paymentData, $user);

            if (!$paymentResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message'] ?? 'Payment failed',
                    'error' => $paymentResult['error'] ?? null
                ], $paymentResult['code'] ?? 500);
            }

            // التحقق من حالة الدفع
            if (isset($paymentResult['status'])) {
                switch ($paymentResult['status']) {
                    case 'requires_action':
                        // إعادة توجيه للدفع (مثل Benefit)
                        return response()->json([
                            'success' => false,
                            'message' => 'Payment requires additional action',
                            'data' => [
                                'requires_action' => true,
                                'redirect_url' => $paymentResult['redirect_url'],
                                'transaction_id' => $paymentResult['transaction_id'],
                                'status' => 'payment_pending'
                            ]
                        ], 202);
                        
                    case 'completed':
                        // الدفع نجح فوراً - إيداع فوري
                        DB::beginTransaction();
                        try {
                            $this->walletService->deposit($user, $amount);
                            
                            // تسجيل معاملة المحفظة
                            $user->walletTransactions()->create([
                                'type' => 'deposit',
                                'amount' => $amount,
                                'description' => 'Wallet top-up via ' . $paymentMethod,
                                'metadata' => [
                                    'payment_method' => $paymentMethod,
                                    'transaction_id' => $paymentResult['transaction_id'],
                                    'gateway' => 'tap'
                                ]
                            ]);
                            
                            DB::commit();
                            
                            return response()->json([
                                'success' => true,
                                'message' => 'Wallet topped up successfully',
                                'data' => [
                                    'new_balance' => $this->walletService->getBalance($user),
                                    'amount_added' => $amount,
                                    'transaction_id' => $paymentResult['transaction_id']
                                ]
                            ], 201);
                            
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error('Wallet top-up deposit failed', [
                                'user_id' => $user->id,
                                'amount' => $amount,
                                'error' => $e->getMessage()
                            ]);
                            
                            return response()->json([
                                'success' => false,
                                'message' => 'Payment succeeded but wallet update failed. Please contact support.',
                                'code' => 500
                            ], 500);
                        }
                        
                    case 'pending':
                        // الدفع معلق (في انتظار webhook)
                        return response()->json([
                            'success' => true,
                            'message' => 'Payment is being processed. Wallet will be updated once confirmed.',
                            'data' => [
                                'transaction_id' => $paymentResult['transaction_id'],
                                'status' => 'pending',
                                'amount' => $amount
                            ]
                        ], 202);
                }
            }

            // الحالة الافتراضية
            return response()->json([
                'success' => true,
                'message' => 'Wallet top-up initiated',
                'data' => [
                    'transaction_id' => $paymentResult['transaction_id'],
                    'status' => $paymentResult['status'] ?? 'unknown'
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Wallet top-up error', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Wallet top-up failed',
                'error' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * تحويل الأموال بين المستخدمين
     */
    public function transfer(\App\Http\Requests\WalletTransferRequest $request)
    {
        $sender = auth()->user();
        
        $receiver = app(\App\Repositories\UserRepo\IUserRepo::class)
            ->getAll(columns: ['id'], filter: ['public_id' => $request->public_id], query_builder: true)
            ->first();
            
        if (!$receiver) {
            return response()->json([
                'success' => false,
                'message' => 'Receiver User not found',
                'code' => 400
            ], 400);
        }

        $currency = $request->input('currency') ?: (optional($sender->wallet)->currency ?? get_setting('default_wallet_currency','SAR'));
        $balance = $this->walletService->transfer($sender, $receiver, $request->amount, 'user_transfer', $currency);
        
        app(\App\Services\NotificationService::class)->created([
            'user_id' => $receiver->id,
            'action' => 'wallet_transfer_received',
            'message' => 'تم إضافة مبلغ ' . $request->amount . ' إلى محفظتك من المستخدم ' . $sender->full_name,
        ]);
        
        app(\App\Services\NotificationService::class)->created([
            'user_id' => $sender->id,
            'action' => 'wallet_transfer_sent',
            'message' => 'تم تحويل مبلغ ' . $request->amount . ' بنجاح إلى محفظة المستخدم ' . $receiver->full_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transferred successfully',
            'data' => ['balance' => $balance]
        ]);
    }

    /**
     * شحن المحفظة (deposit method للـ charge route)
     */
    public function deposit(WalletTopUpRequest $request)
    {
        // استخدام نفس logic الخاص بـ topUp method
        return $this->topUp($request);
    }

    /**
     * الحصول على إعدادات المحفظة
     */
    public function getSettings()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'min_topup_amount' => get_setting('min_wallet_topup_amount', 10),
                'max_topup_amount' => get_setting('max_wallet_topup_amount', 10000),
                'default_currency' => get_setting('default_wallet_currency', 'SAR'),
                'supported_payment_methods' => [
                    'tap_card',
                    'tap_apple_pay',
                    'tap_google_pay',
                    'tap_benefit',
                    'tap_benefitpay'
                ]
            ]
        ]);
    }
}