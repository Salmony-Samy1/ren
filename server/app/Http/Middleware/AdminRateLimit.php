<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        // التحقق من أن المستخدم مسجل دخول
        if (!Auth::check()) {
            return response()->json(['error' => 'غير مصرح بالوصول'], 401);
        }

        $user = Auth::user();
        
        // التحقق من أن المستخدم لديه صلاحية أدمن
        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'ليس لديك صلاحية للوصول'], 403);
        }

        // إنشاء مفتاح فريد للمستخدم والمسار
        $key = 'admin_rate_limit_' . $user->id . '_' . $request->path();
        
        // التحقق من عدد المحاولات
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            // تسجيل محاولة تجاوز الحد
            \Log::warning('Admin rate limit exceeded', [
                'user_id' => $user->id,
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attempts' => $attempts
            ]);
            
            return response()->json([
                'error' => 'تم تجاوز الحد المسموح من الطلبات',
                'message' => 'يرجى المحاولة مرة أخرى بعد ' . $decayMinutes . ' دقيقة',
                'retry_after' => $decayMinutes * 60
            ], 429);
        }
        
        // زيادة عدد المحاولات
        Cache::put($key, $attempts + 1, $decayMinutes * 60);
        
        // إضافة معلومات Rate Limit في الاستجابة
        $response = $next($request);
        
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - $attempts - 1));
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);
        
        return $response;
    }
}