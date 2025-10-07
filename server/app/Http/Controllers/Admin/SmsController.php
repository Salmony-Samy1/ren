<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    public function __construct(private readonly SmsService $smsService)
    {
    }

    /**
     * عرض إعدادات SMS
     */
    public function settings()
    {
        $settings = [
            'api_key' => config('services.sms.api_key'),
            'api_secret' => config('services.sms.api_secret'),
            'from' => config('services.sms.from'),
            'base_url' => config('services.sms.base_url'),
            'gateway' => config('services.sms.gateway'),
        ];

        return format_response(true, 'تم جلب إعدادات SMS بنجاح', $settings);
    }

    /**
     * تحديث إعدادات SMS
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'from' => 'required|string|max:11',
            'base_url' => 'required|url',
            'gateway' => 'required|in:testing,twilio,vonage,other',
        ]);

        if ($validator->fails()) {
            return format_response(false, 'بيانات غير صحيحة', $validator->errors(), 422);
        }

        // تحديث ملف .env
        $this->updateEnvFile([
            'SMS_API_KEY' => $request->api_key,
            'SMS_FROM' => $request->from,
            'SMS_BASE_URL' => $request->base_url,
            'SMS_GATEWAY' => $request->gateway,
        ]);

        return format_response(true, 'تم تحديث إعدادات SMS بنجاح');
    }

    /**
     * إرسال SMS تجريبي
     */
    public function sendTestSms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:160',
        ]);

        if ($validator->fails()) {
            return format_response(false, 'بيانات غير صحيحة', $validator->errors(), 422);
        }

        $result = $this->smsService->sendSms($request->phone, $request->message);

        if ($result['success']) {
            return format_response(true, 'تم إرسال SMS بنجاح', $result);
        }

        return format_response(false, 'فشل في إرسال SMS', $result, 500);
    }

    /**
     * الحصول على رصيد SMS
     */
    public function getBalance()
    {
        $result = $this->smsService->getBalance();

        if ($result['success']) {
            return format_response(true, 'تم جلب رصيد SMS بنجاح', $result['balance']);
        }

        return format_response(false, 'فشل في جلب رصيد SMS', $result, 500);
    }

    /**
     * إرسال SMS مجمعة
     */
    public function sendBulkSms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*.phone' => 'required|string',
            'message' => 'required|string|max:160',
            'template_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return format_response(false, 'بيانات غير صحيحة', $validator->errors(), 422);
        }

        $result = $this->smsService->sendBulkSms(
            $request->recipients,
            $request->message,
            $request->template_id
        );

        return format_response(true, 'تم إرسال SMS المجمعة بنجاح', $result);
    }

    /**
     * التحقق من حالة رسالة
     */
    public function checkMessageStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return format_response(false, 'بيانات غير صحيحة', $validator->errors(), 422);
        }

        $result = $this->smsService->checkMessageStatus($request->message_id);

        if ($result['success']) {
            return format_response(true, 'تم جلب حالة الرسالة بنجاح', $result['status']);
        }

        return format_response(false, __('sms.fetch_status_failed'), $result, 500);
    }

    /**
     * إرسال إشعارات مخصصة
     */
    public function sendCustomNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:booking_confirmation,booking_cancellation,booking_reminder,wallet_deposit,wallet_withdrawal,points,referral,service_approval,service_rejection',
            'recipients' => 'required|array|min:1',
            'recipients.*.phone' => 'required|string',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return format_response(false, __('validation.invalid_data'), $validator->errors(), 422);
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($request->recipients as $recipient) {
            $result = $this->sendNotificationByType(
                $request->type,
                $recipient['phone'],
                $request->data
            );

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }

            $results[] = [
                'phone' => $recipient['phone'],
                'result' => $result
            ];
        }

        return format_response(true, 'تم إرسال الإشعارات بنجاح', [
            'total_sent' => count($request->recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ]);
    }

    /**
     * إرسال إشعار حسب النوع
     */
    private function sendNotificationByType(string $type, string $phone, array $data): array
    {
        return match($type) {
            'booking_confirmation' => $this->smsService->sendBookingConfirmation($phone, $data),
            'booking_cancellation' => $this->smsService->sendBookingCancellation($phone, $data),
            'booking_reminder' => $this->smsService->sendBookingReminder($phone, $data),
            'wallet_deposit' => $this->smsService->sendWalletDepositNotification($phone, $data),
            'wallet_withdrawal' => $this->smsService->sendWalletWithdrawalNotification($phone, $data),
            'points' => $this->smsService->sendPointsNotification($phone, $data),
            'referral' => $this->smsService->sendReferralNotification($phone, $data),
            'service_approval' => $this->smsService->sendServiceApprovalNotification($phone, $data),
            'service_rejection' => $this->smsService->sendServiceRejectionNotification($phone, $data),
            default => ['success' => false, 'error' => 'نوع إشعار غير معروف'],
        };
    }

    /**
     * تحديث ملف .env
     */
    private function updateEnvFile(array $data): void
    {
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            return;
        }

        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }
}
