<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EventManagementAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        $user = Auth::user();

        // Check if user has admin privileges
        if (!$user->hasRole(['admin', 'event_manager']) && $user->type !== 'admin') {
            abort(403, 'ليس لديك صلاحية للوصول إلى إدارة الفعاليات');
        }

        // Additional security checks
        if ($user->is_banned || $user->deleted_at) {
            abort(403, 'حسابك محظور أو محذوف');
        }

        // Log access attempt
        activity()
            ->causedBy($user)
            ->log('Accessed event management panel');

        return $next($request);
    }
}
