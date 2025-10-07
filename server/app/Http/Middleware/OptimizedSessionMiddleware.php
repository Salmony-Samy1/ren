<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OptimizedSessionMiddleware
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
        // تحسين: التحقق من وجود جلسة صالحة قبل إنشاء جديدة
        $sessionId = $request->session()->getId();
        $hasValidSession = $request->session()->has('admin_authenticated') || 
                          $request->session()->has('_token');
        
        // إذا كانت الجلسة صالحة، استخدمها بدلاً من إنشاء جديدة
        if ($hasValidSession && $sessionId) {
            // تحديث آخر نشاط للجلسة
            $request->session()->put('last_activity', now());
            return $next($request);
        }
        
        // فقط في حالة عدم وجود جلسة صالحة، أنشئ جلسة جديدة
        $response = $next($request);
        
        // تحسين: إضافة headers لتحسين الأداء
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }
}
