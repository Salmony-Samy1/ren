<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $apiKey;
    private string $from;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.sms.api_key') ?? '');
        $this->from = (string) (config('services.sms.from') ?? 'App');
        $this->baseUrl = (string) (config('services.sms.base_url') ?? '');
    }

    /**
     * Build provider API URL supporting base URLs with or without trailing /api
     * Examples:
     *  - base: https://host => https://host/api/{endpoint}
     *  - base: https://host/api => https://host/api/{endpoint}
     */
    private function providerApiUrl(string $endpoint): string
    {
        $base = rtrim($this->baseUrl, '/');
        $ep = ltrim($endpoint, '/');
        if (substr($base, -4) === '/api') {
            return $base . '/' . $ep;
        }
        return $base . '/api/' . $ep;
    }


    /**
     * إرسال رسالة SMS
     */
    public function sendSms(string $to, string $message, ?string $templateId = null): array
    {
        try {
            $payload = [
                'to' => $this->formatPhoneNumber($to),
                'message' => $message,
                'from' => $this->from,
            ];

            if ($templateId) {
                $payload['template_id'] = $templateId;
            }

            // Provider-agnostic simple GET API (configured via services.sms.base_url)
            $response = Http::timeout(10)->get($this->providerApiUrl('send.aspx'), [
                'apikey' => $this->apiKey,
                'language' => $this->detectLanguage($message),
                'sender' => $this->from,
                'mobile' => ltrim($payload['to'], '+'),
                'message' => $message,
            ]);
            Log::info('SMS sent successfully', ['to'=>$to,'body'=>$response->body()]);

            // Parse plain-text response: OK,... or error,reason
            $body = trim((string) $response->body());
            if (!$response->ok()) {
                Log::error('SMS sending failed (HTTP)', ['to'=>$to,'status'=>$response->status()]);
                return ['success'=>false,'error'=>'SMS service temporarily unavailable','status_code'=>$response->status()];
            }
            if (stripos($body, 'error,') === 0) {
                $reason = trim(substr($body, strlen('error,')));
                Log::error('SMS sending failed (provider)', ['to'=>$to,'reason'=>$reason]);
                return ['success'=>false,'error'=>'SMS service temporarily unavailable'];
            }
            Log::info('SMS sent successfully', ['to'=>$to,'body'=>$body]);
            return ['success'=>true,'status'=>'sent'];
        } catch (\Exception $e) {
            Log::error('SMS service error', ['to'=>$to,'error'=>$e->getMessage()]);
            return [
                'success' => false,
                'error' => 'SMS service temporarily unavailable'
            ];
        }
    }

    private function detectLanguage(string $message): int
    {
        $hasArabic = preg_match('/[\x{0600}-\x{06FF}]/u', $message) === 1;
        if ($hasArabic) return 2; // Arabic
        // Assume GSM-7 english if only basic ASCII present
        if (preg_match('/^[\x20-\x7E\r\n]+$/', $message) === 1) return 1;
        return 3; // Unicode
    }

    /**
     * إرسال رمز التحقق OTP
     */
    public function sendOtp(string $phone, string $otp): array
    {
        $message = "رمز التحقق الخاص بك هو: {$otp}. صالح لمدة 10 دقائق.";

        return $this->sendSms($phone, $message, 'otp_template');
    }

    /**
     * إرسال إشعار تأكيد الحجز
     */
    public function sendBookingConfirmation(string $phone, array $bookingData): array
    {
        $message = "تم تأكيد حجزك بنجاح! رقم الحجز: {$bookingData['booking_id']}. المبلغ: {$bookingData['amount']} ريال.";

        return $this->sendSms($phone, $message, 'booking_confirmation_template');
    }

    /**
     * إرسال إشعار إلغاء الحجز
     */
    public function sendBookingCancellation(string $phone, array $bookingData): array
    {
        $message = "تم إلغاء حجزك رقم: {$bookingData['booking_id']}. سيتم إرجاع المبلغ خلال 3-5 أيام عمل.";

        return $this->sendSms($phone, $message, 'booking_cancellation_template');
    }

    /**
     * إرسال إشعار تذكير بالحجز
     */
    public function sendBookingReminder(string $phone, array $bookingData): array
    {
        $message = "تذكير: لديك حجز غداً في {$bookingData['service_name']}. الوقت: {$bookingData['time']}.";

        return $this->sendSms($phone, $message, 'booking_reminder_template');
    }

    /**
     * إرسال إشعار تحديث حالة الحجز
     */
    public function sendBookingStatusUpdate(string $phone, array $bookingData): array
    {
        $message = "تم تحديث حالة حجزك رقم {$bookingData['booking_id']} إلى: {$bookingData['status']}.";

        return $this->sendSms($phone, $message, 'status_update_template');
    }

    /**
     * إرسال إشعار للمزود بحجز جديد
     */
    public function sendNewBookingNotification(string $phone, array $bookingData): array
    {
        $message = "لديك حجز جديد! رقم الحجز: {$bookingData['booking_id']}. العميل: {$bookingData['customer_name']}.";

        return $this->sendSms($phone, $message, 'new_booking_template');
    }

    /**
     * إرسال إشعار إيداع المحفظة
     */
    public function sendWalletDepositNotification(string $phone, array $walletData): array
    {
        $message = "تم إيداع {$walletData['amount']} ريال في محفظتك. الرصيد الجديد: {$walletData['balance']} ريال.";

        return $this->sendSms($phone, $message, 'wallet_deposit_template');
    }

    /**
     * إرسال إشعار سحب من المحفظة
     */
    public function sendWalletWithdrawalNotification(string $phone, array $walletData): array
    {
        $message = "تم سحب {$walletData['amount']} ريال من محفظتك. الرصيد الجديد: {$walletData['balance']} ريال.";

        return $this->sendSms($phone, $message, 'wallet_withdrawal_template');
    }

    /**
     * إرسال إشعار نقاط جديدة
     */
    public function sendPointsNotification(string $phone, array $pointsData): array
    {
        $message = "تم منحك {$pointsData['points']} نقاط! النقاط الإجمالية: {$pointsData['total_points']}.";

        return $this->sendSms($phone, $message, 'points_notification_template');
    }

    /**
     * إرسال إشعار إحالة
     */
    public function sendReferralNotification(string $phone, array $referralData): array
    {
        $message = "تم تسجيل مستخدم جديد برمز إحالتك! تم منحك {$referralData['points']} نقاط.";

        return $this->sendSms($phone, $message, 'referral_notification_template');
    }

    /**
     * إرسال إشعار موافقة على الخدمة
     */
    public function sendServiceApprovalNotification(string $phone, array $serviceData): array
    {
        $message = "تمت الموافقة على خدمتك '{$serviceData['service_name']}'. يمكنك الآن استقبال الحجوزات.";

        return $this->sendSms($phone, $message, 'service_approval_template');
    }

    /**
     * إرسال إشعار رفض الخدمة
     */
    public function sendServiceRejectionNotification(string $phone, array $serviceData): array
    {
        $message = "تم رفض خدمتك '{$serviceData['service_name']}'. السبب: {$serviceData['reason']}.";

        return $this->sendSms($phone, $message, 'service_rejection_template');
    }

    /**
     * تنسيق رقم الهاتف
     */
    private function formatPhoneNumber(string $phone): string
    {
        // إزالة جميع الأحرف غير الرقمية
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // احتفاظ بالصيغة الدولية إن وُجدت
        if (str_starts_with($digits, '966') && strlen($digits) >= 12) return '+'.$digits;
        if (str_starts_with($digits, '973') && strlen($digits) >= 11) return '+'.$digits;

        // السعودية: 9 أرقام تبدأ بـ 5، أو 10 تبدأ بصفر ثم 5
        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            return '+966'.$digits;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '05')) {
            return '+966'.substr($digits, 1);
        }

        // البحرين: 8 أرقام
        if (strlen($digits) === 8) {
            return '+973'.$digits;
        }

        // fallback: إعادة كما هي مع +
        return '+'.$digits;
    }

    /**
     * التحقق من حالة الرسالة
     */
    public function checkMessageStatus(string $messageId): array
    {
        try {
            // Provider does not offer a dedicated status endpoint in the plain-text spec.
            // We return not_supported to clarify behavior without failing the API.
            return [
                'success' => false,
                'error' => 'not_supported'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error checking message status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على رصيد SMS
     */
    public function getBalance(): array
    {
        try {
            $response = Http::timeout(10)->get($this->providerApiUrl('balance.aspx'), [
                'apikey' => $this->apiKey,
            ]);
            $body = trim((string) $response->body());
            Log::info('SMS balance check', ['body'=>$response]);
            if (!$response->ok()) {
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $response->status() . ': ' . $body,
                    'status_code' => $response->status()
                ];
            }
            if (stripos($body, 'error,') === 0) {
                $reason = trim(substr($body, strlen('error,')));
                return [
                    'success' => false,
                    'error' => $reason ?: 'provider_error'
                ];
            }
            return [
                'success' => true,
                'balance' => $body
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error getting balance: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إرسال SMS مجمعة
     */
    public function sendBulkSms(array $recipients, string $message, ?string $templateId = null): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->sendSms($recipient['phone'], $message, $templateId);

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

        return [
            'success' => true,
            'total_sent' => count($recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }
}
