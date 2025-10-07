<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SecureAdminDatabaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // التسجيل الأمني لكل طلب
        Log::channel('security')->info('Admin API Request', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'authenticated_user' => auth()->id(),
            'user_type' => auth()->user()?->type,
            'timestamp' => now()->toISOString()
        ]);

        // التحقق من وجود توكين صالح
        $user = auth()->user();
        if (!$user) {
            Log::channel('security')->warning('Unauthenticated Admin API Access Attempt', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'method' => $request->method()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'تم رفض الوصول. جلسة غير صالحة أو منتهية الصلاحية.',
                'error_code' => 'INVALID_SESSION',
                'security_logged' => true
            ], 401);
        }

        // التحقق من نوع المستخدم
        if ($user->type !== 'admin') {
            Log::channel('security')->warning('Non-Admin User Accessing Admin Endpoints', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'user_email' => $user->email,
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح لك بالوصول لهذه الصفحة.',
                'error_code' => 'ACCESS_DENIED',
                'security_logged' => true
            ], 403);
        }

        // التحقق من حالة الحساب
        if (!$user->is_active) {
            Log::channel('security')->warning('Inactive Admin Account Access Attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'تم تعطيل حسابك. اتصل بإدارة النظام.',
                'error_code' => 'ACCOUNT_DISABLED',
                'security_logged' => true
            ], 403);
        }

        // التحقق من آخر نشاط للمستخدم (اختياري)
        if ($user->last_login_at && $user->last_login_at->diffInHours(now()) > 24) {
            Log::channel('security')->info('Admin Long Session Usage', [
                'user_id' => $user->id,
                'last_login' => $user->last_login_at,
                'session_duration_hours' => $user->last_login_at->diffInHours(now())
            ]);
        }

        return $next($request);
    }
}

