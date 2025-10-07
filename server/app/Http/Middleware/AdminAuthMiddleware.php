<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // محاولة الحصول على التوكن من الرأس أو من localStorage (سيتم إرساله من JavaScript)
            $token = $request->bearerToken() ?? $request->header('Authorization');
            
            // إذا لم يكن هناك توكن في الرأس، تحقق من وجود مستخدم مسجل دخول بالفعل
            if (!$token) {
                $user = Auth::user();
                if ($user && $user->type === 'admin') {
                    return $next($request);
                }
            } else {
                // إزالة "Bearer " من بداية التوكن إذا كان موجوداً
                $token = str_replace('Bearer ', '', $token);
                
                // التحقق من صحة التوكن
                $user = JWTAuth::setToken($token)->authenticate();
                
                if ($user && $user->type === 'admin') {
                    // تسجيل دخول المستخدم في النظام
                    Auth::login($user);
                    return $next($request);
                }
            }
            
            // إذا كان الطلب من AJAX، إرجاع JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'redirect' => '/login'
                ], 401);
            }
            
            // إعادة التوجيه إلى صفحة تسجيل الدخول
            return redirect('/login')->with('error', 'يجب تسجيل الدخول أولاً');
            
        } catch (JWTException $e) {
            // إذا كان الطلب من AJAX، إرجاع JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'توكن غير صالح',
                    'redirect' => '/login'
                ], 401);
            }
            
            // إعادة التوجيه إلى صفحة تسجيل الدخول
            return redirect('/login')->with('error', 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى');
        }
    }
}
