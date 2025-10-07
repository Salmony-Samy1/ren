<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\NationalIdVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NationalIdVerificationController extends Controller
{
    protected $verificationService;

    public function __construct(NationalIdVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * التحقق من الهوية الوطنية
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before:today'
        ], [
            'national_id.required' => 'الهوية الوطنية مطلوبة',
            'national_id.size' => 'الهوية الوطنية يجب أن تكون 10 أرقام',
            'national_id.regex' => 'الهوية الوطنية يجب أن تحتوي على أرقام فقط',
            'full_name.required' => 'الاسم الكامل مطلوب',
            'full_name.max' => 'الاسم الكامل يجب أن لا يتجاوز 255 حرف',
            'date_of_birth.before' => 'تاريخ الميلاد يجب أن يكون في الماضي'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->verificationService->verify(
            $request->national_id,
            $request->full_name,
            $request->date_of_birth
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['verified'] ? 'تم التحقق من الهوية الوطنية بنجاح' : 'فشل في التحقق من الهوية الوطنية',
                'data' => $result['data'],
                'gateway' => $result['gateway']
            ]);
        }

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
        ], [
            'national_id.required' => 'الهوية الوطنية مطلوبة',
            'national_id.size' => 'الهوية الوطنية يجب أن تكون 10 أرقام',
            'national_id.regex' => 'الهوية الوطنية يجب أن تحتوي على أرقام فقط'
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
            'national_id' => 'required|string|size:10|regex:/^[0-9]{10}$/'
        ], [
            'national_id.required' => 'الهوية الوطنية مطلوبة',
            'national_id.size' => 'الهوية الوطنية يجب أن تكون 10 أرقام',
            'national_id.regex' => 'الهوية الوطنية يجب أن تحتوي على أرقام فقط'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->verificationService->revokeVerification($request->national_id);

        return response()->json($result);
    }

    /**
     * الحصول على معلومات التحقق
     */
    public function info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string|size:10|regex:/^[0-9]{10}$/'
        ], [
            'national_id.required' => 'الهوية الوطنية مطلوبة',
            'national_id.size' => 'الهوية الوطنية يجب أن تكون 10 أرقام',
            'national_id.regex' => 'الهوية الوطنية يجب أن تحتوي على أرقام فقط'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // الحصول على معلومات من التخزين المؤقت
        $cacheKey = "national_id_verification_{$request->national_id}";
        $verificationInfo = cache($cacheKey);

        if (!$verificationInfo) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد معلومات تحقق لهذه الهوية الوطنية'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $verificationInfo['data'],
            'expires_at' => $verificationInfo['expires_at']
        ]);
    }
}
