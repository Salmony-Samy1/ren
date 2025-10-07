<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NationalIdVerificationService
{
    protected $apiKey;
    protected $baseUrl;
    protected $gateway;

    public function __construct()
    {
        $this->apiKey = config('services.national_id.api_key');
        $this->baseUrl = config('services.national_id.base_url');
        $this->gateway = config('services.national_id.gateway', 'absher');
    }

    /**
     * التحقق من صحة الهوية الوطنية
     */
    public function verify(string $nationalId, string $fullName, string $dateOfBirth = null): array
    {
        try {
            // التحقق من التخزين المؤقت أولاً
            $cacheKey = "national_id_verification_{$nationalId}";
            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                if ($cached['expires_at'] > now()) {
                    return $cached['data'];
                }
                Cache::forget($cacheKey);
            }

            // التحقق من صحة التنسيق
            if (!$this->validateFormat($nationalId)) {
                return [
                    'success' => false,
                    'message' => 'تنسيق الهوية الوطنية غير صحيح',
                    'error_code' => 'INVALID_FORMAT'
                ];
            }

            // إرسال طلب التحقق
            $response = $this->sendVerificationRequest($nationalId, $fullName, $dateOfBirth);
            
            if ($response['success']) {
                // تخزين النتيجة في التخزين المؤقت لمدة 24 ساعة
                Cache::put($cacheKey, [
                    'data' => $response,
                    'expires_at' => now()->addHours(24)
                ], now()->addHours(24));
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('National ID verification error', [
                'national_id' => $nationalId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من الهوية الوطنية',
                'error_code' => 'VERIFICATION_ERROR'
            ];
        }
    }

    /**
     * التحقق من صحة تنسيق الهوية الوطنية
     */
    protected function validateFormat(string $nationalId): bool
    {
        // الهوية الوطنية السعودية تتكون من 10 أرقام
        if (strlen($nationalId) !== 10) {
            return false;
        }

        // يجب أن تكون أرقام فقط
        if (!ctype_digit($nationalId)) {
            return false;
        }

        // التحقق من صحة الرقم الأول (يجب أن يكون 1 أو 2)
        $firstDigit = (int) $nationalId[0];
        if (!in_array($firstDigit, [1, 2])) {
            return false;
        }

        return true;
    }

    /**
     * إرسال طلب التحقق
     */
    protected function sendVerificationRequest(string $nationalId, string $fullName, string $dateOfBirth = null): array
    {
        switch ($this->gateway) {
            case 'absher':
                return $this->verifyWithAbsher($nationalId, $fullName, $dateOfBirth);
            
            case 'nitaqat':
                return $this->verifyWithNitaqat($nationalId, $fullName, $dateOfBirth);
            
            case 'testing':
                return $this->mockVerification($nationalId, $fullName, $dateOfBirth);
            
            default:
                return [
                    'success' => false,
                    'message' => 'بوابة التحقق غير مدعومة',
                    'error_code' => 'UNSUPPORTED_GATEWAY'
                ];
        }
    }

    /**
     * التحقق عبر بوابة أبشر
     */
    protected function verifyWithAbsher(string $nationalId, string $fullName, string $dateOfBirth = null): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/verify', [
                'national_id' => $nationalId,
                'full_name' => $fullName,
                'date_of_birth' => $dateOfBirth
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'verified' => $data['verified'] ?? false,
                    'data' => $data,
                    'gateway' => 'absher'
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل في الاتصال ببوابة أبشر',
                'error_code' => 'ABSHER_ERROR'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال ببوابة أبشر',
                'error_code' => 'ABSHER_CONNECTION_ERROR'
            ];
        }
    }

    /**
     * التحقق عبر بوابة نطاقات
     */
    protected function verifyWithNitaqat(string $nationalId, string $fullName, string $dateOfBirth = null): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/verify', [
                'national_id' => $nationalId,
                'full_name' => $fullName,
                'date_of_birth' => $dateOfBirth
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'verified' => $data['verified'] ?? false,
                    'data' => $data,
                    'gateway' => 'nitaqat'
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل في الاتصال ببوابة نطاقات',
                'error_code' => 'NITAQAT_ERROR'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال ببوابة نطاقات',
                'error_code' => 'NITAQAT_CONNECTION_ERROR'
            ];
        }
    }

    /**
     * محاكاة التحقق للاختبار
     */
    protected function mockVerification(string $nationalId, string $fullName, string $dateOfBirth = null): array
    {
        // محاكاة تأخير الشبكة
        usleep(rand(100000, 500000));

        // محاكاة نجاح التحقق
        $verified = rand(0, 1) === 1;

        return [
            'success' => true,
            'verified' => $verified,
            'data' => [
                'national_id' => $nationalId,
                'full_name' => $fullName,
                'date_of_birth' => $dateOfBirth,
                'verification_date' => now()->toISOString(),
                'status' => $verified ? 'verified' : 'not_verified'
            ],
            'gateway' => 'testing'
        ];
    }

    /**
     * التحقق من حالة الهوية الوطنية
     */
    public function checkStatus(string $nationalId): array
    {
        $cacheKey = "national_id_status_{$nationalId}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // في الواقع، هذا سيتطلب اتصال بالبوابة الحكومية
        // للآن سنقوم بإرجاع حالة افتراضية
        $status = [
            'national_id' => $nationalId,
            'status' => 'active',
            'last_verified' => now()->subDays(rand(1, 30))->toISOString(),
            'verification_count' => rand(1, 10)
        ];

        Cache::put($cacheKey, $status, now()->addHours(6));
        
        return $status;
    }

    /**
     * إلغاء التحقق من الهوية الوطنية
     */
    public function revokeVerification(string $nationalId): array
    {
        $cacheKey = "national_id_verification_{$nationalId}";
        Cache::forget($cacheKey);

        return [
            'success' => true,
            'message' => 'تم إلغاء التحقق من الهوية الوطنية',
            'national_id' => $nationalId
        ];
    }

    /**
     * الحصول على إحصائيات التحقق
     */
    public function getVerificationStats(): array
    {
        $cacheKey = 'national_id_verification_stats';
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // في الواقع، هذا سيتطلب استعلام قاعدة البيانات
        $stats = [
            'total_verifications' => rand(100, 1000),
            'successful_verifications' => rand(80, 95),
            'failed_verifications' => rand(5, 20),
            'pending_verifications' => rand(0, 10),
            'last_24_hours' => rand(10, 50)
        ];

        Cache::put($cacheKey, $stats, now()->addHours(1));
        
        return $stats;
    }
}
