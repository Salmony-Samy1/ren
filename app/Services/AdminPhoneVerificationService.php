<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminPhoneVerificationService
{
    /**
     * الموافقة المباشرة على رقم الهاتف للمديرين
     * تجاوز عملية OTP للمستخدمين الجدد
     */
    public function approvePhoneDirectly(User $user, bool $approve = true): bool
    {
        try {
            DB::beginTransaction();

        // التحقق من المستخدم المصادق
        $adminUser = Auth::user();
            
            if (!$adminUser) {
                throw new \Exception('يجب تسجيل الدخول أولاً');
            }
            
            // التحقق من أن المستخدم الحالي هو مدير
            if (!$adminUser->hasRole('admin') && !$adminUser->hasRole('Admin')) {
                throw new \Exception('غير مصرح لك بتنفيذ هذه العملية');
            }

            $adminId = $adminUser->id;

            // تحديث حالة التحقق من الهاتف
            $user->update([
                'phone_verified_at' => $approve ? now() : null,
                'is_approved' => $approve,
                'approved_at' => $approve ? now() : null,
                'approved_by' => $approve ? $adminId : null,
                'approval_notes' => $approve ? 'موافقة مباشرة من المدير - تجاوز عملية OTP' : 'إلغاء الموافقة من المدير'
            ]);

            // تسجيل النشاط
            $this->logAdminActivity($user, $approve ? 'phone_approved_admin' : 'phone_disapproved_admin', 
                $approve ? 'تم الموافقة على رقم الهاتف مباشرة من المدير' : 'تم إلغاء الموافقة على رقم الهاتف من المدير');

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * إنشاء مستخدم جديد مع الموافقة المباشرة على الهاتف
     */
    public function createUserWithDirectPhoneApproval(array $userData, array $profileData = []): User
    {
        try {
            DB::beginTransaction();

        // التحقق من المستخدم المصادق
        $adminUser = Auth::user();
            
            if (!$adminUser) {
                throw new \Exception('يجب تسجيل الدخول أولاً');
            }
            
            // التحقق من أن المستخدم الحالي هو مدير
            if (!$adminUser->hasRole('admin') && !$adminUser->hasRole('Admin')) {
                throw new \Exception('غير مصرح لك بتنفيذ هذه العملية');
            }

            $adminId = $adminUser->id;

            // إضافة بيانات الموافقة المباشرة
            $userData = array_merge($userData, [
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $adminId,
            ]);

            // إنشاء المستخدم
            $user = User::create($userData);

            if (!$user) {
                throw new \Exception('فشل في إنشاء المستخدم');
            }

            // إنشاء الملف الشخصي إذا تم توفير البيانات
            if (!empty($profileData)) {
                $profileData['user_id'] = $user->id;
                
                if ($user->type === 'customer') {
                    \App\Models\CustomerProfile::create($profileData);
                } elseif ($user->type === 'provider') {
                    \App\Models\CompanyProfile::create($profileData);
                }
            }

            // تسجيل النشاط
            $this->logAdminActivity($user, 'user_created_with_direct_approval', 
                'تم إنشاء مستخدم جديد مع الموافقة المباشرة على رقم الهاتف');

            DB::commit();
            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * تجاوز عملية OTP للمستخدمين الموجودين
     */
    public function bypassOtpForExistingUser(string $phone, int $countryId): ?User
    {
        try {
        // التحقق من المستخدم المصادق
        $adminUser = Auth::user();
            
            if (!$adminUser) {
                throw new \Exception('يجب تسجيل الدخول أولاً');
            }
            
            // التحقق من أن المستخدم الحالي هو مدير
            if (!$adminUser->hasRole('admin') && !$adminUser->hasRole('Admin')) {
                throw new \Exception('غير مصرح لك بتنفيذ هذه العملية');
            }

            $adminId = $adminUser->id;

            // Get country code from country_id
            $country = \App\Models\Country::find($countryId);
            if (!$country) {
                throw new \Exception('الدولة غير موجودة');
            }
            $countryCode = $country->code;

            // البحث عن المستخدم
            $user = User::where('phone', $phone)
                      ->where('country_code', $countryCode)
                      ->first();

            if (!$user) {
                return null;
            }

            // الموافقة المباشرة على الهاتف
            $user->update([
                'phone_verified_at' => now(),
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $adminId,
                'approval_notes' => 'تجاوز عملية OTP من المدير'
            ]);

            // تسجيل النشاط
            $this->logAdminActivity($user, 'otp_bypassed_admin', 
                'تم تجاوز عملية OTP والموافقة على رقم الهاتف مباشرة');

            return $user;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * تسجيل نشاط المدير
     */
    private function logAdminActivity(User $user, string $action, string $description): void
    {
        try {
        // التحقق من المستخدم المصادق
        $adminUser = Auth::user();
            
            \App\Models\UserActivity::create([
                'user_id' => $user->id,
                'action' => $action,
                'description' => $description,
                'meta' => [
                    'admin_id' => $adminUser ? $adminUser->id : null,
                    'admin_name' => $adminUser ? ($adminUser->full_name ?? $adminUser->email) : 'Unknown',
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            // تسجيل الخطأ ولكن عدم إيقاف العملية
            \Log::error('فشل في تسجيل نشاط المدير: ' . $e->getMessage());
        }
    }

    /**
     * التحقق من صلاحيات المدير للموافقة على الهاتف
     */
    public function canApprovePhone(): bool
    {
        // التحقق من المستخدم المصادق
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // التحقق من نوع المستخدم
        if ($user->type !== 'admin') {
            return false;
        }

        // التحقق من الأدوار
        if ($user->hasRole('admin') || $user->hasRole('Admin')) {
            return true;
        }

        // التحقق من التصاريح المباشرة
        if ($user->can('users.manage') || $user->can('providers.manage')) {
            return true;
        }

        return false;
    }

    /**
     * الحصول على إحصائيات الموافقات المباشرة
     */
    public function getDirectApprovalStats(): array
    {
        // التحقق من المستخدم المصادق
        $adminUser = Auth::user();
        
        if (!$adminUser) {
            return [
                'total_approved' => 0,
                'today_approved' => 0,
                'this_month_approved' => 0,
            ];
        }
        
        $adminId = $adminUser->id;
        
        return [
            'total_approved' => User::where('approved_by', $adminId)->count(),
            'today_approved' => User::where('approved_by', $adminId)
                                   ->whereDate('approved_at', today())
                                   ->count(),
            'this_month_approved' => User::where('approved_by', $adminId)
                                        ->whereMonth('approved_at', now()->month)
                                        ->whereYear('approved_at', now()->year)
                                        ->count(),
        ];
    }
}