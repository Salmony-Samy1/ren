<?php

namespace App\Services;

use App\Models\User;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class ApprovalService
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    /**
     * التحقق من إعدادات الموافقة التلقائية
     */
    private function isAutoApprovalEnabled(string $type): bool
    {
        $settingKey = $type === 'provider' ? 'auto_approve_providers' : 'auto_approve_services';
        return get_setting($settingKey, false);
    }

    /**
     * موافقة على مقدم خدمة
     */
    public function approveProvider(User $provider, ?string $notes = null): bool
    {
        if ($provider->type !== 'provider') {
            return false;
        }

        DB::beginTransaction();
        try {
            $provider->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            // إرسال إشعار للمستخدم
            $this->notificationService->created([
                'user_id' => $provider->id,
                'action' => 'provider_approved',
                'message' => 'تمت الموافقة على تسجيلك كمقدم خدمة بنجاح.',
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * رفض مقدم خدمة
     */
    public function rejectProvider(User $provider, string $notes): bool
    {
        if ($provider->type !== 'provider') {
            return false;
        }

        DB::beginTransaction();
        try {
            $provider->update([
                'is_approved' => false,
                'approval_notes' => $notes,
            ]);

            // إرسال إشعار للمستخدم
            $this->notificationService->created([
                'user_id' => $provider->id,
                'action' => 'provider_rejected',
                'message' => 'تم رفض تسجيلك كمقدم خدمة. السبب: ' . $notes,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * موافقة على خدمة
     */
    public function approveService(Service $service, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $service->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            // إرسال إشعار لمقدم الخدمة
            $this->notificationService->created([
                'user_id' => $service->user_id,
                'action' => 'service_approved',
                'message' => 'تمت الموافقة على خدمتك: ' . $service->name,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * رفض خدمة
     */
    public function rejectService(Service $service, string $notes): bool
    {
        DB::beginTransaction();
        try {
            $service->update([
                'is_approved' => false,
                'approval_notes' => $notes,
            ]);

            // إرسال إشعار لمقدم الخدمة
            $this->notificationService->created([
                'user_id' => $service->user_id,
                'action' => 'service_rejected',
                'message' => 'تم رفض خدمتك: ' . $service->name . '. السبب: ' . $notes,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * معالجة تسجيل مقدم خدمة جديد
     */
    public function handleProviderRegistration(User $provider): bool
    {
        if ($this->isAutoApprovalEnabled('provider')) {
            return $this->approveProvider($provider, 'موافقة تلقائية');
        }

        // إرسال إشعار للإدارة
        $this->notificationService->created([
            'user_id' => 1, // admin user
            'action' => 'new_provider_registration',
            'message' => 'مقدم خدمة جديد ينتظر الموافقة: ' . $provider->email,
        ]);

        return true;
    }

    /**
     * معالجة إنشاء خدمة جديدة
     */
    public function handleServiceCreation(Service $service): bool
    {
        if ($this->isAutoApprovalEnabled('service')) {
            return $this->approveService($service, 'موافقة تلقائية');
        }

        // إرسال إشعار للإدارة
        $this->notificationService->created([
            'user_id' => 1, // admin user
            'action' => 'new_service_creation',
            'message' => 'خدمة جديدة تنتظر الموافقة: ' . $service->name,
        ]);

        return true;
    }

    /**
     * الحصول على قائمة مقدمي الخدمات المعلقين
     */
    public function getPendingProviders()
    {
        return User::where('type', 'provider')
            ->where('is_approved', false)
            ->with('companyProfile')
            ->latest()
            ->paginate(10);
    }

    /**
     * الحصول على قائمة الخدمات المعلقة
     */
    public function getPendingServices()
    {
        return Service::where('is_approved', false)
            ->with(['user', 'category'])
            ->latest()
            ->paginate(10);
    }

    /**
     * تحديث إعدادات الموافقة
     */
    public function updateApprovalSettings(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
