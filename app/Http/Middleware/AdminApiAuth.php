<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminApiAuth
{
    /**
     * Handle an incoming request for Admin API routes
     * This middleware ensures strict authentication and authorization
     */
    public function handle(Request $request, Closure $next)
    {
        // الحصول على المستخدم المعتمد من JWT
        $user = $request->user('api');
        
        // إذا لم يتم العثور على مستخدم من JWT، تحقق من المصادقة عبر الجلسة
        if (!$user) {
            $sessionUser = $request->session()->get('admin_user');
            if ($sessionUser && $sessionUser->type === 'admin') {
                Auth::guard('api')->login($sessionUser);
                $user = $sessionUser;
            }
        }
        
        // إذا لم يتم العثور على مستخدم معتمد، إرجاع خطأ 401
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'تم رفض الوصول. يرجى تسجيل الدخول كمدير.',
                'error_code' => 'INVALID_ADMIN_TOKEN',
                'error' => 'Admin authentication required'
            ], 401);
        }
        
        // التحقق من أن المستخدم هو مدير
        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error', 
                'message' => 'تم رفض الوصول. صلاحيات مدير مطلوبة.',
                'error_code' => 'ADMIN_PERMISSION_DENIED',
                'error' => 'Admin access required'
            ], 403);
        }
        
        // التحقق من أن المستخدم فعال
        if ($user->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'تم تعطيل حسابك. اتصل بإدارة النظام.',
                'error_code' => 'ADMIN_ACCOUNT_DISABLED',
                'error' => 'Admin account is inactive'
            ], 403);
        }
        
        return $next($request);
    }
}
