<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CompanyProfile;

class RequireNationalIdVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من أن المستخدم مزود خدمة
        if (auth()->user()->type !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب متاح فقط لمزودي الخدمة'
            ], 403);
        }

        // التحقق من وجود ملف شخصي للشركة
        $profile = CompanyProfile::where('user_id', auth()->id())->first();
        
        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إنشاء ملف شخصي للشركة أولاً'
            ], 400);
        }

        // التحقق من وجود هوية وطنية
        if (!$profile->national_id) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إدخال الهوية الوطنية أولاً'
            ], 400);
        }

        // التحقق من إعدادات النظام
        $verificationRequired = get_setting('national_id_verification_required_for_approval', true);
        
        if ($verificationRequired && !$profile->national_id_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'يجب التحقق من الهوية الوطنية قبل المتابعة',
                'verification_required' => true,
                'profile_id' => $profile->id
            ], 400);
        }

        return $next($request);
    }
}
