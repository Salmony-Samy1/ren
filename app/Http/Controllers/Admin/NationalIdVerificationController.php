<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NationalIdVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CompanyProfile;
use App\Models\User;

class NationalIdVerificationController extends Controller
{
    protected $verificationService;

    public function __construct(NationalIdVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * عرض قائمة التحقق من الهوية الوطنية
     */
    public function index(Request $request)
    {
        $query = CompanyProfile::with(['user' => function($q) {
            $q->select('id', 'name', 'email', 'phone', 'type', 'is_approved');
        }]);

        // فلترة حسب حالة الموافقة
        if ($request->has('approval_status')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('is_approved', $request->approval_status);
            });
        }

        // فلترة حسب حالة التحقق
        if ($request->has('verification_status')) {
            switch ($request->verification_status) {
                case 'verified':
                    $query->whereNotNull('national_id_verified_at');
                    break;
                case 'pending':
                    $query->whereNull('national_id_verified_at');
                    break;
                case 'failed':
                    $query->whereNotNull('national_id_verification_failed_at');
                    break;
            }
        }

        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('national_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $profiles = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $profiles
        ]);
    }

    /**
     * التحقق من الهوية الوطنية
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'profile_id' => 'required|exists:company_profiles,id'
        ], [
            'national_id.required' => 'الهوية الوطنية مطلوبة',
            'national_id.size' => 'الهوية الوطنية يجب أن تكون 10 أرقام',
            'national_id.regex' => 'الهوية الوطنية يجب أن تحتوي على أرقام فقط',
            'full_name.required' => 'الاسم الكامل مطلوب',
            'full_name.max' => 'الاسم الكامل يجب أن لا يتجاوز 255 حرف',
            'date_of_birth.before' => 'تاريخ الميلاد يجب أن يكون في الماضي',
            'profile_id.required' => 'معرف الملف الشخصي مطلوب',
            'profile_id.exists' => 'الملف الشخصي غير موجود'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = CompanyProfile::findOrFail($request->profile_id);
        
        // التحقق من أن الهوية الوطنية تتطابق مع الملف الشخصي
        if ($profile->national_id !== $request->national_id) {
            return response()->json([
                'success' => false,
                'message' => 'الهوية الوطنية لا تتطابق مع الملف الشخصي'
            ], 400);
        }

        $result = $this->verificationService->verify(
            $request->national_id,
            $request->full_name,
            $request->date_of_birth
        );

        if ($result['success'] && $result['verified']) {
            // تحديث حالة التحقق
            $profile->update([
                'national_id_verified_at' => now(),
                'national_id_verification_failed_at' => null,
                'national_id_verification_data' => json_encode($result['data'])
            ]);

            // إرسال إشعار للمستخدم
            // TODO: إضافة نظام الإشعارات

            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من الهوية الوطنية بنجاح',
                'data' => $result['data'],
                'gateway' => $result['gateway']
            ]);
        }

        // تحديث حالة الفشل
        $profile->update([
            'national_id_verification_failed_at' => now(),
            'national_id_verification_data' => json_encode($result)
        ]);

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code']
        ], 400);
    }

    /**
     * التحقق من حالة الهوية الوطنية
     */
    public function checkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string|size:10|regex:/^[0-9]{10}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $this->verificationService->checkStatus($request->national_id);

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * إلغاء التحقق من الهوية الوطنية
     */
    public function revoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:company_profiles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = CompanyProfile::findOrFail($request->profile_id);
        
        $result = $this->verificationService->revokeVerification($profile->national_id);

        if ($result['success']) {
            // إعادة تعيين حالة التحقق
            $profile->update([
                'national_id_verified_at' => null,
                'national_id_verification_failed_at' => null,
                'national_id_verification_data' => null
            ]);
        }

        return response()->json($result);
    }

    /**
     * الحصول على إحصائيات التحقق
     */
    public function statistics()
    {
        $stats = $this->verificationService->getVerificationStats();

        // إضافة إحصائيات من قاعدة البيانات
        $dbStats = [
            'total_providers' => CompanyProfile::count(),
            'verified_providers' => CompanyProfile::whereNotNull('national_id_verified_at')->count(),
            'pending_verification' => CompanyProfile::whereNull('national_id_verified_at')->count(),
            'failed_verification' => CompanyProfile::whereNotNull('national_id_verification_failed_at')->count(),
            'recent_verifications' => CompanyProfile::whereNotNull('national_id_verified_at')
                ->where('national_id_verified_at', '>=', now()->subDays(7))
                ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge($stats, $dbStats)
        ]);
    }

    /**
     * تحديث إعدادات التحقق
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|in:absher,nitaqat,testing',
            'api_key' => 'required_if:gateway,absher,nitaqat|string',
            'base_url' => 'required_if:gateway,absher,nitaqat|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // تحديث ملف .env
        $this->updateEnvFile([
            'NATIONAL_ID_GATEWAY' => $request->gateway,
            'NATIONAL_ID_API_KEY' => $request->api_key ?? '',
            'NATIONAL_ID_BASE_URL' => $request->base_url ?? ''
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات التحقق بنجاح'
        ]);
    }

    /**
     * تحديث ملف .env
     */
    protected function updateEnvFile(array $data)
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            return false;
        }

        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            if (strpos($envContent, "{$key}=") !== false) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
        
        return true;
    }
}
