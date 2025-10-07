<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NationalIdVerificationService;
use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Log;

class VerifyNationalIdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profileId;
    protected $retryCount;

    /**
     * Create a new job instance.
     */
    public function __construct(int $profileId, int $retryCount = 0)
    {
        $this->profileId = $profileId;
        $this->retryCount = $retryCount;
    }

    /**
     * Execute the job.
     */
    public function handle(NationalIdVerificationService $verificationService): void
    {
        try {
            $profile = CompanyProfile::find($this->profileId);
            
            if (!$profile || !$profile->national_id) {
                Log::warning('National ID verification job failed: Profile not found or no national ID', [
                    'profile_id' => $this->profileId
                ]);
                return;
            }

            // التحقق من أن الملف الشخصي لم يتم التحقق منه بالفعل
            if ($profile->national_id_verified_at) {
                Log::info('National ID already verified', [
                    'profile_id' => $this->profileId,
                    'national_id' => $profile->national_id
                ]);
                return;
            }

            // محاولة التحقق من الهوية الوطنية
            $result = $verificationService->verify(
                $profile->national_id,
                $profile->owner ?? 'Unknown',
                null // تاريخ الميلاد غير متوفر
            );

            if ($result['success'] && $result['verified']) {
                // تحديث حالة التحقق
                $profile->update([
                    'national_id_verified_at' => now(),
                    'national_id_verification_failed_at' => null,
                    'national_id_verification_data' => json_encode($result['data'])
                ]);

                Log::info('National ID verification successful', [
                    'profile_id' => $this->profileId,
                    'national_id' => $profile->national_id,
                    'gateway' => $result['gateway']
                ]);

                // إرسال إشعار للمستخدم
                // TODO: إضافة نظام الإشعارات

                // التحقق من إعدادات الموافقة التلقائية
                $autoApprove = get_setting('national_id_verification_auto_approve', false);
                if ($autoApprove && $profile->user) {
                    $profile->user->update([
                        'is_approved' => true,
                        'approved_at' => now()
                    ]);

                    Log::info('Provider auto-approved after national ID verification', [
                        'user_id' => $profile->user->id,
                        'profile_id' => $this->profileId
                    ]);
                }

            } else {
                // تحديث حالة الفشل
                $profile->update([
                    'national_id_verification_failed_at' => now(),
                    'national_id_verification_data' => json_encode($result)
                ]);

                Log::warning('National ID verification failed', [
                    'profile_id' => $this->profileId,
                    'national_id' => $profile->national_id,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);

                // إعادة المحاولة إذا كان عدد المحاولات أقل من 3
                if ($this->retryCount < 3) {
                    $this->release(now()->addMinutes(30)); // إعادة المحاولة بعد 30 دقيقة
                }
            }

        } catch (\Exception $e) {
            Log::error('National ID verification job error', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // إعادة المحاولة في حالة حدوث خطأ
            if ($this->retryCount < 3) {
                $this->release(now()->addMinutes(15));
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('National ID verification job failed permanently', [
            'profile_id' => $this->profileId,
            'error' => $exception->getMessage()
        ]);

        // تحديث حالة الفشل الدائم
        $profile = CompanyProfile::find($this->profileId);
        if ($profile) {
            $profile->update([
                'national_id_verification_failed_at' => now(),
                'national_id_verification_data' => json_encode([
                    'error' => 'Job failed permanently',
                    'message' => $exception->getMessage()
                ])
            ]);
        }
    }
}
