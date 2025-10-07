<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminWebAuthMiddleware
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
        // تحقق من وجود مستخدم مسجل دخول في الجلسة أولاً
        if (Auth::check() && Auth::user()->type === 'admin') {
            return $next($request);
        }
        
        // للطلبات API أو AJAX، نستخدم التوكن
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            try {
                $token = $request->bearerToken() ?? $request->header('Authorization');
                
                if ($token) {
                    // إزالة "Bearer " من بداية التوكن إذا كان موجوداً
                    $token = str_replace('Bearer ', '', $token);
                    
                    // التحقق من صحة التوكن
                    $user = JWTAuth::setToken($token)->authenticate();
                    
                    if ($user && $user->type === 'admin') {
                        // تسجيل دخول المستخدم في النظام للطلبات العادية
                        Auth::login($user);
                        return $next($request);
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'redirect' => '/login'
                ], 401);
                
            } catch (JWTException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'توكن غير صالح',
                    'redirect' => '/login'
                ], 401);
            }
        }
        
        // للطلبات العادية، إعادة التوجيه إلى صفحة تسجيل الدخول
        return redirect('/login')->with('error', 'يجب تسجيل الدخول أولاً');
    }
}
