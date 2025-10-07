<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // التحقق من الجلسة المخصصة للمدير
        $adminUser = $request->session()->get('admin_user');
        $isAuthenticated = $request->session()->get('admin_authenticated', false);
        
        // تحسين: التحقق من صحة الجلسة قبل إعادة التوجيه
        if (!$isAuthenticated || !$adminUser || $adminUser->type !== 'admin') {
            // تنظيف الجلسة الفاسدة
            $request->session()->forget(['admin_user', 'admin_authenticated', 'admin_login_time']);
            
            // إضافة logging فقط عند الفشل لتقليل الضغط على السيرفر
            \Log::warning('AdminAuth Failed', [
                'reason' => !$isAuthenticated ? 'not_authenticated' : 
                            (!$adminUser ? 'no_admin_user' : 'wrong_user_type'),
                'url' => $request->url(),
                'session_id' => $request->session()->getId()
            ]);
            
            return redirect('/login');
        }
        
        // التحقق من انتهاء صلاحية الجلسة
        $loginTime = $request->session()->get('admin_login_time');
        if ($loginTime && now()->diffInMinutes($loginTime) > config('session.lifetime', 120)) {
            $request->session()->forget(['admin_user', 'admin_authenticated', 'admin_login_time']);
            return redirect('/login');
        }
        
        // تسجيل دخول المستخدم في نظام المصادقة العادي
        Auth::login($adminUser);
        
        return $next($request);
    }
}