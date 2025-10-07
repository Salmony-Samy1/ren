<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncAdminAuth
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
        // التحقق من الجلسة المخصصة للمدير
        $adminUser = $request->session()->get('admin_user');
        $isAuthenticated = $request->session()->get('admin_authenticated', false);
        
        if ($isAuthenticated && $adminUser && $adminUser->type === 'admin') {
            // تسجيل دخول المستخدم في نظام المصادقة العادي
            Auth::login($adminUser);
        }
        
        return $next($request);
    }
}

