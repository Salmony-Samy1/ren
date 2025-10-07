<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\BroadcastServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '/api/v1'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'user_type' => \App\Http\Middleware\UserTypeMiddleware::class,
            'ctx' => \App\Http\Middleware\ContextSwitchMiddleware::class,
            'main_service_approved' => \App\Http\Middleware\EnsureProviderMainServiceApproved::class,
            'phone.verified' => \App\Http\Middleware\EnsurePhoneVerified::class,
            'require_national_id_verification' => \App\Http\Middleware\RequireNationalIdVerification::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'phone.verified.optional' => \App\Http\Middleware\PhoneVerificationOptional::class,
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'force.guard.api' => \App\Http\Middleware\ForceApiGuard::class,
            'payment.rate.limit' => \App\Http\Middleware\PaymentRateLimitMiddleware::class,
            'csp' => \App\Http\Middleware\CSPMiddleware::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.api.auth' => \App\Http\Middleware\AdminApiAuth::class,
            'secure.admin.db' => \App\Http\Middleware\SecureAdminDatabaseMiddleware::class,
            'sync.admin.auth' => \App\Http\Middleware\SyncAdminAuth::class,
            'optimized.session' => \App\Http\Middleware\OptimizedSessionMiddleware::class,
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
            'terms.accepted' => \App\Http\Middleware\EnsureTermsAccepted::class,
        ]);

        $middleware->api()->append([
            \App\Http\Middleware\LangMiddleware::class,
            \App\Http\Middleware\ApiResponseCacheHeaders::class,
            \App\Http\Middleware\ETagMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\ContextSwitchMiddleware::class,
            \App\Http\Middleware\ApiPresenceHeartbeat::class, // record presence
        ]);

        $middleware->web()->append([
            \App\Http\Middleware\LangMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SanitizeInput::class,
        ]);
        
        // تعديل: إضافة middleware الجلسات والكوكيز للمسارات الـ web مع تحسينات
        $middleware->web()->prepend([
            // [إضافة] هذا السطر ضروري لتشفير وفك تشفير الكوكيز
            \Illuminate\Cookie\Middleware\EncryptCookies::class, 
            // [إضافة] هذا السطر ضروري لإضافة الكوكيز للرد
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, 
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\OptimizedSessionMiddleware::class, // تحسين إدارة الجلسات
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);
        
        // إضافة استثناء CSRF لـ session-login و API routes
        $middleware->validateCsrfTokens(except: [
            'admin/session-login',
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();