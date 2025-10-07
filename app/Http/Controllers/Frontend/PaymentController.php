<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
        $this->middleware('auth:api');
    }

    /**
     * معالجة الدفع
     */
    public function processPayment(ProcessPaymentRequest $request)
    {
        $result = $this->paymentService->charge($request->validated(), auth()->user());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Payment processed successfully',
                'data' => [
                    'transaction_id' => $result['transaction_id'] ?? null,
                    'status' => $result['status'] ?? 'confirmed',
                ]
            ]);
        }

        // Handle gateways requiring user action (e.g. Tap 3DS/Benefit redirect)
        if (!empty($result['requires_action'])) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Additional action required',
                'data' => [
                    'requires_action' => true,
                    'redirect_url' => $result['redirect_url'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? null,
                    'status' => $result['status'] ?? 'INITIATED',
                ],
            ], 202);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Payment failed',
            'error' => $result['error'] ?? null
        ], $result['code'] ?? 400);
    }

    /**
     * عرض معاملات الدفع للمستخدم
     */
    public function getUserTransactions(Request $request)
    {
        $query = PaymentTransaction::where('user_id', auth()->id())
            ->with(['booking.service']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $transactions = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    /**
     * عرض معاملة دفع محددة
     */
    public function showTransaction(PaymentTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction->load(['booking.service', 'user']);

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * الحصول على إعدادات بوابة الدفع
     */
    public function getGatewaySettings(Request $request)
    {
        $gateway = $request->gateway;
        
        if (!in_array($gateway, ['apple_pay', 'visa', 'mada', 'samsung_pay', 'benefit', 'stcpay'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid gateway'
            ], 400);
        }

        $settings = $this->paymentService->getGatewaySettings($gateway);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
