<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminTokenAuthMiddleware
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
        // للطلبات العادية (غير API/AJAX)، نستخدم النظام المخصص
        if (!$request->ajax() && !$request->wantsJson() && !$request->expectsJson()) {
            // التحقق من النظام المخصص للمصادقة
            $isAuthenticated = $request->session()->get('admin_authenticated', false);
            $userData = $request->session()->get('admin_user');
            
            if ($isAuthenticated && $userData && isset($userData['user'])) {
                $user = $userData['user'];
                
                // التحقق من أن المستخدم لا يزال موجوداً في قاعدة البيانات
                $currentUser = \App\Models\User::find($user->id);
                
                if ($currentUser && $currentUser->type === 'admin') {
                    // التحقق من انتهاء صلاحية الجلسة (اختياري)
                    $loginTime = $request->session()->get('admin_login_time', 0);
                    $sessionLifetime = config('session.lifetime', 120) * 60; // تحويل إلى ثواني
                    
                    if (time() - $loginTime < $sessionLifetime) {
                        // المصادقة ناجحة، المتابعة
                        return $next($request);
                    } else {
                        // انتهت صلاحية الجلسة
                        $request->session()->forget(['admin_user', 'admin_authenticated', 'admin_login_time']);
                    }
                } else {
                    // المستخدم غير موجود أو ليس أدمن
                    $request->session()->forget(['admin_user', 'admin_authenticated', 'admin_login_time']);
                }
            }
            
            // إعادة التوجيه إلى صفحة تسجيل الدخول
            return redirect('/login')->with('error', 'يجب تسجيل الدخول أولاً');
        }
        
        // للطلبات API أو AJAX، نستخدم التوكن
        $token = $request->bearerToken() ?? $request->header('Authorization');
        
        if ($token) {
            try {
                // إزالة "Bearer " من بداية التوكن إذا كان موجوداً
                $token = str_replace('Bearer ', '', $token);
                
                // التحقق من صحة التوكن
                $user = JWTAuth::setToken($token)->authenticate();
                
                if ($user && $user->type === 'admin') {
                    // تسجيل دخول المستخدم في النظام للطلبات العادية أيضاً
                    Auth::login($user);
                    return $next($request);
                }
                
            } catch (JWTException $e) {
                // تجاهل الأخطاء والاستمرار
            }
        }
        
        // للطلبات AJAX، إرجاع JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول',
                'redirect' => '/login'
            ], 401);
        }
        
        // للطلبات العادية، إعادة التوجيه إلى صفحة تسجيل الدخول
        return redirect('/login')->with('error', 'يجب تسجيل الدخول أولاً');
    }
}
