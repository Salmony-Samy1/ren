<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
        // تعطيل CSRF protection للـ webhooks
        $this->middleware('web');
    }

    /**
     * معالجة Webhook من Apple Pay
     */
    public function applePay(Request $request)
    {
        Log::info('Apple Pay webhook received', $request->all());
        
        $result = $this->paymentService->handleWebhook('apple_pay', $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * معالجة Webhook من Visa
     */
    public function visa(Request $request)
    {
        Log::info('Visa webhook received', $request->all());
        
        $result = $this->paymentService->handleWebhook('visa', $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * معالجة Webhook من Mada
     */
    public function mada(Request $request)
    {
        Log::info('Mada webhook received', $request->all());
        
        $result = $this->paymentService->handleWebhook('mada', $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * معالجة Webhook من Samsung Pay
     */
    public function samsungPay(Request $request)
    {
        Log::info('Samsung Pay webhook received', $request->all());
        
        $result = $this->paymentService->handleWebhook('samsung_pay', $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * معالجة Webhook من Benefit
     */
    public function benefit(Request $request)
    {
        Log::info('Benefit webhook received', $request->all());
        
        $result = $this->paymentService->handleWebhook('benefit', $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * معالجة Webhook من STC Pay
     */
    public function stcPay(Request $request)
    {
        Log::info('STC Pay webhook received', $request->all());
        
        $result = $this->paymentService->handleWebhook('stcpay', $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * معالجة Webhook عام (للاختبار)
     */
    public function generic(Request $request, string $gateway)
    {
        Log::info("Generic webhook received for gateway: {$gateway}", $request->all());
        
        $result = $this->paymentService->handleWebhook($gateway, $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
