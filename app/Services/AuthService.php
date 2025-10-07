<?php

namespace App\Services;

use App\Repositories\CompanyProfileRepo\ICompanyProfileRepo;
use App\Repositories\CustomerHobbyRepo\ICustomerHobbyRepo;
use App\Repositories\CustomerProfileRepo\ICustomerProfileRepo;
use App\Repositories\UserRepo\IUserRepo;
use App\Services\ApprovalService;
use App\Models\UserTermsAgreement;
use App\Models\LegalPage;
use App\Models\CompanyLegalDocument;
use App\Enums\ReviewStatus;
use App\Enums\CompanyLegalDocType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AuthService
{

    public function __construct(private readonly IUserRepo $IUserRepo,
                                private readonly ICompanyProfileRepo $companyProfileRepo,
                                private readonly ICustomerProfileRepo $customerProfileRepo,
                                private readonly ICustomerHobbyRepo $customerHobbyRepo,
                                )
    {
    }

    public function login(array $credentials)
    {
        $remember = (bool)($credentials['remember'] ?? false);
        unset($credentials['remember']);

        // Determine TTL: use long-lived TTL when remember=true, else default from config
        $defaultTtl = (int) config('jwt.ttl', 60);
        $rememberTtl = (int) env('REMEMBER_ME_TTL', 60 * 24 * 60); // default 60 days in minutes
        $ttl = $remember ? $rememberTtl : $defaultTtl;

        // Locate user and verify password
        if (($credentials['type'] ?? null) === 'admin') {
            // Admin login via email/password
            $user = \App\Models\User::where('email', $credentials['email'] ?? null)
                ->where('type', 'admin')
                ->first();
        } else {
            // Customer/Provider login via phone/country_id/password
            $countryId = $credentials['country_id'] ?? null;
            $country = \App\Models\Country::find($countryId);
            $countryCode = $country ? $country->code : null;
            
            $user = \App\Models\User::where('phone', $credentials['phone'] ?? null)
                ->where('country_code', $countryCode)
                ->where('type', $credentials['type'] ?? null)
                ->first();
        }
        if (!$user || !Hash::check($credentials['password'] ?? '', $user->password)) {
            // تسجيل محاولة دخول فاشلة
            if ($user) {
                $this->logAuthentication($user, $credentials, false);
                $this->logUserActivity($user, 'login_failed', 'فشل في تسجيل الدخول - كلمة مرور خاطئة', [
                    'login_type' => $this->getLoginType($credentials),
                    'reason' => 'invalid_password'
                ], 'failed');
            }
            
            return [
                'status' => false,
                'error' => 'invalid_credentials',
                'message' => 'Wrong credentials',
            ];
        }

        // Enforce phone verification before granting login (skip for admin)
        if ($user->type !== 'admin' && empty($user->phone_verified_at)) {
            return [
                'status' => false,
                'error' => 'phone_not_verified',
                'message' => 'Phone verification required. Please request and enter the OTP.',
                'phone' => $user->phone,
                'country_code' => $user->country_code,
                'user_type' => $user->type,
            ];
        }

        // Issue token after passing checks
        \Tymon\JWTAuth\Facades\JWTAuth::factory()->setTTL($ttl);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
        
        // Set the token in the request for immediate use
        request()->headers->set('Authorization', 'Bearer ' . $token);

        // إنشاء سجل دخول في authentication_log
        $this->logAuthentication($user, $credentials, true);
        
        // تسجيل نشاط تسجيل الدخول في user_activities
        $this->logUserActivity($user, 'login', 'تم تسجيل الدخول بنجاح', [
            'login_type' => $this->getLoginType($credentials),
            'remember' => $remember,
            'ttl' => $ttl
        ], 'success');

        return [
            'status' => true,
            'token' => $token,
            'user' => $user,
            'ttl' => $ttl,
            'remember' => $remember,
        ];
    }


    public function refreshToken()
    {
        try {
            return \Tymon\JWTAuth\Facades\JWTAuth::refresh();
        } catch (\Exception $e) {
            \Log::error('Token refresh failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function logout()
    {
        try {
            $user = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();
            if ($user) {
                // تسجيل نشاط تسجيل الخروج
                $this->logUserActivity($user, 'logout', 'تم تسجيل الخروج بنجاح', [], 'success');
            }
            \Tymon\JWTAuth\Facades\JWTAuth::invalidate();
        } catch (\Exception $e) {
            \Log::error('Logout failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * تسجيل محاولة الدخول في authentication_log
     */
    private function logAuthentication($user, $credentials, $successful = true)
    {
        try {
            $request = request();
            
            // تحديد نوع الدخول بناءً على البيانات المرسلة
            $loginType = 'api';
            $userAgent = 'API Access';
            
            if (isset($credentials['type']) && $credentials['type'] === 'admin') {
                $loginType = 'admin_api';
                $userAgent = 'Admin API Access';
            } elseif (isset($credentials['phone'])) {
                $loginType = 'mobile_api';
                $userAgent = 'Mobile API Access';
            } elseif (isset($credentials['email'])) {
                $loginType = 'web_api';
                $userAgent = 'Web API Access';
            }

            // الحصول على معلومات الطلب
            $ipAddress = $request->ip();
            $userAgentHeader = $request->userAgent() ?? $userAgent;
            
            // إنشاء سجل الدخول
            \App\Models\AuthenticationLog::create([
                'authenticatable_type' => 'App\\Models\\User',
                'authenticatable_id' => $user->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgentHeader,
                'login_at' => now(),
                'login_successful' => $successful,
                'location' => null, // يمكن إضافة خدمة تحديد الموقع لاحقاً
                'logout_at' => null,
                'cleared_by_user' => false,
            ]);

        } catch (\Exception $e) {
            // تسجيل الخطأ ولكن عدم إيقاف عملية تسجيل الدخول
            \Log::error('Failed to log authentication', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * تسجيل نشاط المستخدم في user_activities
     */
    private function logUserActivity($user, $action, $description = null, $metadata = [], $status = 'success')
    {
        try {
            \App\Models\UserActivity::log($user->id, $action, $description, $metadata, $status);
        } catch (\Exception $e) {
            \Log::error('Failed to log user activity', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * تحديد نوع الدخول بناءً على البيانات المرسلة
     */
    private function getLoginType($credentials)
    {
        if (isset($credentials['type']) && $credentials['type'] === 'admin') {
            return 'admin_api';
        } elseif (isset($credentials['phone'])) {
            return 'mobile_api';
        } elseif (isset($credentials['email'])) {
            return 'web_api';
        } else {
            return 'api';
        }
    }


    public function registerProvider(array $data)
    {
        DB::beginTransaction();
        
        // Get country_code from country_id for backward compatibility
        if (isset($data['country_id'])) {
            $country = \App\Models\Country::find($data['country_id']);
            if ($country) {
                $data['country_code'] = $country->code;
            }
        }
        
        $user = $this->IUserRepo->create($data);
        if ($user) {
            $profile = $this->companyProfileRepo->create(array_merge($data, ['user_id' => $user->id]));
            if ($profile) {
                DB::commit();
                
                // تسجيل نشاط التسجيل
                $this->logUserActivity($user, 'register_provider', 'تم تسجيل مقدم خدمة جديد', [
                    'profile_id' => $profile->id,
                    'company_name' => $data['company_name'] ?? null
                ], 'success');
                
                // معالجة الموافقة على مقدم الخدمة
                $approvalService = app(ApprovalService::class);
                $approvalService->handleProviderRegistration($user);
                
                // إنشاء سجل الموافقة على الشروط والأحكام إذا تم قبولها
                if (isset($data['terms_of_service_provider']) && $data['terms_of_service_provider']) {
                    $this->createTermsAgreement($user, 'terms-of-service-provider');
                }
                
                // إنشاء موافقات لجميع الصفحات القانونية المطلوبة
                $this->createAllLegalAgreements($user, $data);
                
                // معالجة المستندات القانونية
                $this->handleLegalDocuments($profile, $data);
                
                // معالجة الصور
                $this->handleImages($user, $profile, $data);
                
                // معالجة الهوايات
                $this->handleHobbies($profile, $data);
                
                return [
                    'status' => true,
                    'user' => $user,
                    'profile' => $profile,
                ];
            }
        }
        DB::rollBack();
        return null;
    }

    public function registerCustomer(array $data)
    {
        DB::beginTransaction();
        
        // Extract user data (fields that belong to users table)
        $userData = [
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'theme' => $data['theme'] ?? 'light',
            'phone' => $data['phone'],
            'country_id' => $data['country_id'] ?? null,
            'type' => 'customer'
        ];

        // Get country_code from country_id for backward compatibility
        if (isset($data['country_id'])) {
            $country = \App\Models\Country::find($data['country_id']);
            if ($country) {
                $userData['country_code'] = $country->code;
            }
        }
        
        // Create user
        $user = $this->IUserRepo->create($userData);
        if ($user) {
            $profileData = [
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'] ?? null,
                'country_code' => $userData['country_code'] ?? null, // Use the country_code from userData
                // national_id تم نقله إلى جدول users
                'region_id' => $data['region_id'] ?? null,
                'neigbourhood_id' => $data['neigbourhood_id'] ?? null,
            ];

            // Create customer profile
            $profile = $this->customerProfileRepo->create($profileData);
            // Add hobbies if provided (normalized)
            if(isset($data['hobby_ids']) && is_array($data['hobby_ids'])){
                $profile->hobbies()->sync($data['hobby_ids']);
            } elseif(isset($data['hobbies']) && is_array($data['hobbies'])){
                $ids = collect($data['hobbies'])->filter()->map(function($name){
                    $h = \App\Models\Hobby::firstOrCreate(['name' => $name]);
                    return $h->id;
                })->all();
                $profile->hobbies()->sync($ids);
            }
            if ($profile) {
                // معالجة الإحالة إذا وجدت
                if (isset($data['referral_code']) && !empty($data['referral_code'])) {
                    $referralService = app(\App\Services\ReferralService::class);
                    $referralService->processReferral($data['referral_code'], $user);
                }
                
                DB::commit();
                
                // تسجيل نشاط التسجيل
                $this->logUserActivity($user, 'register_customer', 'تم تسجيل عميل جديد', [
                    'profile_id' => $profile->id,
                    'referral_code' => $data['referral_code'] ?? null,
                    'hobbies_count' => count($data['hobby_ids'] ?? $data['hobbies'] ?? [])
                ], 'success');
                
                return [
                    'status' => true,
                    'user' => $user,
                    'profile' => $profile,
                ];
            }
        }
        DB::rollBack();
        return null;
    }
    
    /**
     * إنشاء سجل الموافقة على الشروط والأحكام
     */
    private function createTermsAgreement($user, $slug)
    {
        $legalPage = LegalPage::where('slug', $slug)->first();
        
        if ($legalPage) {
            UserTermsAgreement::create([
                'user_id' => $user->id,
                'legal_page_id' => $legalPage->id,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        }
    }
    
    /**
     * معالجة المستندات القانونية الديناميكية
     */
    private function handleLegalDocuments($profile, $data)
    {
        // معالجة المستندات الديناميكية الجديدة
        if (isset($data['legal_documents']) && is_array($data['legal_documents'])) {
            foreach ($data['legal_documents'] as $docData) {
                if (isset($docData['file']) && $docData['file']) {
                    $filePath = $this->storeDocument($docData['file']);
                    
                    // البحث عن المتطلب المناسب
                    $requirement = \App\Models\MainServiceRequiredDocument::where('main_service_id', $profile->main_service_id)
                        ->where('country_id', $profile->country_id)
                        ->where('document_type', $docData['type'])
                        ->first();
                    
                    if ($requirement) {
                        CompanyLegalDocument::create([
                            'company_profile_id' => $profile->id,
                            'main_service_required_document_id' => $requirement->id,
                            'doc_type' => \App\Enums\CompanyLegalDocType::from($docData['type']),
                            'file_path' => $filePath,
                            'start_date' => isset($docData['start_date']) ? Carbon::parse($docData['start_date']) : null,
                            'expires_at' => isset($docData['end_date']) ? Carbon::parse($docData['end_date']) : null,
                            'status' => ReviewStatus::PENDING,
                        ]);
                    }
                }
            }
        }
        
        // معالجة المستندات القديمة للتوافق مع الإصدارات السابقة
        $legacyDocuments = [
            'tourism_license' => [
                'file' => $data['commercial_license_document'] ?? null,
                'start_date' => $data['commercial_license_start_date'] ?? null,
                'end_date' => $data['commercial_license_end_date'] ?? null,
            ],
            'commercial_registration' => [
                'file' => $data['commercial_register_document'] ?? null,
                'start_date' => $data['commercial_register_start_date'] ?? null,
                'end_date' => $data['commercial_register_end_date'] ?? null,
            ],
            'catering_permit' => [
                'file' => $data['activity_permit_document'] ?? null,
                'start_date' => $data['activity_permit_start_date'] ?? null,
                'end_date' => $data['activity_permit_end_date'] ?? null,
            ],
        ];
        
        foreach ($legacyDocuments as $type => $docData) {
            if ($docData['file']) {
                $filePath = $this->storeDocument($docData['file']);
                
                // البحث عن المتطلب المناسب أو إنشاء واحد افتراضي
                $requirement = \App\Models\MainServiceRequiredDocument::where('main_service_id', $profile->main_service_id)
                    ->where('country_id', $profile->country_id)
                    ->where('document_type', $type)
                    ->first();
                
                if ($requirement) {
                    CompanyLegalDocument::create([
                        'company_profile_id' => $profile->id,
                        'main_service_required_document_id' => $requirement->id,
                        'doc_type' => CompanyLegalDocType::from($type),
                        'file_path' => $filePath,
                        'start_date' => $docData['start_date'] ? Carbon::parse($docData['start_date']) : null,
                        'expires_at' => $docData['end_date'] ? Carbon::parse($docData['end_date']) : null,
                        'status' => ReviewStatus::PENDING,
                    ]);
                }
            }
        }
    }
    
    /**
     * حفظ المستند
     */
    private function storeDocument($file)
    {
        if (is_string($file) && str_starts_with($file, 'data:')) {
            // Base64 file
            $fileContent = base64_decode(substr($file, strpos($file, ',') + 1));
            $fileName = 'document_' . time() . '_' . rand(1000, 9999) . '.pdf';
            $path = 'legal_documents/' . $fileName;
            Storage::disk('public')->put($path, $fileContent);
            return $path;
        } elseif (is_object($file) && method_exists($file, 'getClientOriginalExtension')) {
            // Regular file upload (UploadedFile object)
            $fileName = 'document_' . time() . '_' . rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('legal_documents', $fileName, 'public');
            return $path;
        } else {
            // Handle other file types or fallback
            $fileName = 'document_' . time() . '_' . rand(1000, 9999) . '.pdf';
            $path = 'legal_documents/' . $fileName;
            Storage::disk('public')->put($path, $file);
            return $path;
        }
    }
    
    /**
     * معالجة الصور (avatar و company_logo)
     */
    private function handleImages($user, $profile, $data)
    {
        // معالجة صورة المستخدم (avatar)
        if (isset($data['avatar']) && $data['avatar']) {
            $this->handleAvatarUpload($user, $data['avatar']);
        }
        
        // معالجة شعار الشركة (company_logo)
        if (isset($data['company_logo']) && $data['company_logo']) {
            $this->handleCompanyLogoUpload($profile, $data['company_logo']);
        }
    }
    
    /**
     * معالجة رفع صورة المستخدم
     */
    private function handleAvatarUpload($user, $avatar)
    {
        if (is_string($avatar) && str_starts_with($avatar, 'data:')) {
            // Base64 image
            $user->clearMediaCollection('avatar');
            $user->addMediaFromBase64($avatar)->toMediaCollection('avatar');
            $user->update(['avatar_url' => $user->getFirstMediaUrl('avatar')]);
        } elseif (is_object($avatar) && method_exists($avatar, 'getClientOriginalExtension')) {
            // Regular file upload
            $user->clearMediaCollection('avatar');
            $user->addMedia($avatar)->toMediaCollection('avatar');
            $user->update(['avatar_url' => $user->getFirstMediaUrl('avatar')]);
        }
    }
    
    /**
     * معالجة رفع شعار الشركة
     */
    private function handleCompanyLogoUpload($profile, $logo)
    {
        if (is_string($logo) && str_starts_with($logo, 'data:')) {
            // Base64 image
            $profile->clearMediaCollection('company_logo');
            $profile->addMediaFromBase64($logo)->toMediaCollection('company_logo');
            $profile->update(['company_logo_url' => $profile->getFirstMediaUrl('company_logo')]);
        } elseif (is_object($logo) && method_exists($logo, 'getClientOriginalExtension')) {
            // Regular file upload
            $profile->clearMediaCollection('company_logo');
            $profile->addMedia($logo)->toMediaCollection('company_logo');
            $profile->update(['company_logo_url' => $profile->getFirstMediaUrl('company_logo')]);
        }
    }
    
    /**
     * معالجة الهوايات
     */
    private function handleHobbies($profile, $data)
    {
        if (isset($data['hobbies']) && is_array($data['hobbies'])) {
            $profile->hobbies()->sync($data['hobbies']);
        }
    }
    
    /**
     * إنشاء موافقات لجميع الصفحات القانونية المطلوبة
     */
    private function createAllLegalAgreements($user, $data)
    {
        $legalPages = [
            'terms-of-service-provider' => 'terms_of_service_provider',
            'pricing-seasonality-policy' => 'pricing_seasonality_policy',
            'refund-cancellation-policy' => 'refund_cancellation_policy',
            'privacy-policy' => 'privacy_policy',
            'advertising-policy' => 'advertising_policy',
            'acceptable-content-policy' => 'acceptable_content_policy',
            'contract-continuity-terms' => 'contract_continuity_terms',
            'customer-response-policy' => 'customer_response_policy',
        ];
        
        foreach ($legalPages as $slug => $field) {
            if (isset($data[$field]) && $data[$field]) {
                $this->createTermsAgreement($user, $slug);
            }
        }
    }
}
