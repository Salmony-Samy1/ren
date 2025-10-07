<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Traits\LogUserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use App\Models\CustomerProfile;
use App\Models\CustomerHobby;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\OtpService;
use App\Services\SmsService;
use App\Http\Requests\UpdateBasicInfoRequest;
use App\Http\Requests\UpdateCustomerProfileRequest;
use App\Http\Requests\UpdateCompanyProfileRequest;

class UserProfileController extends Controller
{
    use LogUserActivity;
    /**
     * The `__construct` method and the middleware call have been removed from the controller.
     * Middleware is now applied directly in the routes file for better separation of concerns
     * and to resolve the "undefined method" error.
     */

    /**
     * Fetch the authenticated user's profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        $user = auth()->user();

        $user->loadCount(['followers', 'follows', 'bookings'])
            ->loadAvg('reviews', 'rating');

        if ($user->type === 'customer') {
            $user->load('customerProfile');
        } else if ($user->type === 'company') {
            $user->load('companyProfile');
        }
        
        return format_response(true, __('User profile fetched successfully'), new \App\Http\Resources\UserResource($user));
    }

    public function updateBasicInfo(UpdateBasicInfoRequest $request)
    {
        $user = Auth::user();
        $validatedData = $request->validated();

        $user->update($validatedData);

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        // تسجيل نشاط تحديث المعلومات الأساسية
        $this->logProfileUpdate('المعلومات الأساسية', $validatedData);

        return format_response(true, __('Basic info updated successfully'), new UserResource($user->fresh()));
    }

    /**
     * Update the customer profile for a customer user.
     */
    public function updateCustomerProfile(UpdateCustomerProfileRequest $request)
    {
        $user = Auth::user();
        if ($user->type !== 'customer') {
            return format_response(false, __('Unauthorized access'), code: 403);
        }

        $validatedData = $request->validated();

        $profile = $user->customerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validatedData
        );
        if ($request->has('hobby_ids')) {
            $profile->hobbies()->sync($request->input('hobby_ids'));
        }

        // تسجيل نشاط تحديث ملف العميل
        $this->logProfileUpdate('ملف العميل', $validatedData);

        return format_response(
            true,
            __('Customer profile updated successfully'),
            new UserResource($user->fresh()->load('customerProfile.hobbies'))
        );
    }

    /**
     * Update the company profile for a provider/company user.
     */
    public function updateCompanyProfile(UpdateCompanyProfileRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->type, ['provider', 'company'])) {
            return format_response(false, __('Unauthorized access'), code: 403);
        }

        $profile = $user->companyProfile()->firstOrCreate(['user_id' => $user->id]);
        $profile->update($request->validated());

        if ($request->hasFile('company_logo')) {
            $profile->clearMediaCollection('company_logo');
            $profile->addMediaFromRequest('company_logo')->toMediaCollection('company_logo');
        }

        // تسجيل نشاط تحديث ملف الشركة
        $this->logProfileUpdate('ملف الشركة', $request->validated());

        return format_response(true, __('Company profile updated successfully'), new UserResource($user->fresh()->load('companyProfile')));
    }
    /**
     * Update the authenticated user's password.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return format_response(false, __('Validation failed'), $validator->errors(), 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return format_response(false, __('Current password is incorrect'), code: 401);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        // تسجيل نشاط تغيير كلمة المرور
        $this->logPasswordChange();

        return format_response(true, __('Password updated successfully'));
    }


    /**
     * Request OTP for account deletion (soft delete).
     */
    public function requestDeleteOtp(Request $request)
    {
        $user = Auth::user();
        $identifier = (string) $user->phone;
        $otpResult = OtpService::generateOtp($identifier);
        $token = is_object($otpResult) ? ($otpResult->token ?? null) : (is_array($otpResult) ? ($otpResult['token'] ?? null) : (string) $otpResult);

        $delivery = config('services.otp.delivery', 'log');
        if ($token && $delivery === 'sms') {
            try {
                app(SmsService::class)->sendOtp($identifier, $token);
            } catch (\Throwable $e) {
                report($e);
                return format_response(false, __('Failed to send OTP'), code: 500);
            }
        }
        return format_response(true, __('OTP sent successfully'), $delivery === 'sms' ? [] : ['otp' => $token]);
    }

    /**
     * Confirm account deletion after OTP verification; store reason; soft delete.
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|min:4|max:8',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return format_response(false, __('Validation failed'), $validator->errors(), 422);
        }

        $user = Auth::user();

        // Validate OTP bound to user's phone
        $identifier = (string) $user->phone;
        $ok = OtpService::validate($identifier, (string) $request->string('otp'));
        if (! $ok) {
            return format_response(false, __('Invalid or expired OTP'), code: 422);
        }

        // Store reason then soft delete
        $user->deleted_reason = (string) $request->string('reason');
        $user->save();
        $user->delete();

        return format_response(true, __('Account deleted successfully'));
    }

    public function uploadAvatar(\App\Http\Requests\User\UpdateAvatarRequest $request)
    {
        $user = Auth::user();
        $user->clearMediaCollection('avatar');
        $media = $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        $user->update(['avatar_url' => $media->getUrl()]);
        return format_response(true, __('Avatar updated successfully'), ['avatar_url' => $user->avatar_url]);
    }

    public function uploadCompanyLogo(\App\Http\Requests\User\UpdateCompanyLogoRequest $request)
    {
        $user = Auth::user();
        $profile = $user->companyProfile;
        if (!$profile) { return format_response(false, __('No company profile found'), code: 404); }
        $profile->clearMediaCollection('company_logo');
        $media = $profile->addMediaFromRequest('logo')->toMediaCollection('company_logo');
        $profile->update(['company_logo_url' => $media->getUrl()]);
        return format_response(true, __('Company logo updated successfully'), ['company_logo_url' => $profile->company_logo_url]);
    }

}
