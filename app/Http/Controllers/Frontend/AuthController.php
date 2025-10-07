<?php

namespace App\Http\Controllers\Frontend;

use App\DTO\CustomerDTO;
use App\DTO\ProviderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\RegisterProviderRequest;
use App\Http\Requests\SendOTPRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\ValidateOtpRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SendSMS;
use App\Repositories\CityRepo\CityRepo;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Http\Requests\SetNewPasswordRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly CityRepo    $cityRepo,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function login(UserLoginRequest $request)
    {
        $credentials = $request->validated();
        $result = $this->authService->login($credentials);
        if ($result['status']) {
            $payload = array_merge(
                (new UserResource($result['user']))->resolve(),
                ['token' => $result['token'], 'remember' => (bool)($result['remember'] ?? false), 'ttl' => $result['ttl'] ?? null]
            );
            $response = format_response(true, __('auth.login.success'), [
                'user' => $payload,
            ]);

            if (!empty($credentials['remember']) && ($result['remember'] ?? false)) {
                $cookieName = env('AUTH_TOKEN_COOKIE', 'auth_token');
                $minutes = (int) ($result['ttl'] ?? 0);
                $cookie = cookie(
                    name: $cookieName,
                    value: $result['token'],
                    minutes: $minutes,
                    path: '/',
                    domain: config('session.domain'),
                    secure: (bool) config('session.secure', true),
                    httpOnly: (bool) config('session.http_only', true),
                    sameSite: config('session.same_site', 'lax')
                );
                $response->headers->setCookie($cookie);
            }

            return $response;
        }

        // Surface specific errors (e.g., phone not verified)
        if (($result['error'] ?? null) === 'phone_not_verified') {
            return response()->json([
                'success' => false,
                'message' => 'Phone verification required. Please request and enter the OTP.',
                'error' => 'phone_not_verified',
                'data' => [
                    'phone' => $result['phone'] ?? null,
                    'country_code' => $result['country_code'] ?? null,
                    'user_type' => $result['user_type'] ?? null,
                    'profile_status' => false,
                ],
            ], 403);
        }

        return format_response(false, __('auth.login.invalid'), code: 401);
    }

    public function refreshToken()
    {
        $result = $this->authService->refreshToken();
        return format_response(true, __('auth.token.refreshed'), ['token' => $result]);
    }


    public function logout()
    {
        $this->authService->logout();

        // Clear auth cookie if set
        $cookieName = env('AUTH_TOKEN_COOKIE', 'auth_token');
        cookie()->queue(cookie(
            name: $cookieName,
            value: '',
            minutes: -1,
            path: '/',
            domain: config('session.domain'),
            secure: (bool) config('session.secure', true),
            httpOnly: (bool) config('session.http_only', true),
            sameSite: config('session.same_site', 'lax')
        ));

        return format_response(true, __('auth.logout.success'));
    }

    public function completeProfile(\App\Http\Requests\Auth\CompleteProfileRequest $request)
    {
        // Ensure pending-profile token
        $jwt = \Tymon\JWTAuth\Facades\JWTAuth::parseToken();
        $claims = $jwt->getPayload();
        if ($claims->get('profile_status') !== 'pending_profile_completion') {
            return format_response(false, 'Invalid token state', code: 403);
        }
        $user = $jwt->authenticate();

        // Update user basic fields
        $data = $request->validated();
        $user->update([
            'email' => $data['email'],
            'full_name' => $data['name'],
        ]);

        // Create/update customer profile
        $profile = $user->customerProfile()->first();
        if (!$profile) {
            $profile = \App\Models\CustomerProfile::create([
                'user_id' => $user->id,
                'first_name' => $data['name'],
                'last_name' => '',
                'gender' => $data['gender'],
                'national_id' => $data['national_id'],
                'country_code' => $user->country_code,
                'region_id' => $data['region_id'],
                'neigbourhood_id' => $data['neigbourhood_id'],
            ]);
        } else {
            $profile->update([
                'first_name' => $data['name'],
                'gender' => $data['gender'],
                'national_id' => $data['national_id'],
                'region_id' => $data['region_id'],
                'neigbourhood_id' => $data['neigbourhood_id'],
            ]);
        }

        // Generate public_id if missing
        if (empty($user->public_id)) {
            $user->public_id = 'GATHRO-USER-'.str_pad((string)$user->id, 5, '0', STR_PAD_LEFT);
            $user->save();
        }

        // Award welcome points via PointsLedgerService if configured
        try {
            $welcome = get_setting('first_booking_points');
            $expiryDays = (int) (get_setting('points_expiry_days') ?? 365);
            if ($welcome !== null) {
                app(\App\Services\PointsLedgerService::class)->earn($user, (int)$welcome, 'welcome_bonus', null, $expiryDays);
            }
        } catch (\Throwable $e) { report($e); }

        // Issue full JWT
        \Tymon\JWTAuth\Facades\JWTAuth::factory()->setTTL((int) config('jwt.ttl', 60));
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        return format_response(true, 'Profile completed', [
            'token' => $token,
            'user' => new \App\Http\Resources\UserResource($user->fresh('customerProfile')),
            'profile_status' => 'active',
        ]);
    }

    public function registerProvider(RegisterProviderRequest $request)
    {
        $provider = new ProviderDTO(
            name: $request->company_name,
            owner: $request->owner,
            national_id: $request->national_id,
            email: $request->email,
            password: $request->password,
            phone: $request->phone,
            city_id: $request->city_id,
            country_id: $request->country_id,
            cityRepo: $this->cityRepo,
            nationality_id: $request->nationality_id,
            iban: $request->iban,
            tourism_license_number: $request->tourism_license_number,
            kyc_id: $request->kyc_id,
            main_service_id: $request->main_service_id,
            region_id: $request->region_id,
            service_classification: $request->service_classification,
            description: $request->description,
            hobbies: $request->hobbies,
            legal_documents: $request->legal_documents,
            company_logo: $request->company_logo,
            avatar: $request->avatar,
            terms_of_service_provider: $request->input('terms-of-service-provider'),
            pricing_seasonality_policy: $request->input('pricing-seasonality-policy'),
            refund_cancellation_policy: $request->input('refund-cancellation-policy'),
            privacy_policy: $request->input('privacy-policy'),
            advertising_policy: $request->input('advertising-policy'),
            acceptable_content_policy: $request->input('acceptable-content-policy'),
            contract_continuity_terms: $request->input('contract-continuity-terms'),
            customer_response_policy: $request->input('customer-response-policy')
        );

        // إضافة الملفات للبيانات
        $providerData = $provider->toArray();
        
        // معالجة المستندات القانونية الديناميكية
        if ($request->has('legal_documents')) {
            $legalDocs = [];
            foreach ($request->legal_documents as $index => $doc) {
                $legalDocs[] = [
                    'type' => $doc['type'],
                    'file' => $request->file("legal_documents.{$index}.file"),
                    'start_date' => $doc['start_date'],
                    'end_date' => $doc['end_date'],
                ];
            }
            $providerData['legal_documents'] = $legalDocs;
        }
        
        $providerData['company_logo'] = $request->file('company_logo');
        $providerData['avatar'] = $request->file('avatar');
        
        $result = $this->authService->registerProvider($providerData);
        if ($result) {
            // Award welcome bonus points: strictly from admin settings only
            $token = null;
            try {
                $welcome = get_setting('first_booking_points');
                $expiryDays = (int) (get_setting('points_expiry_days') ?? 365);
                if ($welcome === null) { throw new \RuntimeException('first_booking_points not configured'); }
                app(\App\Services\PointsLedgerService::class)->earn($result['user'], (int) $welcome, 'welcome_bonus', null, $expiryDays);
                $identifier = ($result['user']->country_code ?? '') . ($result['user']->phone ?? '');
                $otpResult = OtpService::generateOtp($identifier);
                $token = is_object($otpResult) ? ($otpResult->token ?? null) : (is_array($otpResult) ? ($otpResult['token'] ?? null) : (string) $otpResult);
                if ($token) {
                    \Illuminate\Support\Facades\Bus::dispatchSync(new SendSMS($identifier, 'رمز التحقق: ' . $token . ' صالح لمدة 10 دقائق'));
                    if (!app()->environment('production')) {
                        Log::info('OTP generated (dev)', [ 'identifier' => substr($identifier,0,5).'***' ]);
                    }
                }
            } catch (\Throwable $e) { report($e); }
            return format_response(true, __('User registered successfully'), [
                'user' => new UserResource($result['user']),
                'otp' => $token,
            ]);
        }
        return format_response(false, __('Something went wrong'), code: 500);
    }

    public function registerCustomer(RegisterCustomerRequest $request)
    {
        $customer = new CustomerDTO(
            first_name: $request->first_name,
            last_name: $request->last_name,
            email: $request->email,
            password: $request->password,
            gender: $request->gender,
            phone: $request->phone,
            country_id: $request->country_id,
            region_id: $request->region_id,
            neigbourhood_id: $request->neigbourhood_id,
            national_id: $request->national_id,
            hobbies: $request->hobbies,
        );
        $result = $this->authService->registerCustomer($customer->toArray());
        if ($result) {
            // Generate and send OTP immediately after successful registration
            $token = null;
            try {
                $identifier = ($result['user']->country_code ?? '') . ($result['user']->phone ?? '');
                $otpResult = OtpService::generateOtp($identifier);
                $token = is_object($otpResult) ? ($otpResult->token ?? null) : (is_array($otpResult) ? ($otpResult['token'] ?? null) : (string) $otpResult);
                if ($token) {
                    \Illuminate\Support\Facades\Bus::dispatchSync(new SendSMS($identifier, 'رمز التحقق: ' . $token . ' صالح لمدة 10 دقائق'));
                    if (!app()->environment('production')) {
                        Log::info('OTP generated (dev)', [ 'identifier' => substr($identifier,0,5).'***' ]);
                    }
                }
            } catch (\Throwable $e) { report($e); }

            // Award welcome bonus points: strictly from admin settings only
            try {
                $welcome = get_setting('first_booking_points');
                $expiryDays = (int) (get_setting('points_expiry_days') ?? 365);
                if ($welcome === null) { throw new \RuntimeException('first_booking_points not configured'); }
                app(\App\Services\PointsLedgerService::class)->earn($result['user'], (int) $welcome, 'welcome_bonus', null, $expiryDays);
            } catch (\Throwable $e) { report($e); }
            return format_response(true, __('User registered successfully'), [
                'user' => new UserResource($result['user']),
                'otp' => $token,
            ]);
        }
        return format_response(false, __('Something went wrong'), code: 500);
    }

    public function sendOtp(SendOTPRequest $request)
    {
        $countryId = $request->input('country_id');
        $phone = $request->input('phone');

        // Get country code from country_id
        $country = \App\Models\Country::find($countryId);
        if (!$country) {
            return format_response(false, __('Invalid country'), code: 422);
        }
        $countryCode = $country->code;

        // 1) Allow sending only if the phone exists in DB
        $userExists = User::where('phone', $phone)->where('country_code', $countryCode)->exists();
        if (!$userExists) {
            return format_response(false, __('phone_not_registered'), code: 404);
        }

        $identifier = $countryCode . $phone;

        // 2) Cooldown per phone to avoid spam (default 60s)
        $cooldown = (int) (get_setting('otp_cooldown_seconds', 60));
        $key = 'otp:cooldown:' . $identifier;
        $nowTs = now()->timestamp;
        $nextAllowedTs = (int) (\Cache::get($key) ?? 0);
        if ($nextAllowedTs > $nowTs) {
            $retry = max(1, $nextAllowedTs - $nowTs);
            return response()->json([
                'success' => false,
                'message' => 'too_many_attempts',
                'data' => ['retry_after_seconds' => $retry]
            ], 429);
        }

        // Additional per-IP throttling (e.g., 20 requests/hour)
        $ip = $request->ip();
        $ipKey = 'otp:ip:' . $ip;
        $ipCount = (int) (\Cache::get($ipKey, 0));
        $ipLimit = (int) (get_setting('otp_hourly_limit_per_ip', 20));
        if ($ipCount >= $ipLimit) {
            return response()->json([
                'success' => false,
                'message' => 'too_many_attempts',
                'data' => ['retry_after_seconds' => 3600]
            ], 429);
        }
        \Cache::put($ipKey, $ipCount + 1, 3600);

        // 3) Generate and (optionally) send OTP
        $otpResult = OtpService::generateOtp($identifier);
        $token = is_object($otpResult) ? ($otpResult->token ?? null) : (is_array($otpResult) ? ($otpResult['token'] ?? null) : (string) $otpResult);

        // Log OTP for testing/verification before SMS integration

        $delivery = config('services.otp.delivery', 'log');
        if ($delivery === 'sms' && $token) {
            \Illuminate\Support\Facades\Bus::dispatchSync(new SendSMS($identifier, 'رمز التحقق: ' . $token . ' صالح لمدة 10 دقائق'));
        }

        // Set next allowed time
        \Cache::put($key, now()->addSeconds($cooldown)->timestamp, $cooldown + 5);

        return format_response(true, __('OTP sent successfully'), ['otp' => $token]);
    }

    public function validateOtp(ValidateOtpRequest $request)
    {
        $countryId = $request->input('country_id');
        $phone = $request->input('phone');

        // Get country code from country_id
        $country = \App\Models\Country::find($countryId);
        if (!$country) {
            return format_response(false, __('Invalid country'), code: 422);
        }
        $countryCode = $country->code;

        $identifier = $countryCode . $phone;
        $result = OtpService::validate($identifier, $request['otp']);
        if (!$result) {
            return format_response(false, __('Invalid OTP'), code: 401);
        }

        // Find existing user by phone
        $user = \App\Models\User::where('phone', $phone)
            ->where('country_code', $countryCode)
            ->first();

        if ($user) {
            // Mark phone verified
            $user->forceFill(['phone_verified_at' => now()])->save();
            // Issue full JWT
            \Tymon\JWTAuth\Facades\JWTAuth::factory()->setTTL((int) config('jwt.ttl', 60));
            $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
            return format_response(true, __('OTP validated successfully'), [
                'token' => $token,
                'user' => new \App\Http\Resources\UserResource($user),
            ]);
        }

        // Create minimal user record with pending profile
        $user = \App\Models\User::create([
            'email' => 'pending_'.bin2hex(random_bytes(6)).'@gathro.local',
            'password' => \Illuminate\Support\Facades\Hash::make(bin2hex(random_bytes(8))),
            'phone' => $request['phone'],
            'country_code' => $request['country_code'],
            'type' => 'customer',
            'phone_verified_at' => now(),
        ]);

        // JWT with limited claim
        \Tymon\JWTAuth\Facades\JWTAuth::factory()->setTTL((int) config('jwt.ttl', 60));
        $token = \Tymon\JWTAuth\Facades\JWTAuth::claims([
            'profile_status' => 'pending_profile_completion',
        ])->fromUser($user);

        return format_response(true, __('OTP validated successfully'), [
            'token' => $token,
            'user' => new \App\Http\Resources\UserResource($user),
            'profile_status' => 'pending_profile_completion',
        ]);
    }

    public function setNewPassword(SetNewPasswordRequest $request)
    {
        $isValidOtp = OtpService::validate($request['country_code'] . $request['phone'], $request['otp']);

        if (!$isValidOtp) {
            return format_response(false, __('Invalid OTP'), code: 401);
        }

        $user = User::where('phone', $request['phone'])->first();

        if (!$user) {
            return format_response(false, __('User not found'), code: 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $this->notificationService->created([
            'user_id' => $user->id,
            'action' => 'password_changed',
            'message' => 'تم تغيير كلمة المرور لحسابك بنجاح.',
        ]);

        return format_response(true, __('Password updated successfully'));
    }

}
